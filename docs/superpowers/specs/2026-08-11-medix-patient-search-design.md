# MEDIX Patient Search & Import — Design

## Purpose

Let staff search MEDIX HMS for a patient by last name directly from the existing "Patient Management" modal, and pull the selected match's details into the same search-form fields the HOMIS search already fills — so staff can review before saving into the local `patients` table, without re-typing HOMIS/local-search data by hand.

## Background / API

MEDIX exposes two endpoints:

1. **Token** — `POST https://medixhms.com:3000/api/auth/identity/token`, form-data body `grant_type=client_credentials`, `client_id`, `client_secret`. Assumed standard OAuth2 client-credentials response shape: `{ access_token, token_type, expires_in }`.
2. **Patient search** — `GET https://medixhms-external.com/api/External/GetPatientInformation?LastName=...`, header `Authorization: Bearer <token>`. Sample response:

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

`LastName` is required by MEDIX. This app only ever sends `LastName` (no separate first-name search field in the UI), so a last name may return multiple matches — the UI must let the user pick the right one.

## Credentials & Config

Add to `.env` (double-quote the secret — it contains `&`/`#`/`$`, and unquoted `#` starts a comment in `.env` parsing):

```
MEDIX_CLIENT_ID=H07021036-CSMC
MEDIX_CLIENT_SECRET="Fu&1ld4Gfg*jIJn#q6my!k*Lp7Z$lRIQEN"
MEDIX_TOKEN_URL=https://medixhms.com:3000/api/auth/identity/token
MEDIX_BASE_URL=https://medixhms-external.com/api/External
```

Add a `medix` entry to `config/services.php` reading these four vars.

## Backend Changes

All in `App\Http\Controllers\PatientController` — this app has no service layer (no `app/Services`), so a private helper on the controller matches existing conventions (e.g. `getAuthorization()`).

1. **`private function getMedixToken()`**
   - Cache key `medix_access_token` via `Illuminate\Support\Facades\Cache` (file driver, already configured).
   - On cache miss: `Http::asForm()->post(config('services.medix.token_url'), [...])`, cache the resulting `access_token` for `expires_in - 60` seconds (fallback 300s if `expires_in` missing).
   - Returns null on failure; caller handles the `code => 500` response.

2. **`public function searchMedixPatient(Request $request)`**
   - Validates `lastname` is present (`code => 500` JSON error if missing, consistent with existing methods' try/catch + `response(['code' => 500, ...])` pattern).
   - Gets token via `getMedixToken()`.
   - `Http::withToken($token)->get(config('services.medix.base_url') . '/GetPatientInformation', ['LastName' => $request->lastname])`.
   - Returns `{code: 200, results: [...]}` (array as returned by MEDIX, passed through) or `{code: 500, message: ...}` on failure/non-2xx.

3. **Route** (`routes/web.php`, inside the existing `authority.api` middleware group alongside `/patient/get`):
   ```
   Route::get('/patient/medix/search', [PatientController::class, 'searchMedixPatient']);
   ```

No changes to `store()` / `insert_patient` — the MEDIX flow reuses that endpoint exactly as the existing HOMIS/manual-entry flow already does.

## Frontend Changes

`resources/views/modals/prescription.blade.php` (`#patient_modal`) and `resources/views/js/home.blade.php`.

### Modal markup

- New button `#btn_find_medix` ("SEARCH MEDIX") added directly below the existing SEARCH button (`#btn_find`), in the same `col-md-12` button stack, above ADD / UPDATE. No new input fields — it reads the existing `#lname` field.
- New results section added inside the same left-hand card, below the button stack, hidden by default (`d-none`):
  - A small inline error/status line (`#medix_search_status`), e.g. "Lastname is required" / "No record found" / failure messages.
  - A compact results table (`#medix_patient_list` tbody), columns: Hospital No, Name, Birthdate, Sex, and a select-icon action button per row.

### JS behavior (`home.blade.php`)

1. **`medix_results` module-level array** — holds the raw array from the last search, index-addressed (hospital numbers can repeat/be blank, so index is the stable key).

2. **`#btn_find_medix` click handler**:
   - Reads `#lname`, trims it; if empty, shows the inline status message and does not fire a request (same client-side-guard pattern as `#search_firstname`/`#search_lastname`).
   - Disables the button + shows a spinner (matching `#btn_find`'s existing pattern).
   - `fetch` to `` `{{ url('patient/medix/search') }}?lastname=` + encodeURIComponent(lastname) ``.
   - On `code == 500`: show the failure message in `#medix_search_status`, keep results area visible but table empty.
   - On success with `results.length == 0`: show "No record found".
   - On success with results: populate `#medix_patient_list` rows, reveal the results section (remove `d-none`).
   - Re-enables the button/removes spinner in all cases.

3. **`selectMedixPatient(index)`** (row action button `onclick`):
   - Reads `medix_results[index]`.
   - Maps and fills the existing search-panel fields: `#hospital_id` = `hospitalNo`, `#fname` = `firstName`, `#mname` = `middleName`, `#lname` = `lastName`, `#suffix` = `suffixName`, `#birthdate` = `moment(birthDate, "MM-DD-YYYY").format("YYYY-MM-DD")`, `#sex` = `gender.charAt(0)` (`MALE`→`M`, `FEMALE`→`F`).
   - Hides the results section again.
   - No network call here — this only populates the form. The user reviews the filled fields and clicks the existing **ADD / UPDATE** button (`#btn_save_patient`) to persist, exactly as they would after a HOMIS search.

## Error Handling Summary

- Missing `lastname` on search → client-side guard (no request sent), inline message.
- MEDIX token/API failure → `{code: 500, message}`, surfaced inline in `#medix_search_status`.
- Empty MEDIX results → "No record found" message, matching `getPatients()`'s existing empty state pattern.
- Saving the filled fields afterward reuses the existing `insert_patient` flow/error handling unchanged (no new failure path introduced there).

## Out of Scope

- No duplicate-detection UI beyond what `insert_patient` already does (update-by-`hospital_id` if it exists, else create).
- No search by first name / middle name — MEDIX search is last-name-only, matching the single reused `#lname` field.
- No retry/backoff logic for MEDIX token fetch beyond the single request + cache.
- No auto-save or auto-opening of the prescription modal on selection — selecting a MEDIX match only fills the form for review, same as the HOMIS flow.
