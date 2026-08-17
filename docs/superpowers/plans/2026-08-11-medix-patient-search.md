# MEDIX Patient Search & Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "SEARCH MEDIX" button to the Patient Management modal's existing HOMIS-search panel that queries the MEDIX HMS API by last name and, on selection, fills the same search-form fields the HOMIS search already fills — so staff can review before saving via the existing ADD / UPDATE button.

**Architecture:** Backend adds two things to the existing `PatientController` (no service layer — none exists in this codebase): a private OAuth token helper (cached) and a `searchMedixPatient` action that proxies MEDIX's `GetPatientInformation`. Frontend adds one button and a small results table to the existing left-hand search card in `#patient_modal`, plus two new JS functions. No changes to `insert_patient`/`store()` and no changes to the prescription-modal-opening flow — the MEDIX flow stops at "fill the form fields," reusing the existing save step unchanged.

**Tech Stack:** Laravel 8 (Guzzle-backed `Http` facade, `Cache` facade, file cache driver), Bootstrap 5 modal, jQuery, moment.js — all already used elsewhere in this file, no new dependencies.

---

## Task 1: MEDIX credentials & config

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\.env` (not committed — gitignored)
- Modify: `c:\wamp64\projects\paperless_prescription\config\services.php`

- [ ] **Step 1: Add MEDIX vars to `.env`**

Append to the end of `c:\wamp64\projects\paperless_prescription\.env`:

```
MEDIX_CLIENT_ID=H07021036-CSMC
MEDIX_CLIENT_SECRET="Fu&1ld4Gfg*jIJn#q6my!k*Lp7Z$lRIQEN"
MEDIX_TOKEN_URL=https://medixhms.com:3000/api/auth/identity/token
MEDIX_BASE_URL=https://medixhms-external.com/api/External
```

Note the double quotes around the secret — it contains `&`, `#`, and `$`, and an unquoted `#` starts a comment in `.env` parsing.

- [ ] **Step 2: Add `medix` entry to `config/services.php`**

In `c:\wamp64\projects\paperless_prescription\config\services.php`, change:

```php
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
```

to:

```php
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'medix' => [
        'client_id' => env('MEDIX_CLIENT_ID'),
        'client_secret' => env('MEDIX_CLIENT_SECRET'),
        'token_url' => env('MEDIX_TOKEN_URL'),
        'base_url' => env('MEDIX_BASE_URL'),
    ],

];
```

- [ ] **Step 3: Run `php artisan config:clear` so the cached config (if any) picks up the new keys**

Run: `cd c:\wamp64\projects\paperless_prescription && php artisan config:clear`
Expected: `Configuration cache cleared successfully.` (or "nothing to clear" — either is fine)

- [ ] **Step 4: Commit**

```bash
git add config/services.php
git commit -m "Add MEDIX service config"
```

(`.env` is gitignored — do not attempt to add it.)

---

