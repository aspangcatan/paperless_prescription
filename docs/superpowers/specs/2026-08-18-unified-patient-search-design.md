# Unified Local/MEDIX Patient Search — Design

## Purpose

Replace the split "search HOMIS to fill a review form" / "search local patients to prescribe" experience in the Patient Management modal (`#patient_modal`) with a single search panel: two buttons, "Search Locally" and "Search MEDIX", both feeding one results table (`#patient_list`) tagged by source. Selecting a Local result behaves exactly as it does today (opens the prescription flow directly). Selecting a Medix result inserts it into the local `patients` table and then opens the prescription flow the same way — MEDIX becomes a one-click "find and prescribe," not a "find, review, save, then find again" flow.

The old "Search patient in HOMIS" card (hospital-ID lookup + the original MEDIX-search-into-form-fields flow, added in [2026-08-11-medix-patient-search-design.md](2026-08-11-medix-patient-search-design.md)) is not deleted — it's hidden (`d-none`) and unused by this new flow, kept only because its code stays valid for a possible future use.

## Background

This builds directly on the existing MEDIX integration: `PatientController::searchMedixPatient()` / `getMedixToken()`, the `patient/medix/search` route, and the `MEDIX_*` config are all reused unchanged except where noted below. See that design doc for MEDIX API shapes and the TLS/self-signed-cert handling — unaffected here.

## UI / Markup Changes

`resources/views/modals/prescription.blade.php`, `#patient_modal`:

1. **Left card** (currently "Search patient in HOMIS", lines ~441-512): wrap in `d-none`. No internal changes — `#btn_find`, `#btn_find_medix`, the form fields, `#btn_save_patient`, `#medix_results_container` all stay as-is, just permanently hidden. Nothing in this feature reveals it.

2. **Right panel** (currently lines ~514-535): keep `#search_firstname` / `#search_lastname`. Replace the single icon-only `#btn_search_local` with two labeled buttons:
   - `#btn_search_local` — text "Search Locally" (was icon-only)
   - `#btn_search_medix` (new) — text "Search MEDIX"

3. **`#patient_list` table** (currently lines ~541-556): add a first column "Source", holding a badge:
   - `Local` — `badge bg-secondary`
   - `Medix` — `badge bg-info text-white` (matches the existing "SEARCH MEDIX" button color)

   Resulting columns: Source, Hospital ID, Name, Birthdate, Sex, Action.

   Action column: Local rows keep the existing prescription icon (`bi-prescription`). Medix rows get `bi-cloud-download`, signaling "import and prescribe," since the click does more than a Local click (insert, then prescribe).

## Data Flow / JS Changes

`resources/views/js/home.blade.php`:

### Result arrays

Replace DOM-scraping with two module-level arrays, mirroring the existing `medix_results` pattern:

- `local_results = []` — populated by Local search, same shape `patient/get` already returns (`id, hospital_id, fname, mname, lname, suffix, birthdate, sex`).
- `medix_results = []` — populated by Medix search (unchanged shape from `patient/medix/search`).

### Search handlers

- `#btn_search_local` click / Enter-in-search-fields (existing handlers at ~790/799, retargeted): calls `patient/get` as today, but instead of a bespoke local-only render, stores results into `local_results` and calls a shared `renderPatientRows('local', local_results)`.
- `#btn_search_medix` click (new): reads `#search_lastname` (required — client-side guard, same "Lastname is required" pattern as the old `#btn_find_medix`) and `#search_firstname` (optional). Calls `patient/medix/search?lastname=...&firstname=...` (firstname omitted from the query string if blank). Stores results into `medix_results`, calls `renderPatientRows('medix', medix_results)`.

### Merge-without-duplicating render

`renderPatientRows(source, results)`:
- Removes only existing `<tr>` elements tagged `data-source="<source>"` from `#patient_list` (not the other source's rows).
- Appends a fresh `<tr data-source="<source>">` per result, with the Source badge as the first `<td>`, and the action button wired to `selectPatientRow('<source>', <index>)`.
- If `#patient_list` ends up with zero rows across both sources, show the existing "No record found" placeholder row (untagged, removed by the next render of either source).

This lets a user search Locally, then MEDIX, and see both sets at once; re-running the same search just replaces that source's own rows.

### Row selection

`selectPatientRow(source, index)` replaces the current `makePrescription(id)` (which reads `id` and scrapes sibling `<td>` text — both broken by the new Source column shifting cell positions, and now unnecessary since data is already in hand):

- **`source === 'local'`**: look up `local_results[index]`, call the prescription-opening logic directly with that object (same effect as today's `makePrescription`, just data-driven instead of DOM-scraped).
- **`source === 'medix'`**: look up `medix_results[index]`, map fields to the `insert_patient` payload shape (`hospital_id: hospitalNo, fname: firstName, mname: middleName, lname: lastName, suffix: suffixName, birthdate: <converted>, sex: <M/F>`), add `check_duplicate: true`, and POST to `insert_patient`. Button shows a spinner during the request (matching `#btn_save_patient`'s existing pattern).
  - On success: response includes `id`. Immediately call the same prescription-opening logic used for Local, with `{id, hospital_id, fname, mname, lname, birthdate, sex}` — no re-search, no intermediate render.
  - On `code: 500` "Duplicate entry" (or any other failure): toastr error with the server's message, spinner clears, no prescription flow opens, row remains as-is for retry.

## Backend Changes

`App\Http\Controllers\PatientController`:

1. **`store()`** (~line 184):
   - Add `'id' => $patient->id` to the success response (additive — no existing caller breaks).
   - Add duplicate-rejection: when `$request->check_duplicate` is truthy, look up `Patient::where('hospital_id', $request->hospital_id)->first()` *before* the existing upsert block. If found, return `{code: 500, message: 'Duplicate entry: a patient with this Hospital ID already exists'}` immediately — no save, no update. When the flag is absent (legacy left-card ADD/UPDATE flow), behavior is exactly as today (upsert by `hospital_id`).

2. **`searchMedixPatient()`** (~line 51): accept an optional `firstname` request param; when present, include it as `FirstName` in the `GetPatientInformation` query alongside the required `LastName`. Omitted entirely from the query when blank.

No new routes — `patient/get`, `patient/medix/search`, and `insert_patient` all already exist and are reused as-is (payload/response shape additions only).

## Error Handling Summary

- Missing lastname on MEDIX search → client-side guard, no request sent (same as before).
- MEDIX/local search failures → existing inline "Something went wrong" / "No record found" patterns, per-source.
- Duplicate `hospital_id` on Medix-row insert → `{code: 500, message: 'Duplicate entry...'}`, shown via toastr, flow stops (no prescription modal opens). Re-selecting the same Medix row will hit the same error until the local record is otherwise removed — the user's path for an already-imported patient is "Search Locally" instead.
- Insert network/API failure (non-duplicate) → toastr error, same as duplicate case minus the specific message.

## Out of Scope

- The hidden left card ("Search patient in HOMIS") is not touched beyond adding `d-none` — no cleanup of `#btn_find`/`#btn_find_medix`/`#medix_results_container`, no removal of their JS handlers.
- No UI to un-hide the left card. If a future feature needs it, that's a separate change.
- No de-duplication logic between `local_results` and `medix_results` themselves (e.g. hiding a Medix row if the same hospital_id is already showing as a Local row) — only the insert-time backend check on `hospital_id` matters.
- No change to `patient/get`'s (Local search) query behavior — only its rendering path changes.
