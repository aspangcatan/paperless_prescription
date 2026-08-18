# Unified Local/MEDIX Patient Search Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the Patient Management modal's split "search HOMIS to fill a review form" / "search local patients to prescribe" flows with one panel — "Search Locally" and "Search MEDIX" buttons feeding a single results table tagged by source, where a Local row prescribes directly (unchanged) and a Medix row inserts into the local `patients` table then prescribes directly (new).

**Architecture:** The old "Search patient in HOMIS" card is hidden (`d-none`), not deleted — none of its buttons/JS are touched. The right-hand search panel gains a second button and takes the freed column width. `#patient_list` is driven by two JS arrays (`local_search_results`, `medix_search_results`) instead of DOM-cell-scraping, so a shared `openPrescriptionForPatient()` can be called with data directly from either source (keyed by `hospital_id`, same as the app's existing prescription flow) — this also fixes an index-shift bug the new "Source" column would otherwise cause in the old cell-scraping code. Backend gets one additive guard: `store()` rejects inserts where `hospital_id` already exists, when a `check_duplicate` flag is sent — only the new Medix-insert call sends it, so the legacy HOMIS/manual-entry upsert-by-`hospital_id` flow is unaffected.

**Tech Stack:** Laravel 8 (Eloquent, `Illuminate\Http\Request`), Blade, jQuery, moment.js, toastr, Bootstrap 5 — all already used elsewhere in these files, no new dependencies.

**Design doc:** [docs/superpowers/specs/2026-08-18-unified-patient-search-design.md](../specs/2026-08-18-unified-patient-search-design.md)

---

## Task 1: `store()` rejects duplicate hospital_id on request (TDD)

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\app\Http\Controllers\PatientController.php:184-207`
- Create: `c:\wamp64\projects\paperless_prescription\tests\Unit\PatientControllerTest.php`

**Note:** This test suite has no isolated test database — `phpunit.xml`'s sqlite override is commented out, so tests run against the same MySQL database the running app uses (`paperless_prescription`, per `.env`). The tests below use a hospital ID that only a test would ever use (`TEST-STORE-DUPLICATE-0001`) and delete any row with that ID in both `setUp()` and `tearDown()`, so they're safe to run repeatedly and never leave data behind — but do not reuse this pattern with a real-looking hospital ID.

- [ ] **Step 1: Write the failing tests**

Create `c:\wamp64\projects\paperless_prescription\tests\Unit\PatientControllerTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Http\Controllers\PatientController;
use App\Models\Patient;
use Illuminate\Http\Request;
use Tests\TestCase;

class PatientControllerTest extends TestCase
{
    private $test_hospital_id = 'TEST-STORE-DUPLICATE-0001';

    protected function setUp(): void
    {
        parent::setUp();
        Patient::where('hospital_id', $this->test_hospital_id)->delete();
    }

    protected function tearDown(): void
    {
        Patient::where('hospital_id', $this->test_hospital_id)->delete();
        parent::tearDown();
    }

    private function makeTestPatient($lname = 'DELA CRUZ')
    {
        $patient = new Patient();
        $patient->hospital_id = $this->test_hospital_id;
        $patient->fname = 'JUAN';
        $patient->mname = 'SANTOS';
        $patient->lname = $lname;
        $patient->suffix = '';
        $patient->birthdate = '1990-01-01';
        $patient->sex = 'M';
        $patient->save();

        return $patient;
    }

    public function testStoreRejectsDuplicateHospitalIdWhenCheckDuplicateIsSet()
    {
        $this->makeTestPatient('DELA CRUZ');

        $request = Request::create('/insert_patient', 'POST', [
            'hospital_id' => $this->test_hospital_id,
            'fname' => 'JUAN',
            'mname' => 'SANTOS',
            'lname' => 'DELA CRUZ UPDATED',
            'suffix' => '',
            'birthdate' => '1990-01-01',
            'sex' => 'M',
            'check_duplicate' => true,
        ]);

        $response = (new PatientController())->store($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
        $this->assertStringContainsString('Duplicate entry', $data['message']);
        $this->assertEquals('DELA CRUZ', Patient::where('hospital_id', $this->test_hospital_id)->first()->lname);
    }

    public function testStoreStillUpdatesDuplicateWhenCheckDuplicateIsNotSet()
    {
        $this->makeTestPatient('DELA CRUZ');

        $request = Request::create('/insert_patient', 'POST', [
            'hospital_id' => $this->test_hospital_id,
            'fname' => 'JUAN',
            'mname' => 'SANTOS',
            'lname' => 'DELA CRUZ UPDATED',
            'suffix' => '',
            'birthdate' => '1990-01-01',
            'sex' => 'M',
        ]);

        $response = (new PatientController())->store($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);
        $this->assertEquals('DELA CRUZ UPDATED', Patient::where('hospital_id', $this->test_hospital_id)->first()->lname);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd c:\wamp64\projects\paperless_prescription && php artisan test --filter=PatientControllerTest`
Expected: FAIL — `testStoreRejectsDuplicateHospitalIdWhenCheckDuplicateIsSet` fails because `store()` currently has no duplicate check and returns code 200 with the lname overwritten.

- [ ] **Step 3: Update `store()`**

In `c:\wamp64\projects\paperless_prescription\app\Http\Controllers\PatientController.php`, change:

```php
    public function store(Request $request)
    {
        try {
            $message = "Patient information updated";
            $patient = Patient::where('hospital_id', $request->hospital_id)->first();
            if (!$patient) {
                $message = "New patient added";
                $patient = new Patient();
                $patient->hospital_id = $request->hospital_id;
            }

            $patient->fname = $request->fname;
            $patient->mname = $request->mname;
            $patient->lname = $request->lname;
            $patient->suffix = $request->suffix;
            $patient->birthdate = $request->birthdate;
            $patient->sex = $request->sex;
            $patient->save();
            return response(['code' => 200, 'message' => $message]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'insertPatient', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
```

to:

```php
    public function store(Request $request)
    {
        try {
            $existing = Patient::where('hospital_id', $request->hospital_id)->first();

            if ($existing && $request->boolean('check_duplicate')) {
                return response(['code' => 500, 'message' => 'Duplicate entry: a patient with this Hospital ID already exists']);
            }

            $message = "Patient information updated";
            $patient = $existing;
            if (!$patient) {
                $message = "New patient added";
                $patient = new Patient();
                $patient->hospital_id = $request->hospital_id;
            }

            $patient->fname = $request->fname;
            $patient->mname = $request->mname;
            $patient->lname = $request->lname;
            $patient->suffix = $request->suffix;
            $patient->birthdate = $request->birthdate;
            $patient->sex = $request->sex;
            $patient->save();
            return response(['code' => 200, 'message' => $message]);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'insertPatient', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd c:\wamp64\projects\paperless_prescription && php artisan test --filter=PatientControllerTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PatientController.php tests/Unit/PatientControllerTest.php
git commit -m "Reject duplicate hospital_id in store() when requested"
```

---

## Task 2: `searchMedixPatient` accepts optional `firstname` (TDD)

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\app\Http\Controllers\PatientController.php:64-66`
- Modify: `c:\wamp64\projects\paperless_prescription\tests\Unit\PatientControllerMedixTest.php`

- [ ] **Step 1: Write the failing tests**

In `c:\wamp64\projects\paperless_prescription\tests\Unit\PatientControllerMedixTest.php`, add these two test methods just before the final closing `}` of the class:

```php
    public function testSearchMedixPatientIncludesFirstNameWhenProvided()
    {
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => Http::response([], 200),
        ]);

        $request = Request::create('/patient/medix/search', 'GET', ['lastname' => 'LAGOS', 'firstname' => 'ROLANDO']);
        (new PatientController())->searchMedixPatient($request);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'GetPatientInformation')
                && $req['LastName'] === 'LAGOS'
                && $req['FirstName'] === 'ROLANDO';
        });
    }

    public function testSearchMedixPatientOmitsFirstNameWhenNotProvided()
    {
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => Http::response([], 200),
        ]);

        $request = Request::create('/patient/medix/search', 'GET', ['lastname' => 'LAGOS']);
        (new PatientController())->searchMedixPatient($request);

        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'GetPatientInformation')
                && $req['LastName'] === 'LAGOS'
                && !isset($req['FirstName']);
        });
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd c:\wamp64\projects\paperless_prescription && php artisan test --filter=PatientControllerMedixTest`
Expected: `testSearchMedixPatientIncludesFirstNameWhenProvided` FAILs (no `FirstName` sent today); `testSearchMedixPatientOmitsFirstNameWhenNotProvided` passes already (nothing new to omit) — that's fine, it becomes a regression guard.

- [ ] **Step 3: Add `FirstName` passthrough**

In `c:\wamp64\projects\paperless_prescription\app\Http\Controllers\PatientController.php`, change:

```php
            // See the TLS comment in getMedixToken() above.
            $response = Http::withOptions(['verify' => false])->withToken($token)->get(config('services.medix.base_url') . '/GetPatientInformation', [
                'LastName' => $request->lastname,
            ]);
```

to:

```php
            $query = ['LastName' => $request->lastname];
            if (!empty($request->firstname)) {
                $query['FirstName'] = $request->firstname;
            }

            // See the TLS comment in getMedixToken() above.
            $response = Http::withOptions(['verify' => false])->withToken($token)->get(config('services.medix.base_url') . '/GetPatientInformation', $query);
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd c:\wamp64\projects\paperless_prescription && php artisan test --filter=PatientControllerMedixTest`
Expected: PASS (6 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PatientController.php tests/Unit/PatientControllerMedixTest.php
git commit -m "Add optional FirstName passthrough to MEDIX patient search"
```

---

## Task 3: Modal markup — hide HOMIS card, add Search Locally/MEDIX buttons, Source column

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\resources\views\modals\prescription.blade.php`

- [ ] **Step 1: Hide the "Search patient in HOMIS" card**

Change:

```blade
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="mt-3 fs-5 fw-bold">Search patient in HOMIS</div>
```

to:

```blade
                <div class="row">
                    <div class="col-md-4 d-none">
                        <div class="card">
                            <div class="card-body">
                                <div class="mt-3 fs-5 fw-bold">Search patient in HOMIS</div>
```

- [ ] **Step 2: Widen the right panel and add the second search button**

Change:

```blade
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-floating">
                                    <input class="form-control mb-3" placeholder="Search patient &#xF52A"
                                           id="search_firstname"/>
                                    <label>Search firstname</label>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-floating">
                                    <input class="form-control mb-3" placeholder="Search patient &#xF52A"
                                           id="search_lastname"/>
                                    <label>Search lastname</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-lg btn-success" id="btn_search_local">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
```

to:

```blade
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input class="form-control mb-3" placeholder="Search patient &#xF52A"
                                           id="search_firstname"/>
                                    <label>Search firstname</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input class="form-control mb-3" placeholder="Search patient &#xF52A"
                                           id="search_lastname"/>
                                    <label>Search lastname</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <button class="btn form-control btn-success" id="btn_search_local">
                                    <i class="bi bi-search"></i> Search Locally
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn form-control btn-info text-white" id="btn_search_medix">
                                    <i class="bi bi-search"></i> Search MEDIX
                                </button>
                            </div>
                        </div>
```

- [ ] **Step 3: Add the "Source" column to `#patient_list`**

Change:

```blade
                                <div class="table-responsive">
                                    <table class="table table-hover text-center">
                                        <thead>
                                        <tr>
                                            <th scope="col">Hospital ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Birthdate</th>
                                            <th scope="col">Sex</th>
                                            <th scope="col" style="width: 4%"></th>
                                        </tr>
                                        </thead>
                                        <tbody id="patient_list">
                                        <tr>
                                            <td colspan="5">No record found</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
```

to:

```blade
                                <div class="table-responsive">
                                    <table class="table table-hover text-center">
                                        <thead>
                                        <tr>
                                            <th scope="col">Source</th>
                                            <th scope="col">Hospital ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Birthdate</th>
                                            <th scope="col">Sex</th>
                                            <th scope="col" style="width: 4%"></th>
                                        </tr>
                                        </thead>
                                        <tbody id="patient_list">
                                        <tr class="no_record_row">
                                            <td colspan="6">No record found</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/modals/prescription.blade.php
git commit -m "Hide HOMIS search card, add Search Locally/MEDIX buttons and Source column"
```

---

## Task 4: Result arrays, row rendering, and search handlers

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\resources\views\js\home.blade.php`

- [ ] **Step 1: Add the two new result arrays**

Change:

```js
    let prescription_id = "";
    let page = 0;
    let is_loaded = false;
    let medix_results = [];
```

to:

```js
    let prescription_id = "";
    let page = 0;
    let is_loaded = false;
    let medix_results = [];
    let local_search_results = [];
    let medix_search_results = [];
```

- [ ] **Step 2: Add the `#btn_search_medix` click handler**

Change:

```js
        $("#btn_search_local").click(function () {
            const firstname = $("#search_firstname").val().trim();
            const lastname = $("#search_lastname").val().trim();

            if (firstname == "" && lastname == "") return;

            getPatients(firstname, lastname);
        });
```

to:

```js
        $("#btn_search_local").click(function () {
            const firstname = $("#search_firstname").val().trim();
            const lastname = $("#search_lastname").val().trim();

            if (firstname == "" && lastname == "") return;

            getPatients(firstname, lastname);
        });

        $("#btn_search_medix").click(function () {
            const firstname = $("#search_firstname").val().trim();
            const lastname = $("#search_lastname").val().trim();

            if (lastname == "") {
                toastr.error("Lastname is required", "MEDIX Search");
                return;
            }

            searchMedixPatients(firstname, lastname);
        });
```

- [ ] **Step 3: Replace `getPatients()` with the row-builder, renderer, local search, and MEDIX search functions**

Change:

```js
    function getPatients(firstname, lastname) {
        $("#patient_list").empty();
        $("#patient_list").append("<tr><td colspan='5'>Searching patient...</td></tr>");
        fetch("{{ url('patient/get') }}?firstname=" + firstname + "&lastname=" + lastname, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#patient_list").empty();
                if (data.results.length < 1) {
                    $("#patient_list").append("<tr><td colspan='5'>No record found</td></tr>");
                    return;
                }

                data.results.forEach(it => {
                    const name = it.lname + ", " + it.fname + " " + it.mname;
                    const tr = `
                        <tr id='patient_id_` + it.id + `'>
                            <td>` + it.hospital_id + `</td>
                            <td>` + name + `</td>
                            <td>` + moment(it.birthdate).format("MM/DD/YYYY") + `</td>
                            <td>` + it.sex + `</td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="makePrescription(` + it.id + `)">
                                    <i class="bi bi-prescription"></i>
                                </button>
                            </td>
                        </tr>`;

                    $("#patient_list").append(tr);
                })

            }).catch(err => {
            console.log(err);
            $("#patient_list").empty();
            $("#patient_list").append("<tr><td colspan='5'>Something went wrong</td></tr>");
        });
    }
```

to:

```js
    function buildPatientRow(source, it, index) {
        if (source === "local") {
            const name = it.lname + ", " + it.fname + " " + it.mname;
            const birthdate = moment(it.birthdate).format("MM/DD/YYYY");
            return `
                <tr id="patient_row_local_` + index + `" data-source="local">
                    <td><span class="badge bg-secondary">Local</span></td>
                    <td>` + it.hospital_id + `</td>
                    <td>` + name + `</td>
                    <td>` + birthdate + `</td>
                    <td>` + it.sex + `</td>
                    <td>
                        <button class="btn btn-sm btn-success btn-action" onclick="selectPatientRow('local', ` + index + `)">
                            <i class="bi bi-prescription"></i>
                        </button>
                    </td>
                </tr>`;
        }

        const name = it.lastName + ", " + it.firstName + " " + it.middleName;
        const birthdate = moment(it.birthDate, "MM-DD-YYYY").format("MM/DD/YYYY");
        return `
            <tr id="patient_row_medix_` + index + `" data-source="medix">
                <td><span class="badge bg-info text-white">Medix</span></td>
                <td>` + it.hospitalNo + `</td>
                <td>` + name + `</td>
                <td>` + birthdate + `</td>
                <td>` + it.gender + `</td>
                <td>
                    <button class="btn btn-sm btn-success btn-action" onclick="selectPatientRow('medix', ` + index + `)">
                        <i class="bi bi-cloud-download"></i>
                    </button>
                </td>
            </tr>`;
    }

    function renderPatientRows(source, results) {
        $("#patient_list tr[data-source='" + source + "']").remove();
        $("#patient_list tr.no_record_row").remove();

        results.forEach((it, index) => {
            $("#patient_list").append(buildPatientRow(source, it, index));
        });

        if ($("#patient_list tr[data-source]").length < 1) {
            $("#patient_list").append("<tr class='no_record_row'><td colspan='6'>No record found</td></tr>");
        }
    }

    function getPatients(firstname, lastname) {
        $("#patient_list tr[data-source='local']").remove();
        $("#patient_list tr.no_record_row").remove();
        $("#patient_list").append("<tr data-source='local'><td colspan='6'>Searching patient...</td></tr>");

        fetch("{{ url('patient/get') }}?firstname=" + firstname + "&lastname=" + lastname, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                local_search_results = data.results;
                renderPatientRows("local", local_search_results);
            }).catch(err => {
            console.log(err);
            local_search_results = [];
            renderPatientRows("local", local_search_results);
        });
    }

    function searchMedixPatients(firstname, lastname) {
        $("#patient_list tr[data-source='medix']").remove();
        $("#patient_list tr.no_record_row").remove();
        $("#patient_list").append("<tr data-source='medix'><td colspan='6'>Searching patient...</td></tr>");

        const btn = $("#btn_search_medix");
        const original_html = btn.html();
        btn.prop("disabled", true);
        btn.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`);

        let url = "{{ url('patient/medix/search') }}?lastname=" + encodeURIComponent(lastname);
        if (firstname !== "") {
            url += "&firstname=" + encodeURIComponent(firstname);
        }

        fetch(url, {method: "GET"})
            .then(res => res.json())
            .then(data => {
                if (data.code == 500) {
                    medix_search_results = [];
                    renderPatientRows("medix", medix_search_results);
                    toastr.error(data.message, "MEDIX Search");
                    return;
                }

                medix_search_results = data.results;
                renderPatientRows("medix", medix_search_results);
            }).catch(err => {
            console.log(err);
            medix_search_results = [];
            renderPatientRows("medix", medix_search_results);
            toastr.error("Something went wrong", "Ooops");
        }).finally(() => {
            btn.prop("disabled", false);
            btn.html(original_html);
        });
    }
```

- [ ] **Step 4: Commit**

```bash
git add resources/views/js/home.blade.php
git commit -m "Add merged local/MEDIX search rendering to patient list"
```

---

## Task 5: Row selection — prescribe directly (Local) or insert-then-prescribe (Medix)

**Files:**
- Modify: `c:\wamp64\projects\paperless_prescription\resources\views\js\home.blade.php`

- [ ] **Step 1: Replace `makePrescription(id)` with `openPrescriptionForPatient`, `selectPatientRow`, and `insertMedixPatient`**

`makePrescription(id)` is only ever called from the row markup replaced in Task 4 (`onclick="makePrescription(...)"` no longer exists after that task), so it has no remaining callers. Change:

```js
    function makePrescription(id) {
        prescription_id = "";
        const hospital_id = $("#patient_id_" + id + " td:eq(0)").text();
        const name = $("#patient_id_" + id + " td:eq(1)").text();
        const birthdate = $("#patient_id_" + id + " td:eq(2)").text();
        const sex = $("#patient_id_" + id + " td:eq(3)").text();

        $("#concurrent_table").removeClass("table-hover");
        $("#concurrent_table tbody tr").removeClass("table-hover");
        $("#form_hospital_id").text(hospital_id);
        $("#form_patient").text(name);
        $("#form_birthdate").text(birthdate);

        $("#form_age").text(moment().diff(moment(birthdate, "MM/DD/YYYY"), 'years'));
        $("#form_age").text(formatFullAge(birthdate, moment()));

        $("#form_sex").text(sex);
        $("#form_station").val("");
        $("#form_ward").val("");
        $("#form_bed_no").val("");
        $("#form_prescription_type").val("").change();
        $("#form_remarks").val("");
        $("#checkbox_urgent").prop("checked", false);
        $("#checkbox_urgent").prop("disabled", false);
        $("#medication_list").empty();

        $("#prescription_modal input").prop("disabled", false);
        $("#prescription_modal input[type='checkbox']").prop("disabled", false);
        $("#prescription_modal input[type='radio']").prop("disabled", false);
        $("#prescription_modal select").prop("disabled", false);
        $("#prescription_modal textarea").prop("disabled", false);

        $("#btn_view_cart").removeClass("d-none");
        $("#btn_add_microbiological").removeClass("d-none");
        $("#btn_add_concurrent").removeClass("d-none");
        $("#btn_submit").removeClass("d-none");
        $("#form_remarks").removeClass("d-none");
        $("#transaction_container").addClass("d-none");
        $(".cancellation_container").addClass("d-none");

        patient_modal.toggle();
        prescription_modal.toggle();
        $("#btn_download").prop("disabled", false);
        getConcurrentTherapy(hospital_id);
    }
```

to:

```js
    function openPrescriptionForPatient(hospital_id, name, birthdate, sex) {
        prescription_id = "";

        $("#concurrent_table").removeClass("table-hover");
        $("#concurrent_table tbody tr").removeClass("table-hover");
        $("#form_hospital_id").text(hospital_id);
        $("#form_patient").text(name);
        $("#form_birthdate").text(birthdate);

        $("#form_age").text(moment().diff(moment(birthdate, "MM/DD/YYYY"), 'years'));
        $("#form_age").text(formatFullAge(birthdate, moment()));

        $("#form_sex").text(sex);
        $("#form_station").val("");
        $("#form_ward").val("");
        $("#form_bed_no").val("");
        $("#form_prescription_type").val("").change();
        $("#form_remarks").val("");
        $("#checkbox_urgent").prop("checked", false);
        $("#checkbox_urgent").prop("disabled", false);
        $("#medication_list").empty();

        $("#prescription_modal input").prop("disabled", false);
        $("#prescription_modal input[type='checkbox']").prop("disabled", false);
        $("#prescription_modal input[type='radio']").prop("disabled", false);
        $("#prescription_modal select").prop("disabled", false);
        $("#prescription_modal textarea").prop("disabled", false);

        $("#btn_view_cart").removeClass("d-none");
        $("#btn_add_microbiological").removeClass("d-none");
        $("#btn_add_concurrent").removeClass("d-none");
        $("#btn_submit").removeClass("d-none");
        $("#form_remarks").removeClass("d-none");
        $("#transaction_container").addClass("d-none");
        $(".cancellation_container").addClass("d-none");

        patient_modal.toggle();
        prescription_modal.toggle();
        $("#btn_download").prop("disabled", false);
        getConcurrentTherapy(hospital_id);
    }

    function selectPatientRow(source, index) {
        if (source === "local") {
            const it = local_search_results[index];
            if (!it) return;

            const name = it.lname + ", " + it.fname + " " + it.mname;
            const birthdate = moment(it.birthdate).format("MM/DD/YYYY");
            openPrescriptionForPatient(it.hospital_id, name, birthdate, it.sex);
            return;
        }

        if (source === "medix") {
            insertMedixPatient(index);
        }
    }

    function insertMedixPatient(index) {
        const it = medix_search_results[index];
        if (!it) return;

        const params = {
            hospital_id: it.hospitalNo,
            fname: (it.firstName || "").toUpperCase(),
            mname: (it.middleName || "").toUpperCase(),
            lname: (it.lastName || "").toUpperCase(),
            suffix: (it.suffixName || "").toUpperCase(),
            birthdate: moment(it.birthDate, "MM-DD-YYYY").format("YYYY-MM-DD"),
            sex: (it.gender || "").charAt(0),
            check_duplicate: true
        };

        const btn = $("#patient_row_medix_" + index + " .btn-action");
        const original_html = btn.html();
        btn.prop("disabled", true);
        btn.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`);

        fetch('{{ route('insert_patient') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify(params)
        })
            .then(res => res.json())
            .then(data => {
                if (data.code == 500) {
                    toastr.error(data.message, "Something went wrong");
                    btn.prop("disabled", false);
                    btn.html(original_html);
                    return;
                }

                const name = params.lname + ", " + params.fname + " " + params.mname;
                const birthdate_display = moment(params.birthdate).format("MM/DD/YYYY");
                openPrescriptionForPatient(params.hospital_id, name, birthdate_display, params.sex);
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
            btn.prop("disabled", false);
            btn.html(original_html);
        });
    }
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/js/home.blade.php
git commit -m "Replace DOM-scraping makePrescription with data-driven row selection"
```

---

## Task 6: Manual end-to-end verification

**Files:** none (browser walkthrough only — this codebase has no JS test harness)

- [ ] **Step 1: Verify the HOMIS card is gone from view**

Open the app, log in, open "Patient Management" (`#patient_modal`).
Expected: no "Search patient in HOMIS" card visible; the search panel (firstname/lastname inputs, "Search Locally"/"Search MEDIX" buttons, Patient List table) fills the full modal width.

- [ ] **Step 2: Verify Local search**

Enter a firstname or lastname you know exists locally (e.g. a name from an existing patient) and click "Search Locally".
Expected: matching rows appear with a gray "Local" badge in the first column; Hospital ID/Name/Birthdate/Sex populate correctly.

- [ ] **Step 3: Verify Local row still prescribes directly**

Click the prescription icon on a Local row.
Expected: same behavior as before this change — the patient modal closes, the prescription modal opens pre-filled with that patient's info, and concurrent therapy loads (no console errors).

- [ ] **Step 4: Verify MEDIX search and merge-without-duplicating**

With Local results still showing from Step 2, enter a lastname you expect to exist in MEDIX and click "Search MEDIX".
Expected: a "Searching patient..." row appears briefly under a spinner on the button, then Medix-badged rows (blue "Medix" badge) appear *alongside* the existing Local rows — Local rows are not cleared. Re-clicking "Search MEDIX" with the same lastname refreshes only the Medix rows, not duplicating them, and leaves Local rows untouched.

- [ ] **Step 5: Verify Medix row insert-then-prescribe**

Click the cloud-download icon on a Medix row for a patient you know is **not** already in the local `patients` table.
Expected: brief spinner on that row's button, then the patient modal closes and the prescription modal opens pre-filled with that patient's info (same as Step 3), with no intermediate review screen.

- [ ] **Step 6: Verify duplicate rejection**

Search MEDIX again for the same patient from Step 5 (now already imported) and click its row's action button again.
Expected: a toastr error containing "Duplicate entry", the prescription modal does **not** open, and the patient modal stays as-is.

- [ ] **Step 7: Verify the hidden HOMIS card still works if manually revealed (regression check only)**

In the browser console, run `$('#patient_modal .col-md-4').removeClass('d-none')` to temporarily reveal the old card, then use "SEARCH" (HOMIS) and "SEARCH MEDIX" (old button) as before.
Expected: both still function exactly as they did prior to this change — confirms Task 3/4/5 didn't break the hidden code. Reload the page afterward to restore the hidden state (no code change needed — the `d-none` class is back on page load).
