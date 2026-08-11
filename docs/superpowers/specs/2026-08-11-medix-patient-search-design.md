# MEDIX Patient Search & Import — Design

## Purpose

Let staff search MEDIX HMS for a patient (by last name, optionally first name) directly from the existing "Patient Management" modal, and import the selected patient straight into the local `patients` table and prescription workflow — without re-typing HOMIS/local-search data by hand.

## Background / API

MEDIX exposes two endpoints:

1. **Token** — `POST https://medixhms.com:3000/api/auth/identity/token`, form-data body `grant_type=client_credentials`, `client_id`, `client_secret`. Assumed standard OAuth2 client-credentials response shape: `{ access_token, token_type, expires_in }`.
2. **Patient search** — `GET https://medixhms-external.com/api/External/GetPatientInformation?LastName=...&FirstName=...&MiddleName=...`, header `Authorization: Bearer <token>`. Sample response:

```json
[
  {
    "hospitalNo": "00000744",
    "lastName": "LAGOS",
    "firstName": "ROLANDO",
    "middleName": "ELNAS",
    "suffixName": "",
    "gender": "MALE",
    "birthDate": "05-29-1961",
    "age": 65,
    "civilStatus": "MARRIED",
    "region": "",
    "telephone": "",
    "mobileNo": "+63(916) 702-2289",
    "address": "N/A ALCANTARA, LAWAAN CEBU 6033"
  }
]
```

`LastName` is required by MEDIX; `FirstName`/`MiddleName` are optional.

## Credentials & Config

Add to `.env`:

```
MEDIX_CLIENT_ID=H07021036-CSMC
MEDIX_CLIENT_SECRET=Fu&1ld4Gfg*jIJn#q6my!k*Lp7Z$lRIQEN
MEDIX_TOKEN_URL=https://medixhms.com:3000/api/auth/identity/token
MEDIX_BASE_URL=https://medixhms-external.com/api/External
```

Add a `medix` entry to `config/services.php` reading these four vars. No new `.env.example` update requirement noted, but should mirror existing entries if that file is later touched.

## Backend Changes

All in `App\Http\Controllers\PatientController` — this app has no service-layer (no `app/Services`), so a private helper on the controller matches existing conventions (e.g. `getAuthorization()`).

1. **`private function getMedixToken()`**
   - Cache key `medix_access_token` via `Illuminate\Support\Facades\Cache` (file driver, already configured).
   - On cache miss: `Http::asForm()->post(config('services.medix.token_url'), [...])`, cache the resulting `access_token` for `expires_in - 60` seconds (fallback 300s if `expires_in` missing).
   - Throws/returns null on failure; caller handles the `code => 500` response.