## Task 2: `searchMedixPatient` endpoint (TDD)

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\app\Http\Controllers\PatientController.php`
- Modify: `c:\wamp64\projects\paperless_prescription\routes\web.php:121`
- Create: `c:\wamp64\projects\paperless_prescription\tests\Unit\PatientControllerMedixTest.php`

- [ ] **Step 1: Write the failing tests**

Create `c:\wamp64\projects\paperless_prescription\tests\Unit\PatientControllerMedixTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Http\Controllers\PatientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatientControllerMedixTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('medix_access_token');

        config([
            'services.medix.token_url' => 'https://medixhms.com:3000/api/auth/identity/token',
            'services.medix.base_url' => 'https://medixhms-external.com/api/External',
            'services.medix.client_id' => 'test-client-id',
            'services.medix.client_secret' => 'test-secret',
        ]);
    }

    public function testSearchMedixPatientReturnsMappedResults()
    {
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => Http::response([[
                'hospitalNo' => '00000744',
                'lastName' => 'LAGOS',
                'firstName' => 'ROLANDO',
                'middleName' => 'ELNAS',
                'suffixName' => '',
                'gender' => 'MALE',
                'birthDate' => '05-29-1961',
            ]], 200),
        ]);

        $request = Request::create('/patient/medix/search', 'GET', ['lastname' => 'LAGOS']);
        $response = (new PatientController())->searchMedixPatient($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);
        $this->assertCount(1, $data['results']);
        $this->assertEquals('LAGOS', $data['results'][0]['lastName']);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'GetPatientInformation')
                && $req->hasHeader('Authorization', 'Bearer test-token')
                && $req['LastName'] === 'LAGOS';
        });
    }

    public function testSearchMedixPatientRequiresLastname()
    {
        $request = Request::create('/patient/medix/search', 'GET', []);
        $response = (new PatientController())->searchMedixPatient($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
    }

    public function testSearchMedixPatientCachesToken()
    {
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => Http::response([], 200),
        ]);

        $controller = new PatientController();
        $controller->searchMedixPatient(Request::create('/', 'GET', ['lastname' => 'LAGOS']));
        $controller->searchMedixPatient(Request::create('/', 'GET', ['lastname' => 'LAGOS']));

        // 1 token fetch (first call only, cached on the 2nd) + 2 patient searches
        Http::assertSentCount(3);
    }

    public function testSearchMedixPatientReturnsErrorWhenMedixFails()
    {
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => Http::response('Server error', 500),
        ]);

        $request = Request::create('/', 'GET', ['lastname' => 'LAGOS']);
        $response = (new PatientController())->searchMedixPatient($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `cd c:\wamp64\projects\paperless_prescription && php artisan test --filter=PatientControllerMedixTest`
Expected: FAIL — `searchMedixPatient` does not exist on `PatientController` (Error: Call to undefined method).

- [ ] **Step 3: Add `Http`/`Cache` imports and `searchMedixPatient` + `getMedixToken` to `PatientController`**

In `c:\wamp64\projects\paperless_prescription\app\Http\Controllers\PatientController.php`, change the `use` block at the top:

```php
use App\Models\ErrorLog;
use App\Models\HomisPatient;
use App\Models\Patient;
use App\Models\PatientDisposition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
```

to:

```php
use App\Models\ErrorLog;
use App\Models\HomisPatient;
use App\Models\Patient;
use App\Models\PatientDisposition;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
```

Then add these two methods to the `PatientController` class, right after `getAuthorization()` (i.e. right before `searchHomisPatient`):

```php
    private function getMedixToken()
    {
        $token = Cache::get('medix_access_token');
        if ($token) {
            return $token;
        }

        $response = Http::asForm()->post(config('services.medix.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.medix.client_id'),
            'client_secret' => config('services.medix.client_secret'),
        ]);

        if (!$response->successful()) {
            return null;
        }

        $access_token = $response->json('accessToken');
        $expires_in = $response->json('expiresIn');
        $ttl = $expires_in ? max($expires_in - 60, 60) : 300;

        Cache::put('medix_access_token', $access_token, $ttl);

        return $access_token;
    }

    public function searchMedixPatient(Request $request)
    {
        try {
            if (empty($request->lastname)) {
                return response(['code' => 500, 'message' => 'Lastname is required']);
            }

            $token = self::getMedixToken();
            if (!$token) {
                return response(['code' => 500, 'message' => 'Unable to authenticate with MEDIX']);
            }

            $response = Http::withToken($token)->get(config('services.medix.base_url') . '/GetPatientInformation', [
                'LastName' => $request->lastname,
            ]);

            if (!$response->successful()) {
                return response(['code' => 500, 'message' => 'MEDIX request failed']);
            }

            return response(['code' => 200, 'results' => $response->json()]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `cd c:\wamp64\projects\paperless_prescription && php artisan test --filter=PatientControllerMedixTest`
Expected: PASS (4 tests)

- [ ] **Step 5: Add the route**

In `c:\wamp64\projects\paperless_prescription\routes\web.php`, change:

```php
        Route::get('/homis_patient/get/{keyword}', [\App\Http\Controllers\PatientController::class, 'searchHomisPatient']);
        Route::get('/patient/get', [\App\Http\Controllers\PatientController::class, 'getPatient']);
        Route::get('/patient/get/list', [\App\Http\Controllers\PatientController::class, 'getPatientList']);
```

to:

```php
        Route::get('/homis_patient/get/{keyword}', [\App\Http\Controllers\PatientController::class, 'searchHomisPatient']);
        Route::get('/patient/get', [\App\Http\Controllers\PatientController::class, 'getPatient']);
        Route::get('/patient/medix/search', [\App\Http\Controllers\PatientController::class, 'searchMedixPatient']);
        Route::get('/patient/get/list', [\App\Http\Controllers\PatientController::class, 'getPatientList']);
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PatientController.php routes/web.php tests/Unit/PatientControllerMedixTest.php
git commit -m "Add MEDIX patient search endpoint"
```

---

## Task 3: SEARCH MEDIX button + results markup in `#patient_modal`

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\resources\views\modals\prescription.blade.php:478-489`

- [ ] **Step 1: Add the "SEARCH MEDIX" button and results section**

In `c:\wamp64\projects\paperless_prescription\resources\views\modals\prescription.blade.php`, change:

```blade
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button class="btn form-control btn-secondary" id="btn_find">SEARCH
                                        </button>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <button class="btn form-control btn-success" id="btn_save_patient">ADD / UPDATE
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
```

to:

```blade
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button class="btn form-control btn-secondary" id="btn_find">SEARCH
                                        </button>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <button class="btn form-control btn-info text-white" id="btn_find_medix">SEARCH MEDIX
                                        </button>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <button class="btn form-control btn-success" id="btn_save_patient">ADD / UPDATE
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3 d-none" id="medix_results_container">
                                    <div class="small text-danger mb-1 d-none" id="medix_search_status"></div>
                                    <div class="table-responsive" style="max-height: 250px !important;">
                                        <table class="table table-sm table-hover text-center">
                                            <thead>
                                            <tr>
                                                <th>Hospital No</th>
                                                <th>Name</th>
                                                <th>Birthdate</th>
                                                <th>Sex</th>
                                                <th style="width: 4%"></th>
                                            </tr>
                                            </thead>
                                            <tbody id="medix_patient_list"></tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
```

Note: this block is uniquely anchored by `#btn_find`/`#btn_save_patient`, so it only matches the `#patient_modal` instance.

- [ ] **Step 2: Commit**

```bash
git add resources/views/modals/prescription.blade.php
git commit -m "Add SEARCH MEDIX button and results panel to patient modal"
```

---

## Task 4: MEDIX search + select JS behavior

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\resources\views\js\home.blade.php:16` (global var)
- Modify: `c:\wamp64\projects\paperless_prescription\resources\views\js\home.blade.php` (new event handler + functions, near `#btn_find`/`getPatients()`)

- [ ] **Step 1: Add the `medix_results` global**

Change:

```js
    let prescription_id = "";
    let page = 0;
    let is_loaded = false;
```

to:

```js
    let prescription_id = "";
    let page = 0;
    let is_loaded = false;
    let medix_results = [];
```

- [ ] **Step 2: Add `getMedixPatients()` and `selectMedixPatient()`**

Add these two functions immediately before the existing `getPatients(firstname, lastname)` function:

```js
    function getMedixPatients(lastname) {
        $("#medix_search_status").addClass("d-none").text("");
        $("#medix_patient_list").empty();
        $("#medix_results_container").removeClass("d-none");
        $("#medix_patient_list").append("<tr><td colspan='5'>Searching patient...</td></tr>");

        const btn = $("#btn_find_medix");
        const original_html = btn.html();
        btn.prop("disabled", true);
        btn.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`);

        fetch("{{ url('patient/medix/search') }}?lastname=" + encodeURIComponent(lastname), {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#medix_patient_list").empty();

                if (data.code == 500) {
                    $("#medix_search_status").removeClass("d-none").text(data.message);
                    return;
                }

                medix_results = data.results;

                if (medix_results.length < 1) {
                    $("#medix_search_status").removeClass("d-none").text("No record found");
                    return;
                }

                medix_results.forEach((it, index) => {
                    const name = it.lastName + ", " + it.firstName + " " + it.middleName;
                    const tr = `
                        <tr>
                            <td>` + it.hospitalNo + `</td>
                            <td>` + name + `</td>
                            <td>` + it.birthDate + `</td>
                            <td>` + it.gender + `</td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="selectMedixPatient(` + index + `)">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </td>
                        </tr>`;
                    $("#medix_patient_list").append(tr);
                });
            }).catch(err => {
            console.log(err);
            $("#medix_patient_list").empty();
            $("#medix_search_status").removeClass("d-none").text("Something went wrong");
        }).finally(() => {
            btn.prop("disabled", false);
            btn.html(original_html);
        });
    }

    function selectMedixPatient(index) {
        const it = medix_results[index];
        if (!it) return;

        $("#hospital_id").val(it.hospitalNo || "");
        $("#fname").val((it.firstName || "").toUpperCase());
        $("#mname").val((it.middleName || "").toUpperCase());
        $("#lname").val((it.lastName || "").toUpperCase());
        $("#suffix").val((it.suffixName || "").toUpperCase());
        $("#birthdate").val(moment(it.birthDate, "MM-DD-YYYY").format("YYYY-MM-DD"));
        $("#sex").val((it.gender || "").charAt(0));

        $("#medix_results_container").addClass("d-none");
    }

    function getPatients(firstname, lastname) {
```

- [ ] **Step 3: Add the `#btn_find_medix` click handler**

Find the existing `#btn_find` click handler (search for `$("#btn_find").click`) and add the new handler immediately after it:

```js
    $("#btn_find_medix").click(function () {
        const lastname = $("#lname").val().trim();

        if (lastname == "") {
            $("#medix_results_container").removeClass("d-none");
            $("#medix_patient_list").empty();
            $("#medix_search_status").removeClass("d-none").text("Lastname is required");
            return;
        }

        getMedixPatients(lastname);
    });
```

(Button disable/spinner is handled inside `getMedixPatients` itself, via `.finally()`, matching the existing `#btn_find` pattern of tying button state to the fetch lifecycle.)

- [ ] **Step 4: Commit**

```bash
git add resources/views/js/home.blade.php
git commit -m "Add MEDIX patient search and field-fill behavior"
```

---

## Task 5: Manual end-to-end verification

**Files:** none (browser walkthrough only — this codebase has no JS test harness)

- [ ] **Step 1: Verify empty-lastname guard**

Open `http://localhost/paperless_prescription`, log in, open the "Patient Management" modal (`#patient_modal`). Leave "Lastname" blank and click "SEARCH MEDIX".
Expected: inline message "Lastname is required" appears under the buttons, no network request fires (check browser Network tab).

- [ ] **Step 2: Verify a real MEDIX search**

Enter a lastname you expect to exist in MEDIX (e.g. `LAGOS`) in the Lastname field and click "SEARCH MEDIX".
Expected: results table populates with Hospital No / Name / Birthdate / Sex rows; a brief "Searching patient..." row shows first.

- [ ] **Step 3: Verify select fills the form**

Click the select icon on a result row.
Expected: `#hospital_id`, `#fname`, `#mname`, `#lname`, `#suffix`, `#birthdate`, `#sex` populate with the MEDIX record's values (cross-check the birthdate conversion, e.g. `05-29-1961` → `1961-05-29`, and sex `MALE`→`M`); the results table hides again; no page navigation or modal switch occurs.

- [ ] **Step 4: Verify the existing ADD / UPDATE save still works from here**

With the fields populated from Step 3, click "ADD / UPDATE" (the existing, unmodified button/handler).
Expected: same success/error toast as the existing HOMIS-search-then-save flow; re-opening the local search on the right side of the modal shows the patient in the Patient List.

- [ ] **Step 5: Verify failure handling**

Temporarily break the MEDIX credentials (e.g. wrong `MEDIX_CLIENT_SECRET` in `.env`, then `php artisan config:clear`), repeat Step 2.
Expected: inline status message shows a failure, no JS exception in console. Restore the correct credentials and `php artisan config:clear` afterward.