2. **`public function searchMedixPatient(Request $request)`**
   - Validates `lastname` is present (400/500-style JSON error if missing, consistent with existing methods' try/catch + `response(['code' => 500, ...])` pattern).
   - Gets token via `getMedixToken()`.
   - `Http::withToken($token)->get(config('services.medix.base_url') . '/GetPatientInformation', ['LastName' => ..., 'FirstName' => ...])`.
   - Returns `{code: 200, results: [...]}` (array as returned by MEDIX, passed through) or `{code: 500, message: ...}` on failure/non-2xx.

3. **Route** (`routes/web.php`, inside the existing `auth` + `authority` group alongside `/patient/get`):
   ```
   Route::get('/patient/medix/search', [PatientController::class, 'searchMedixPatient']);
   ```

4. **`PatientController::store()`** — add `'id' => $patient->id` to the existing success response (currently `{code, message}` only). Purely additive; existing caller (`insertPatient()` in JS) ignores the new field.

## Frontend Changes

`resources/views/modals/prescription.blade.php` (`#patient_modal`) and `resources/views/js/home.blade.php`.

### Modal markup

- New button "SHOW MEDIX PATIENTS" near the top of `#patient_modal`'s body (above the existing HOMIS/local-search row). Toggles a `d-none` class on a new full-width section and flips its own label to "HIDE MEDIX PATIENTS".
- New section, appended at the bottom of the modal body (below the existing HOMIS-search + local-Patient-List row), initially `d-none`:
  - Text inputs: `#medix_lastname` (required), `#medix_firstname` (optional).
  - `#btn_search_medix` search button.
  - Results table `#medix_patient_list`, columns: Hospital No / Name / Birthdate / Sex / action — same visual style as the existing `#patient_list` table, with a select-icon button per row (reuses `bi-prescription` icon like the local list's action button).

### JS behavior (`home.blade.php`)

1. **Refactor**: extract the body of `makePrescription(id)` (home.blade.php:829-873) that currently sets up `#prescription_modal` fields/buttons and toggles modals into a new `openPrescriptionModal(hospital_id, name, birthdate, sex)` function. `makePrescription(id)` becomes:
   ```js
   function makePrescription(id) {
       const hospital_id = $("#patient_id_" + id + " td:eq(0)").text();
       const name = $("#patient_id_" + id + " td:eq(1)").text();
       const birthdate = $("#patient_id_" + id + " td:eq(2)").text();
       const sex = $("#patient_id_" + id + " td:eq(3)").text();
       openPrescriptionModal(hospital_id, name, birthdate, sex);
   }
   ```
   Behavior is unchanged for the existing local-list flow.

2. **`getMedixPatients(lastname, firstname)`** — mirrors `getPatients()`: fetch `patient/medix/search`, render `#medix_patient_list` rows, cache the raw result array in a module-level `medix_results` variable (index-addressed, since row IDs must stay DOM-safe and hospital numbers may repeat/be blank).
   - Empty lastname client-side guard, "Searching..." / "No record found" rows, `toastr.error` on failure — same pattern as `getPatients()`.

3. **`selectMedixPatient(index)`**:
   - Reads `medix_results[index]`.
   - Maps: `hospital_id` = `hospitalNo`, `fname` = `firstName`, `mname` = `middleName`, `lname` = `lastName`, `suffix` = `suffixName`, `birthdate` = `moment(birthDate, "MM-DD-YYYY").format("YYYY-MM-DD")`, `sex` = `gender.charAt(0)` (`MALE`→`M`, `FEMALE`→`F`).
   - Disables the clicked button + shows a small spinner (existing pattern from `btn_find`/`btn_save_patient`).
   - `POST` to `{{ route('insert_patient') }}` with these params (same shape `insertPatient()` already sends).
   - On success (`code == 200`): build `name = lname + ", " + fname + " " + mname`, `birthdate_display = moment(birthdate).format("MM/DD/YYYY")`, `toastr.success(data.message, "Information")`, then call `openPrescriptionModal(hospital_id, name, birthdate_display, sex)` (already known from the mapped params — no need to read `data.id`; `openPrescriptionModal` itself calls `getConcurrentTherapy(hospital_id)`, so no duplicate call is needed here).
   - On error: re-enable button, `toastr.error("Something went wrong", "Ooops")`.

   Note: the response's new `id` field is not actually needed by the frontend (the modal-opening flow keys off `hospital_id`, not the local `patients.id`), but is still exposed by the API for potential future use / consistency with `getPatientList()`'s `id`-keyed rows.

## Error Handling Summary

- Missing `lastname` on search → client-side guard (no request sent), same as existing local search fields.
- MEDIX token/API failure → `{code: 500, message}`, surfaced via `toastr.error`.
- Empty MEDIX results → "No record found" row, matching `getPatients()`'s existing empty state.
- `insert_patient` failure during select → `toastr.error`, prescription modal does **not** open, button re-enabled so the user can retry.

## Out of Scope

- No duplicate-detection UI beyond what `insert_patient` already does (update-by-`hospital_id` if it exists, else create).
- No manual review/edit step before saving a MEDIX-sourced patient (per explicit user decision — straight save-and-open).
- No retry/backoff logic for MEDIX token fetch beyond the single request + cache.
