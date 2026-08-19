# Per-Medicine ICC Review Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a doctor put multiple medicines on one prescription, and let ICC approve some while rejecting others in a single review — approved medicines proceed to Pharmacy immediately, rejected ones return to the doctor for correction, without touching the medicines that already passed. Type `6` (the multi-prescription cancellation flow) and Pharmacy/Nurse stay whole-prescription, unchanged.

**Architecture:** `prescribed_medicines` gains a `status` column (0=pending, 1=approved, 2=rejected) and `prescription_logs` gains a nullable `prescribed_medicine_id`, so a rejection can carry its own remarks without disturbing the existing whole-prescription log rows Pharmacy/Nurse still write. A new `PrescriptionController::decidePrescriptionMedications` endpoint replaces ICC's use of `/approve_prescription`/`/reject_prescription` for everything except type `6`, writing per-medicine status + logs and forwarding the prescription to Pharmacy and/or back to the doctor as needed — both can happen from the same submission. `getPrescriptionDetails` gains an optional `?status=` filter so Pharmacy only ever sees approved medicines and a re-reviewing ICC only sees pending ones, while the doctor's own Returned view keeps seeing everything.

**Tech Stack:** Laravel 8 (Eloquent, raw `DB::table` joins — this codebase has no model relationships), Blade, jQuery, moment.js, toastr, Bootstrap 5 modals — all already used elsewhere in these files.

**Design doc:** [docs/superpowers/specs/2026-08-19-per-medicine-icc-review-design.md](../specs/2026-08-19-per-medicine-icc-review-design.md)

---

## Task 1: Schema — `prescribed_medicines.status` and `prescription_logs.prescribed_medicine_id`

**Files:**
- Create: `database/migrations/2026_08_19_000001_add_status_to_prescribed_medicines_table.php`
- Create: `database/migrations/2026_08_19_000002_add_prescribed_medicine_id_to_prescription_logs_table.php`

- [ ] **Step 1: Create the `prescribed_medicines.status` migration**

Create `database/migrations/2026_08_19_000001_add_status_to_prescribed_medicines_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToPrescribedMedicinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescribed_medicines', function (Blueprint $table) {
            //0 = pending, 1 = approved, 2 = rejected
            $table->tinyInteger('status')->default(0)->after('quantity_returned');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prescribed_medicines', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
```

- [ ] **Step 2: Create the `prescription_logs.prescribed_medicine_id` migration**

Create `database/migrations/2026_08_19_000002_add_prescribed_medicine_id_to_prescription_logs_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrescribedMedicineIdToPrescriptionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prescription_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('prescribed_medicine_id')->nullable()->after('prescription_id');
            $table->foreign('prescribed_medicine_id')->references('id')->on('prescribed_medicines')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prescription_logs', function (Blueprint $table) {
            $table->dropForeign(['prescribed_medicine_id']);
            $table->dropColumn('prescribed_medicine_id');
        });
    }
}
```

- [ ] **Step 3: Run the migrations**

Run: `cd c:\wamp64\projects\paperless_prescription && php artisan migrate`
Expected: both migrations listed as `Migrating` then `Migrated` with no errors.

- [ ] **Step 4: Verify the columns exist**

Run: `php artisan tinker --execute="echo Schema::hasColumn('prescribed_medicines', 'status') ? 'OK' : 'MISSING'; echo Schema::hasColumn('prescription_logs', 'prescribed_medicine_id') ? 'OK' : 'MISSING';"`
Expected: `OKOK`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_08_19_000001_add_status_to_prescribed_medicines_table.php database/migrations/2026_08_19_000002_add_prescribed_medicine_id_to_prescription_logs_table.php
git commit -m "Add per-medicine status and log linkage columns"
```

---

## Task 2: `getPrescriptionDetails` accepts an optional `status` filter (TDD)

**Files:**
- Modify: `app\Http\Controllers\PrescriptionController.php:113-134`
- Create: `tests\Unit\PrescriptionControllerTest.php`

**Note:** Like the other test files in this suite, this runs against the real (non-isolated) MySQL database — `phpunit.xml`'s sqlite override is commented out. Tests use a dedicated test-only `hospital_id` and rely on `prescriptions`' cascade-delete foreign keys (`prescribed_medicines.prescription_id` and `prescription_logs.prescription_id` both `onDelete('cascade')`) so deleting the test `Prescription` row in `tearDown()` cleans up everything created under it.

- [ ] **Step 1: Write the failing tests**

Create `tests\Unit\PrescriptionControllerTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Http\Controllers\PrescriptionController;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Tests\TestCase;

class PrescriptionControllerTest extends TestCase
{
    private $test_hospital_id = 'TEST-ICC-DECIDE-0001';

    protected function tearDown(): void
    {
        Prescription::where('hospital_id', $this->test_hospital_id)->delete();
        parent::tearDown();
    }

    private function makeTestPrescription($type = '2')
    {
        $prescription = new Prescription();
        $prescription->hospital_id = $this->test_hospital_id;
        $prescription->physician_id = 1;
        $prescription->attending_physician = 'DR. TEST';
        $prescription->station = 'TEST STATION';
        $prescription->ward = 'TEST WARD';
        $prescription->bed_no = '1';
        $prescription->urgency = 'Routine';
        $prescription->type = $type;
        $prescription->save();

        return $prescription;
    }

    private function makeTestMedicine($prescription_id, $status = 0)
    {
        $medicine = new PrescribedMedicine();
        $medicine->prescription_id = $prescription_id;
        $medicine->description = 'TEST MEDICINE';
        $medicine->unit = 'TABLET';
        $medicine->dosage_strength = '500';
        $medicine->dosage_unit = 'MG';
        $medicine->frequency = 'BID';
        $medicine->status = $status;
        $medicine->save();

        return $medicine;
    }

    public function testGetPrescriptionDetailsReturnsAllMedicinesWhenStatusNotProvided()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestMedicine($prescription->id, 0);
        $this->makeTestMedicine($prescription->id, 1);
        $this->makeTestMedicine($prescription->id, 2);

        $response = (new PrescriptionController())->getPrescriptionDetails(Request::create('/prescription/get/' . $prescription->id, 'GET'), $prescription->id);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);
        $this->assertCount(3, $data['results']);
    }

    public function testGetPrescriptionDetailsFiltersByStatusWhenProvided()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestMedicine($prescription->id, 0);
        $this->makeTestMedicine($prescription->id, 1);
        $this->makeTestMedicine($prescription->id, 2);

        $response = (new PrescriptionController())->getPrescriptionDetails(Request::create('/prescription/get/' . $prescription->id, 'GET', ['status' => 1]), $prescription->id);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);
        $this->assertCount(1, $data['results']);
        $this->assertEquals(1, $data['results'][0]['status']);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PrescriptionControllerTest`
Expected: both FAIL — `getPrescriptionDetails` currently takes only `$id`, not `Request $request` too, so this won't even compile/call correctly (an `ArgumentCountError` or similar).

- [ ] **Step 3: Add the optional filter**

In `app\Http\Controllers\PrescriptionController.php`, change:

```php
    public function getPrescriptionDetails($id)
    {
        $ams = [];
        try {
            $prescription = Prescription::find($id);
            if ($prescription->type == '2') {
                $indication = IndicationUse::where('prescription_id', $id)->first();
                $ams['indication_use'] = $indication;
            }

            if ($prescription->type != '4') {
                $microbiologicals = MicrobiologicalResult::where('prescription_id', $id)->get();
                $ams['microbiologicals'] = $microbiologicals;
            }

            #GET MEDICATIONS
            $medications = PrescribedMedicine::where('prescription_id', $id)->get();
            return response(['code' => 200, 'results' => $medications, 'ams' => $ams]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
```

to:

```php
    public function getPrescriptionDetails(Request $request, $id)
    {
        $ams = [];
        try {
            $prescription = Prescription::find($id);
            if ($prescription->type == '2') {
                $indication = IndicationUse::where('prescription_id', $id)->first();
                $ams['indication_use'] = $indication;
            }

            if ($prescription->type != '4') {
                $microbiologicals = MicrobiologicalResult::where('prescription_id', $id)->get();
                $ams['microbiologicals'] = $microbiologicals;
            }

            #GET MEDICATIONS
            $medications_query = PrescribedMedicine::where('prescription_id', $id);
            if ($request->status !== null && $request->status !== '') {
                $medications_query->where('status', $request->status);
            }
            $medications = $medications_query->get();
            return response(['code' => 200, 'results' => $medications, 'ams' => $ams]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
```

- [ ] **Step 4: Update the route to inject `Request`**

In `routes/web.php`, change:

```php
        Route::get('/prescription/get/{id}', [\App\Http\Controllers\PrescriptionController::class, 'getPrescriptionDetails']);
```

to (no actual change needed — Laravel injects `Request` automatically as the first typed parameter regardless of route binding order; this step just confirms it):

Run: `php artisan route:list | grep "prescription/get/{id}"` — should still show the same route bound to `getPrescriptionDetails`. No file edit required here; Laravel resolves the `Request $request` parameter by type-hint, and `{id}` by route-parameter name, independent of declaration order.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=PrescriptionControllerTest`
Expected: PASS (2 tests)

- [ ] **Step 6: Run the full suite to confirm no regressions**

Run: `php artisan test`
Expected: same pass count as before plus these 2, only the pre-existing unrelated `Tests\Feature\ExampleTest` failure.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PrescriptionController.php tests/Unit/PrescriptionControllerTest.php
git commit -m "Add optional status filter to getPrescriptionDetails"
```

---

## Task 3: `decidePrescriptionMedications` — the per-medicine ICC decision endpoint (TDD)

**Files:**
- Modify: `app\Http\Controllers\PrescriptionController.php` (add new method)
- Modify: `routes\web.php:113`
- Modify: `tests\Unit\PrescriptionControllerTest.php`

- [ ] **Step 1: Write the failing tests**

In `tests\Unit\PrescriptionControllerTest.php`, add `use` statements and test methods. Change:

```php
use App\Http\Controllers\PrescriptionController;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Tests\TestCase;
```

to:

```php
use App\Http\Controllers\PrescriptionController;
use App\Models\IccReview;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedPrescription;
use Illuminate\Http\Request;
use Tests\TestCase;
```

Then add these test methods at the end of the class, before the final closing `}`:

```php
    private function makeTestIccReview($prescription_id)
    {
        $icc = new IccReview();
        $icc->prescription_id = $prescription_id;
        $icc->save();

        return $icc;
    }

    public function testDecideRejectsWhenAMedicationHasNoDecisionOrRejectedHasNoRemarks()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestIccReview($prescription->id);
        $medicine = $this->makeTestMedicine($prescription->id);

        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'days_approved' => 7,
            'medications' => [
                ['id' => $medicine->id, 'decision' => 'rejected', 'remarks' => ''],
            ],
        ]);

        $response = (new PrescriptionController())->decidePrescriptionMedications($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
        $this->assertEquals(0, PrescribedMedicine::find($medicine->id)->status);
    }

    public function testDecideRequiresDaysApprovedWhenAnyMedicationIsApproved()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestIccReview($prescription->id);
        $medicine = $this->makeTestMedicine($prescription->id);

        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'medications' => [
                ['id' => $medicine->id, 'decision' => 'approved', 'remarks' => ''],
            ],
        ]);

        $response = (new PrescriptionController())->decidePrescriptionMedications($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
        $this->assertEquals(0, PrescribedMedicine::find($medicine->id)->status);
    }

    public function testDecideSplitsApprovedAndRejectedMedicinesInOneSubmission()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestIccReview($prescription->id);
        $approved_medicine = $this->makeTestMedicine($prescription->id);
        $rejected_medicine = $this->makeTestMedicine($prescription->id);

        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'days_approved' => 7,
            'outcome' => 'Wrong indication',
            'medications' => [
                ['id' => $approved_medicine->id, 'decision' => 'approved', 'remarks' => ''],
                ['id' => $rejected_medicine->id, 'decision' => 'rejected', 'remarks' => 'Dosage exceeds weight-based max'],
            ],
        ]);

        $response = (new PrescriptionController())->decidePrescriptionMedications($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);
        $this->assertEquals(1, PrescribedMedicine::find($approved_medicine->id)->status);
        $this->assertEquals(2, PrescribedMedicine::find($rejected_medicine->id)->status);

        $this->assertNotNull(PharmacyReview::where('prescription_id', $prescription->id)->first());
        $this->assertNotNull(ReturnedPrescription::where('prescription_id', $prescription->id)->first());
        $this->assertNull(IccReview::where('prescription_id', $prescription->id)->first());

        $this->assertEquals('Wrong indication', Prescription::find($prescription->id)->outcome);

        $approved_log = PrescriptionLog::where('prescribed_medicine_id', $approved_medicine->id)->first();
        $rejected_log = PrescriptionLog::where('prescribed_medicine_id', $rejected_medicine->id)->first();
        $this->assertNotNull($approved_log);
        $this->assertNotNull($rejected_log);
        $this->assertStringContainsString('Dosage exceeds weight-based max', $rejected_log->remarks);
    }

    public function testDecideDoesNotCreatePharmacyReviewWhenAllMedicinesRejected()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestIccReview($prescription->id);
        $medicine = $this->makeTestMedicine($prescription->id);

        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'medications' => [
                ['id' => $medicine->id, 'decision' => 'rejected', 'remarks' => 'Not indicated'],
            ],
        ]);

        (new PrescriptionController())->decidePrescriptionMedications($request);

        $this->assertNull(PharmacyReview::where('prescription_id', $prescription->id)->first());
        $this->assertNotNull(ReturnedPrescription::where('prescription_id', $prescription->id)->first());
    }

    public function testDecideDoesNotCreateReturnedPrescriptionWhenAllMedicinesApproved()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestIccReview($prescription->id);
        $medicine = $this->makeTestMedicine($prescription->id);

        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'days_approved' => 7,
            'medications' => [
                ['id' => $medicine->id, 'decision' => 'approved', 'remarks' => ''],
            ],
        ]);

        (new PrescriptionController())->decidePrescriptionMedications($request);

        $this->assertNotNull(PharmacyReview::where('prescription_id', $prescription->id)->first());
        $this->assertNull(ReturnedPrescription::where('prescription_id', $prescription->id)->first());
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PrescriptionControllerTest`
Expected: the 5 new tests FAIL (`decidePrescriptionMedications` doesn't exist yet — `Error: Call to undefined method`). The 2 tests from Task 2 still pass.

- [ ] **Step 3: Add `decidePrescriptionMedications` to `PrescriptionController`**

In `app\Http\Controllers\PrescriptionController.php`, add this method immediately after `rejectPrescription` (i.e. right before `deletePrescription`):

```php
    public function decidePrescriptionMedications(Request $request)
    {
        try {
            $prescription_review = IccReview::where('prescription_id', $request->id)->first();
            if (!$prescription_review) {
                return response(['code' => 500, 'message' => 'Prescription dont exists, kindly refresh']);
            }

            $medications = $request->medications;
            if (empty($medications)) {
                return response(['code' => 500, 'message' => 'No medications submitted']);
            }

            $has_approved = false;
            $has_rejected = false;
            foreach ($medications as $medication) {
                if (!in_array($medication['decision'], ['approved', 'rejected'])) {
                    return response(['code' => 500, 'message' => 'Invalid decision for a medication']);
                }
                if ($medication['decision'] == 'rejected' && empty($medication['remarks'])) {
                    return response(['code' => 500, 'message' => 'Remarks are required for a rejected medication']);
                }
                if ($medication['decision'] == 'approved') {
                    $has_approved = true;
                } else {
                    $has_rejected = true;
                }
            }

            if ($has_approved && empty($request->days_approved)) {
                return response(['code' => 500, 'message' => 'Please specify number of days approved']);
            }

            $date_approved = Carbon::now();

            #CHECK IF TYPE IS INTERVENTION FORM
            #UPDATE SELECTED AMS STOP DATE INTO TODAYS DATE (ONCE PER SUBMISSION, MATCHING EXISTING BEHAVIOR)
            if ($has_approved && ($request->type == "4" || $request->type == "7")) {
                $get_selected_concurrent = ConcurrentTherapy::where("for_switch", $request->id)->first();
                if (!$get_selected_concurrent) {
                    return response(['code' => 500, 'message' => 'You did not specify the antibiotic you wish to be switched']);
                }
                $get_selected_concurrent->stop_date = $date_approved;
                $get_selected_concurrent->save();
            }

            $stop_date = null;
            if ($has_approved) {
                $date = Carbon::now();
                $date = $date->addDays($request->days_approved - 1);
                $stop_date = date('Y-m-d', strtotime($date));
            }

            foreach ($medications as $medication) {
                $prescribed_medicine = PrescribedMedicine::find($medication['id']);
                if (!$prescribed_medicine) {
                    continue;
                }

                if ($medication['decision'] == 'approved') {
                    $prescribed_medicine->status = 1;
                    $prescribed_medicine->save();

                    $concurrent = new ConcurrentTherapy;
                    $concurrent->prescription_id = $request->id;
                    $concurrent->hospital_id = $request->hospital_id;
                    $concurrent->concurrent_date = $date_approved;
                    $concurrent->antimicrobial = $prescribed_medicine->description;
                    $concurrent->dosing_regimen = $prescribed_medicine->dosage_strength . '' . $prescribed_medicine->dosage_unit . ' ' . $prescribed_medicine->frequency;
                    $concurrent->start_date = $date_approved;
                    $concurrent->stop_date = $stop_date;
                    $concurrent->days_approved = $request->days_approved;
                    $concurrent->approved_by = Auth::id();
                    $concurrent->date_approved = $date_approved;
                    $concurrent->save();

                    $released_to = 'PHARMACY';
                    $badge = "<span class='badge bg-success text-white'>Approved <i class='bi bi-hand-thumbs-up-fill'></i></span>";
                } else {
                    $prescribed_medicine->status = 2;
                    $prescribed_medicine->save();

                    $released_to = 'DOCTOR';
                    $badge = "<span class='badge bg-danger text-white'>Rejected <i class='bi bi-hand-thumbs-down-fill'></i></span>";
                }

                $logs = new PrescriptionLog;
                $logs->prescription_id = $request->id;
                $logs->prescribed_medicine_id = $prescribed_medicine->id;
                $logs->origin = "ICC";
                $logs->released_by = Auth::id();
                $logs->released_to = $released_to;
                $logs->remarks = $badge . "<span class='ms-2'>" . $medication['remarks'] . "</span>";
                $logs->save();
            }

            if ($has_approved) {
                $pharmacy = PharmacyReview::where('prescription_id', $request->id)->first();
                if (!$pharmacy) {
                    $pharmacy = new PharmacyReview;
                    $pharmacy->prescription_id = $request->id;
                    $pharmacy->save();
                }
            }

            if ($has_rejected) {
                if ($request->outcome != null) {
                    $selected_prescription = Prescription::find($request->id);
                    $selected_prescription->outcome = $request->outcome;
                    $selected_prescription->save();
                }

                $returned = ReturnedPrescription::where('prescription_id', $request->id)->first();
                if (!$returned) {
                    $returned = new ReturnedPrescription;
                    $returned->prescription_id = $request->id;
                    $returned->save();
                }
            }

            $prescription_review->forceDelete();
            return response(['code' => 200, 'message' => 'Prescription decisions saved']);
        } catch (\Exception $exception) {
            self::insertErrorLogs(Auth::id(), 'decidePrescriptionMedications', $request, $exception->getMessage());
            return response(['code' => 500, 'message' => "Something went wrong"]);
        }
    }
```

- [ ] **Step 4: Add the route**

In `routes\web.php`, change:

```php
        Route::post('/approve_prescription', [\App\Http\Controllers\PrescriptionController::class, 'approvePrescription'])->name('approve_prescription');
        Route::post('/reject_prescription', [\App\Http\Controllers\PrescriptionController::class, 'rejectPrescription'])->name('reject_prescription');
```

to:

```php
        Route::post('/approve_prescription', [\App\Http\Controllers\PrescriptionController::class, 'approvePrescription'])->name('approve_prescription');
        Route::post('/reject_prescription', [\App\Http\Controllers\PrescriptionController::class, 'rejectPrescription'])->name('reject_prescription');
        Route::post('/icc/decide_prescription', [\App\Http\Controllers\PrescriptionController::class, 'decidePrescriptionMedications'])->name('decide_prescription');
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=PrescriptionControllerTest`
Expected: PASS (7 tests)

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: same pre-existing unrelated `Tests\Feature\ExampleTest` failure only, all else passing.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PrescriptionController.php routes/web.php tests/Unit/PrescriptionControllerTest.php
git commit -m "Add per-medicine ICC decision endpoint"
```

---

## Task 4: Doctor resubmission only touches rejected medicines (TDD)

**Files:**
- Modify: `app\Http\Controllers\ReturnedPrescriptionController.php:194-203`
- Create: `tests\Unit\ReturnedPrescriptionControllerTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests\Unit\ReturnedPrescriptionControllerTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Http\Controllers\ReturnedPrescriptionController;
use App\Models\IccReview;
use App\Models\PatientHistory;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\ReturnedPrescription;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReturnedPrescriptionControllerTest extends TestCase
{
    private $test_hospital_id = 'TEST-RETURNED-UPDATE-0001';

    protected function tearDown(): void
    {
        Prescription::where('hospital_id', $this->test_hospital_id)->delete();
        parent::tearDown();
    }

    private function makeTestPrescription($type = '2')
    {
        $prescription = new Prescription();
        $prescription->hospital_id = $this->test_hospital_id;
        $prescription->physician_id = 1;
        $prescription->attending_physician = 'DR. TEST';
        $prescription->station = 'TEST STATION';
        $prescription->ward = 'TEST WARD';
        $prescription->bed_no = '1';
        $prescription->urgency = 'Routine';
        $prescription->type = $type;
        $prescription->save();

        $history = new PatientHistory();
        $history->prescription_id = $prescription->id;
        $history->hospital_id = $this->test_hospital_id;
        $history->name = 'TEST, PATIENT';
        $history->birthdate = '1990-01-01';
        $history->sex = 'M';
        $history->save();

        return $prescription;
    }

    private function makeTestMedicine($prescription_id, $status, $description = 'TEST MEDICINE')
    {
        $medicine = new PrescribedMedicine();
        $medicine->prescription_id = $prescription_id;
        $medicine->description = $description;
        $medicine->unit = 'TABLET';
        $medicine->dosage_strength = '500';
        $medicine->dosage_unit = 'MG';
        $medicine->frequency = 'BID';
        $medicine->status = $status;
        $medicine->save();

        return $medicine;
    }

    public function testUpdatePrescriptionOnlyRebuildsRejectedMedicines()
    {
        $prescription = $this->makeTestPrescription();
        $approved_medicine = $this->makeTestMedicine($prescription->id, 1, 'APPROVED MEDICINE');
        $this->makeTestMedicine($prescription->id, 2, 'OLD REJECTED MEDICINE');

        $returned = new ReturnedPrescription();
        $returned->prescription_id = $prescription->id;
        $returned->save();

        $request = Request::create('/update_prescription', 'POST', [
            'id' => $prescription->id,
            'patient' => [
                'hospital_id' => $this->test_hospital_id,
                'name' => 'TEST, PATIENT',
                'birthdate' => '1990-01-01',
                'sex' => 'M',
                'station' => 'TEST STATION',
                'ward' => 'TEST WARD',
                'bed_no' => '1',
                'attending_physician' => 'DR. TEST',
                'urgency' => 'Routine',
                'type' => '2',
                'drug_allergy' => '',
                'weight' => '',
                'height' => '',
                'serum_creatinine' => '',
                'creatinine_clearance' => '',
                'transaminases' => '',
                'sgpt' => '',
                'sgot' => '',
                'days_requested' => '',
                'additional_labs' => '',
                'diagnosis' => '',
                'for_extension' => 0,
                'remarks' => 'Resubmitting with corrected dosage',
            ],
            'indication_use' => ['description' => 'Empiric'],
            'microbiologicals' => [],
            'concurrent_therapy' => [],
            'medications' => [
                ['description' => 'NEW REJECTED MEDICINE', 'unit' => 'TABLET', 'dosage_strength' => '250', 'dosage_unit' => 'MG', 'frequency' => 'OD', 'quantity_ordered' => 0],
            ],
        ]);

        $response = (new ReturnedPrescriptionController())->updatePrescription($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);

        $remaining_medicines = PrescribedMedicine::where('prescription_id', $prescription->id)->get();
        $this->assertCount(2, $remaining_medicines);
        $this->assertTrue($remaining_medicines->contains('id', $approved_medicine->id));
        $this->assertEquals(1, PrescribedMedicine::find($approved_medicine->id)->status);
        $this->assertTrue($remaining_medicines->contains('description', 'NEW REJECTED MEDICINE'));
        $this->assertFalse($remaining_medicines->contains('description', 'OLD REJECTED MEDICINE'));
    }

    public function testUpdatePrescriptionCreatesNewIccReviewForResubmittedMedicines()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestMedicine($prescription->id, 2, 'OLD REJECTED MEDICINE');

        $returned = new ReturnedPrescription();
        $returned->prescription_id = $prescription->id;
        $returned->save();

        $request = Request::create('/update_prescription', 'POST', [
            'id' => $prescription->id,
            'patient' => [
                'hospital_id' => $this->test_hospital_id,
                'name' => 'TEST, PATIENT',
                'birthdate' => '1990-01-01',
                'sex' => 'M',
                'station' => 'TEST STATION',
                'ward' => 'TEST WARD',
                'bed_no' => '1',
                'attending_physician' => 'DR. TEST',
                'urgency' => 'Routine',
                'type' => '2',
                'drug_allergy' => '',
                'weight' => '',
                'height' => '',
                'serum_creatinine' => '',
                'creatinine_clearance' => '',
                'transaminases' => '',
                'sgpt' => '',
                'sgot' => '',
                'days_requested' => '',
                'additional_labs' => '',
                'diagnosis' => '',
                'for_extension' => 0,
                'remarks' => 'Resubmitting',
            ],
            'indication_use' => ['description' => 'Empiric'],
            'microbiologicals' => [],
            'concurrent_therapy' => [],
            'medications' => [
                ['description' => 'NEW REJECTED MEDICINE', 'unit' => 'TABLET', 'dosage_strength' => '250', 'dosage_unit' => 'MG', 'frequency' => 'OD', 'quantity_ordered' => 0],
            ],
        ]);

        (new ReturnedPrescriptionController())->updatePrescription($request);

        $this->assertNotNull(IccReview::where('prescription_id', $prescription->id)->first());
        $this->assertNull(ReturnedPrescription::where('prescription_id', $prescription->id)->first());
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=ReturnedPrescriptionControllerTest`
Expected: FAIL — `updatePrescription` currently deletes *all* medicines for the prescription (including the approved one), so `testUpdatePrescriptionOnlyRebuildsRejectedMedicines` fails on the count/contains assertions.

- [ ] **Step 3: Update `updatePrescription`**

In `app\Http\Controllers\ReturnedPrescriptionController.php`, change:

```php
            #DELETE ALL MEDICATIONS
            #INSERT ALL MEDICATIONS AS BULK
            $delete_medications = PrescribedMedicine::where('prescription_id', $prescription_id);
            $delete_medications->forceDelete();

            $medications = $request->medications;
            for ($i = 0; $i < count($medications); $i++) {
                $medications[$i]['prescription_id'] = $prescription_id;
            }
            PrescribedMedicine::insert($medications);

            $released_to = "ICC";
            if (($patient['type'] == '1' || $patient['type'] == '5' || $patient['type'] == '8') && $patient['for_extension'] != 1) {
                for ($i = 0; $i < count($medications); $i++) {
                    $concurrent = new ConcurrentTherapy;
                    $concurrent->prescription_id = $prescription_id;
                    $concurrent->hospital_id = $patient['hospital_id'];
                    $concurrent->concurrent_date = Carbon::now();
                    $concurrent->antimicrobial = $medications[$i]['description'];
                    $concurrent->dosing_regimen = $medications[$i]['dosage_strength'] . '' . $medications[$i]['dosage_unit'] . ' ' . $medications[$i]['frequency'];
                    $concurrent->start_date = Carbon::now();
                    $date = Carbon::now();
                    $date = $date->addDays(6);
                    $stop_date = date('Y-m-d', strtotime($date));
                    $concurrent->stop_date = $stop_date;
                    $concurrent->days_approved = 7;
                    $concurrent->date_approved = Carbon::now();
                    $concurrent->save();
                }

                $released_to = "PHARMACY";
                $pharmacy = PharmacyReview::where('prescription_id', $prescription_id)->first();
                if (!$pharmacy) {
                    $pharmacy = new PharmacyReview();
                    $pharmacy->prescription_id = $prescription_id;
                    $pharmacy->save();
                }
            } else {
                $icc = IccReview::where('prescription_id', $prescription_id)->first();
                if (!$icc) {
                    $icc = new IccReview();
                    $icc->prescription_id = $prescription_id;
                    $icc->save();
                }
            }
```

to:

```php
            #DELETE ONLY THE REJECTED MEDICATIONS
            #INSERT THE RESUBMITTED MEDICATIONS AS BULK, RESETTING THEM TO PENDING
            #APPROVED MEDICATIONS (ALREADY WITH PHARMACY) ARE UNTOUCHED
            $delete_medications = PrescribedMedicine::where('prescription_id', $prescription_id)->where('status', 2);
            $delete_medications->forceDelete();

            $medications = $request->medications;
            for ($i = 0; $i < count($medications); $i++) {
                $medications[$i]['prescription_id'] = $prescription_id;
                $medications[$i]['status'] = 0;
            }
            PrescribedMedicine::insert($medications);

            $released_to = "ICC";
            if (($patient['type'] == '1' || $patient['type'] == '5' || $patient['type'] == '8') && $patient['for_extension'] != 1) {
                for ($i = 0; $i < count($medications); $i++) {
                    $concurrent = new ConcurrentTherapy;
                    $concurrent->prescription_id = $prescription_id;
                    $concurrent->hospital_id = $patient['hospital_id'];
                    $concurrent->concurrent_date = Carbon::now();
                    $concurrent->antimicrobial = $medications[$i]['description'];
                    $concurrent->dosing_regimen = $medications[$i]['dosage_strength'] . '' . $medications[$i]['dosage_unit'] . ' ' . $medications[$i]['frequency'];
                    $concurrent->start_date = Carbon::now();
                    $date = Carbon::now();
                    $date = $date->addDays(6);
                    $stop_date = date('Y-m-d', strtotime($date));
                    $concurrent->stop_date = $stop_date;
                    $concurrent->days_approved = 7;
                    $concurrent->date_approved = Carbon::now();
                    $concurrent->save();
                }

                $released_to = "PHARMACY";
                $pharmacy = PharmacyReview::where('prescription_id', $prescription_id)->first();
                if (!$pharmacy) {
                    $pharmacy = new PharmacyReview();
                    $pharmacy->prescription_id = $prescription_id;
                    $pharmacy->save();
                }
            } else {
                $icc = IccReview::where('prescription_id', $prescription_id)->first();
                if (!$icc) {
                    $icc = new IccReview();
                    $icc->prescription_id = $prescription_id;
                    $icc->save();
                }
            }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=ReturnedPrescriptionControllerTest`
Expected: PASS (2 tests)

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: same pre-existing unrelated failure only, all else (now 9 new tests across the three files) passing.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/ReturnedPrescriptionController.php tests/Unit/ReturnedPrescriptionControllerTest.php
git commit -m "Only rebuild rejected medicines on prescription resubmission"
```

---

## Task 5: Remove the "1 medicine" limit

**Files:**
- Modify: `resources/views/js/home.blade.php:708-711` and `:1638-1645`
- Modify: `resources/views/js/returned_prescription.blade.php:409-412` and `:766-771`

- [ ] **Step 1: Remove the submit-time count check in `home.blade.php`**

Change:

```js
                if (medications.length > 1 && is_valid && (type != "1" && type != "5")) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Maximum of 1 medication per prescription</li>");
                }

                if (remarks == "") {
```

to:

```js
                if (remarks == "") {
```

- [ ] **Step 2: Remove the `appendMedicine()` count check in `home.blade.php`**

Change:

```js
        const count = $("#medication_list tr").length;

        if (prescribed_med_id !== "") {
            $("#medication_list tr:eq(" + prescribed_med_id + ")").remove();
        } else if (count > 0 && ($("#form_prescription_type").val() !== "1" && $("#form_prescription_type").val() !== "5ork")) {
            alert("Maximum of 1 medication per prescription only");
            return;
        }
```

to:

```js
        if (prescribed_med_id !== "") {
            $("#medication_list tr:eq(" + prescribed_med_id + ")").remove();
        }
```

- [ ] **Step 3: Remove the submit-time count check in `returned_prescription.blade.php`**

Change:

```js
                if (medications.length > 1 && is_valid && (type != "1" && type != "5")) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Maximum of 1 medication per prescription</li>");
                }

                if (remarks == "") {
```

to:

```js
                if (remarks == "") {
```

- [ ] **Step 4: Remove the `appendMedicine()` count check in `returned_prescription.blade.php`**

Change:

```js
        const unit = $("#" + id + " td:eq(1)").text();
        const count = $("#medication_list tr").length;

        if (prescribed_med_id === "") {
            //APPEND NEW RECORD
            if (count >= 1 && $("#form_prescription_type").val() != "1") {
                alert("Maximum of 1 medication only if Antiobiotic prescription");
                return;
            }

            let tr = "<tr>";
```

to:

```js
        const unit = $("#" + id + " td:eq(1)").text();
        const count = $("#medication_list tr").length;

        if (prescribed_med_id === "") {
            //APPEND NEW RECORD
            let tr = "<tr>";
```

- [ ] **Step 5: Verify Blade compiles cleanly**

Run: `php artisan view:cache` (expect "Blade templates cached successfully!"), then `php artisan view:clear`.

- [ ] **Step 6: Commit**

```bash
git add resources/views/js/home.blade.php resources/views/js/returned_prescription.blade.php
git commit -m "Remove the 1-medicine-per-prescription restriction"
```

---

## Task 6: ICC review — per-medicine decisions in the UI

**Files:**
- Modify: `resources/views/js/icc_review.blade.php`

- [ ] **Step 1: Rewrite `getMedications()` to render per-row decision controls (except for type 6)**

Change:

```js
    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        $("#microbiological_result_list").empty();
        fetch("{{ url('prescription/get') }}/" + id, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#medication_list").empty();
                if (data.code == 200) {
                    const ams = data.ams;
                    if (!jQuery.isEmptyObject(ams.indication_use)) {
                        const indication_index = INDICATION_USE_ARRAY.indexOf(ams.indication_use.description);
                        $("input[name='indication_use']:eq(" + indication_index + ")").prop("checked", true);
                    }
                    if (!jQuery.isEmptyObject(ams.intervention)) {
                        const clinical_index = CLINICAL_STABILITY.indexOf(ams.intervention.clinical_stability);
                        const oral_index = ORAL_INTAKE.indexOf(ams.intervention.oral_intake);

                        $("input[name='clinical_stability']:eq(" + clinical_index + ")").prop("checked", true);
                        $("input[name='oral_intake']:eq(" + oral_index + ")").prop("checked", true);
                    }

                    if (!jQuery.isEmptyObject(ams.microbiologicals)) {
                        ams.microbiologicals.forEach(it => {
                            let tr = "<tr>";
                            tr += "<td>" + moment(it.date).format("MM/DD/YYYY") + " </td>";
                            tr += "<td>" + it.specimen + "</td>";
                            tr += "<td colspan='3'>" + it.pathogen + "</td>";
                            tr += "</tr>";

                            $("#microbiological_result_list").append(tr);
                        });
                    }

                    data.results.forEach(it => {
                        const tr = `<tr>
                                            <td>` + it.description + `</td>
                                            <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                            <td>` + it.unit + `</td>
                                            <td class="d-none"><input type="number" class="form-control text-center" value="` + it.quantity_ordered + `"/></td>
                                            <td>` + it.frequency + `</td>
                                            <td></td>
                                         </tr>`;

                        $("#medication_list").append(tr);
                    });
                }
            }).catch(err => {
            console.log(err);
            $("#medication_list").empty();
            $("#medication_list").append("<tr><td colspan='7'>Something went wrong, please reload page</td></tr>");
        });
    }
```

to:

```js
    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        $("#microbiological_result_list").empty();
        const type_filter = ($("#form_prescription_type").val() == "6") ? "" : "?status=0";
        fetch("{{ url('prescription/get') }}/" + id + type_filter, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#medication_list").empty();
                if (data.code == 200) {
                    const ams = data.ams;
                    if (!jQuery.isEmptyObject(ams.indication_use)) {
                        const indication_index = INDICATION_USE_ARRAY.indexOf(ams.indication_use.description);
                        $("input[name='indication_use']:eq(" + indication_index + ")").prop("checked", true);
                    }
                    if (!jQuery.isEmptyObject(ams.intervention)) {
                        const clinical_index = CLINICAL_STABILITY.indexOf(ams.intervention.clinical_stability);
                        const oral_index = ORAL_INTAKE.indexOf(ams.intervention.oral_intake);

                        $("input[name='clinical_stability']:eq(" + clinical_index + ")").prop("checked", true);
                        $("input[name='oral_intake']:eq(" + oral_index + ")").prop("checked", true);
                    }

                    if (!jQuery.isEmptyObject(ams.microbiologicals)) {
                        ams.microbiologicals.forEach(it => {
                            let tr = "<tr>";
                            tr += "<td>" + moment(it.date).format("MM/DD/YYYY") + " </td>";
                            tr += "<td>" + it.specimen + "</td>";
                            tr += "<td colspan='3'>" + it.pathogen + "</td>";
                            tr += "</tr>";

                            $("#microbiological_result_list").append(tr);
                        });
                    }

                    const type = $("#form_prescription_type").val();

                    data.results.forEach((it, index) => {
                        if (type == "6") {
                            const tr = `<tr>
                                                <td>` + it.description + `</td>
                                                <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                                <td>` + it.unit + `</td>
                                                <td class="d-none"><input type="number" class="form-control text-center" value="` + it.quantity_ordered + `"/></td>
                                                <td>` + it.frequency + `</td>
                                                <td></td>
                                             </tr>`;

                            $("#medication_list").append(tr);
                            return;
                        }

                        const tr = `<tr data-medicine-id="` + it.id + `">
                                            <td>` + it.description + `</td>
                                            <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                            <td>` + it.unit + `</td>
                                            <td class="d-none"><input type="number" class="form-control text-center" value="` + it.quantity_ordered + `"/></td>
                                            <td>` + it.frequency + `</td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input medicine_decision" type="radio" name="medicine_decision_` + index + `" value="approved" checked>
                                                    <label class="form-check-label">Approve</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input medicine_decision" type="radio" name="medicine_decision_` + index + `" value="rejected">
                                                    <label class="form-check-label">Reject</label>
                                                </div>
                                                <textarea class="form-control mt-1 d-none medicine_remarks" placeholder="Reason for rejection" rows="2"></textarea>
                                            </td>
                                         </tr>`;

                        $("#medication_list").append(tr);
                    });

                    if (type != "6") {
                        $(".medicine_decision").change(function () {
                            const textarea = $(this).closest("tr").find(".medicine_remarks");
                            if ($(this).val() === "rejected") {
                                textarea.removeClass("d-none");
                            } else {
                                textarea.addClass("d-none").val("");
                            }
                        });
                    }
                }
            }).catch(err => {
            console.log(err);
            $("#medication_list").empty();
            $("#medication_list").append("<tr><td colspan='7'>Something went wrong, please reload page</td></tr>");
        });
    }
```

- [ ] **Step 2: Commit this piece**

```bash
git add resources/views/js/icc_review.blade.php
git commit -m "Render per-medicine decision controls in ICC review"
```

- [ ] **Step 3: Relabel the top-level buttons for non-type-6 prescriptions**

In `viewPrescription(id)`, change:

```js
        if (type == 6) {
            $("#btn_submit").addClass("d-none");
            $("#btn_reject").text("Cancel pending");

            const outcome_index = OUTCOMES.indexOf(outcome);
            if (outcome_index < 0) {
                $("input[name='outcome']:eq(5)").prop("checked", true);
                $("#outcome").val(outcome);
            } else {
                $("input[name='outcome']:eq(" + outcome_index + ")").prop("checked", true);
            }
        } else {
            $("#btn_submit").removeClass("d-none");
            $("#btn_reject").text("REJECT");
        }
```

to:

```js
        if (type == 6) {
            $("#btn_submit").addClass("d-none");
            $("#btn_reject").removeClass("d-none");
            $("#btn_reject").text("Cancel pending");

            const outcome_index = OUTCOMES.indexOf(outcome);
            if (outcome_index < 0) {
                $("input[name='outcome']:eq(5)").prop("checked", true);
                $("#outcome").val(outcome);
            } else {
                $("input[name='outcome']:eq(" + outcome_index + ")").prop("checked", true);
            }
        } else {
            $("#btn_submit").removeClass("d-none");
            $("#btn_submit").text("SUBMIT DECISIONS");
            $("#btn_reject").addClass("d-none");
        }
```

- [ ] **Step 4: Replace the `#btn_submit` click handler's decision-gathering logic**

Change:

```js
        $("#btn_submit").click(function () {
            button_clicked = $(this).text();
            prescription_id = $(this).val();

            $("#remarks_message").text("Reason for approval");
            $("#reject_outcome_container").addClass("d-none");
            $("#common_remarks").val("");
            $("input[name='reject_outcome']").prop("checked", false).change();
            $("#days_approved_container").removeClass("d-none");
            remarks_modal.toggle();
        });
```

to:

```js
        $("#btn_submit").click(function () {
            if ($("#form_prescription_type").val() == "6") {
                return;
            }

            const rows = $("#medication_list tr[data-medicine-id]");
            if (rows.length < 1) {
                alert("There are no medicines to decide on");
                return;
            }

            let has_approved = false;
            let has_rejected = false;
            let is_valid = true;

            rows.each(function () {
                const decision = $(this).find(".medicine_decision:checked").val();
                const remarks = $(this).find(".medicine_remarks").val().trim();
                if (decision === "rejected") {
                    has_rejected = true;
                    if (remarks === "") {
                        is_valid = false;
                    }
                } else {
                    has_approved = true;
                }
            });

            if (!is_valid) {
                alert("Please specify remarks for every rejected medicine");
                return;
            }

            button_clicked = "SUBMIT_DECISIONS";
            prescription_id = $(this).val();

            $("#remarks_message").text("Additional notes (optional)");
            $("#common_remarks").val("");
            $("#common_remarks").prop("required", false);
            $("#reject_outcome_container").toggleClass("d-none", !has_rejected);
            $("input[name='reject_outcome']").prop("checked", false).change();
            $("#days_approved_container").toggleClass("d-none", !has_approved);
            remarks_modal.toggle();
        });
```

- [ ] **Step 5: Replace the `#btn_proceed` handler's approve branch to call the new decide endpoint**

The handler's very first check requires `#common_remarks` to be non-empty before anything else runs — that must be skipped for the new flow, since `#common_remarks` is now optional "Additional notes" (Step 4 already relabels it that way) and per-medicine remarks were already validated back at the `#btn_submit` click. This step changes the **entire** `#btn_proceed` handler, not just its approve branch. Change:

```js
        $("#btn_proceed").click(function () {
            const remarks = $("#common_remarks").val().trim();
            const outcome = $("input[name='reject_outcome']:checked").next().text().trim();
            if (remarks == "") {
                alert("Please specify remarks or instructions for the receiver");
                return;
            }

            const params = {
                id: prescription_id,
                hospital_id: $("#form_hospital_id").text().trim(),
                type: $("#form_prescription_type").val(),
                origin: "ICC",
                outcome: (outcome == "") ? null : outcome,
                remarks: remarks
            }

            if (button_clicked == "APPROVE") {
                let days_approved = $("input[name='days_approved']:checked").next().text();
                let updated_quantity = $("#medication_list tr:eq(0) td:eq(3) input").val();

                // if (updated_quantity <= 0) {
                //     alert("Quantity ordered must be greater than 0");
                //     return;
                // }

                if (days_approved == "") {
                    alert("Please specify number of days");
                    return;
                }

                if (isNaN(days_approved)) {
                    days_approved = $("#days_approved").val();
                }

                if (days_approved == "" || days_approved < 1) {
                    alert("Please specify number of days");
                    return;
                }

                params.days_approved = days_approved;
                params.updated_quantity = updated_quantity;
                approvePrescription(params);
            } else if (button_clicked == "REJECT") {
                rejectPrescription(params);
            }
        });
```

to:

```js
        $("#btn_proceed").click(function () {
            const remarks = $("#common_remarks").val().trim();
            const outcome = $("input[name='reject_outcome']:checked").next().text().trim();
            if (remarks == "" && button_clicked != "SUBMIT_DECISIONS") {
                alert("Please specify remarks or instructions for the receiver");
                return;
            }

            const params = {
                id: prescription_id,
                hospital_id: $("#form_hospital_id").text().trim(),
                type: $("#form_prescription_type").val(),
                origin: "ICC",
                outcome: (outcome == "") ? null : outcome,
                remarks: remarks
            }

            if (button_clicked == "SUBMIT_DECISIONS") {
                const medications = [];
                let has_approved = false;

                $("#medication_list tr[data-medicine-id]").each(function () {
                    const decision = $(this).find(".medicine_decision:checked").val();
                    if (decision === "approved") {
                        has_approved = true;
                    }
                    medications.push({
                        id: $(this).data("medicine-id"),
                        decision: decision,
                        remarks: $(this).find(".medicine_remarks").val().trim()
                    });
                });

                let days_approved = "";
                if (has_approved) {
                    days_approved = $("input[name='days_approved']:checked").next().text();
                    if (days_approved == "") {
                        alert("Please specify number of days");
                        return;
                    }
                    if (isNaN(days_approved)) {
                        days_approved = $("#days_approved").val();
                    }
                    if (days_approved == "" || days_approved < 1) {
                        alert("Please specify number of days");
                        return;
                    }
                }

                params.medications = medications;
                params.days_approved = days_approved;
                decidePrescriptionMedications(params);
            } else if (button_clicked == "REJECT") {
                rejectPrescription(params);
            }
        });
```

- [ ] **Step 6: Add the `decidePrescriptionMedications` fetch function**

Add this function immediately after `approvePrescription(params)`:

```js
    function decidePrescriptionMedications(params) {
        $("#spinner").removeClass("d-none");
        fetch('{{ route('decide_prescription') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify(params)
        })
            .then(res => res.json())
            .then(data => {
                $("#spinner").addClass("d-none");
                if (data.code == 500) {
                    toastr.error(data.message, "Ooops");
                    return;
                }

                remarks_modal.toggle();
                prescription_modal.toggle();
                toastr.success(data.message, "Information");

                insert("PHARMACY", {id: 1}, function (message) {
                    deleteChild("PHARMACY");
                });

                getPrescriptions();
            }).catch(err => {
            $("#spinner").addClass("d-none");
            if ((err + "").includes("SyntaxError: Unexpected token")) {
                toastr.error("Please try again", "Server Connection Timeout")
                return;
            }
            toastr.error("Something went wrong", "Ooops")
        });
    }
```

- [ ] **Step 7: Verify Blade compiles cleanly**

Run: `php artisan view:cache` then `php artisan view:clear`.

- [ ] **Step 8: Commit**

```bash
git add resources/views/js/icc_review.blade.php
git commit -m "Wire per-medicine decisions to the new decide endpoint"
```

---

## Task 7: Pharmacy only ever sees approved medicines

**Files:**
- Modify: `resources/views/js/pharmacy_review.blade.php:429-435`

A prescription with a split ICC decision can have `prescribed_medicines` rows with `status = 1` (approved, meant for Pharmacy) and `status = 2` (rejected, meant for the doctor) at the same time. Without this filter, Pharmacy's dispensing view would also show the rejected medicine — this makes sure they only ever see what they're actually supposed to dispense.

- [ ] **Step 1: Filter Pharmacy's medicine fetch to approved only**

Change:

```js
    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        $("#microbiological_result_list").empty();
        fetch("{{ url('prescription/get') }}/" + id, {
            method: "GET"
        })
```

to:

```js
    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        $("#microbiological_result_list").empty();
        fetch("{{ url('prescription/get') }}/" + id + "?status=1", {
            method: "GET"
        })
```

- [ ] **Step 2: Verify Blade compiles cleanly**

Run: `php artisan view:cache` then `php artisan view:clear`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/js/pharmacy_review.blade.php
git commit -m "Filter Pharmacy's medicine list to approved medicines only"
```

---

## Task 8: Doctor's Returned view shows approved medicines read-only

**Files:**
- Modify: `resources/views/modals/prescription.blade.php` (add a read-only summary block)
- Modify: `resources/views/js/returned_prescription.blade.php`

- [ ] **Step 1: Add a read-only "Already Approved" block above the editable medicine table**

In `resources/views/modals/prescription.blade.php`, change:

```blade
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title fs-4">Rx</h6>
                            <div>
                                <button class="btn btn-sm btn-success" id="btn_view_cart">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table text-center v-center p-1 m-0">
                                <thead>
                                <tr id="table_header">
                                    <th>Medicine</th>
                                    <th style="width: 20%">Strength</th>
                                    <th style="width: 10%">Unit</th>
                                    <th style="width: 2%" class="d-none">Quantity<br/>Ordered</th>
                                    <th style="width: 15%">Frequency</th>
                                    <th colspan="2" style="width: 16%" id="quantity_render_th"></th>
                                </tr>
                                </thead>
                                <tbody id="medication_list" style="font-size: 12px">
                                </tbody>
                            </table>
                        </div>
                    </div>
```

to:

```blade
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title fs-4">Rx</h6>
                            <div>
                                <button class="btn btn-sm btn-success" id="btn_view_cart">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive d-none" id="approved_medication_container">
                            <div class="small text-muted mb-1">Already approved, with Pharmacy:</div>
                            <table class="table text-center v-center p-1 m-0">
                                <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th style="width: 20%">Strength</th>
                                    <th style="width: 10%">Unit</th>
                                    <th style="width: 15%">Frequency</th>
                                    <th style="width: 16%"></th>
                                </tr>
                                </thead>
                                <tbody id="approved_medication_list" style="font-size: 12px">
                                </tbody>
                            </table>
                        </div>
                        <div class="table-responsive">
                            <table class="table text-center v-center p-1 m-0">
                                <thead>
                                <tr id="table_header">
                                    <th>Medicine</th>
                                    <th style="width: 20%">Strength</th>
                                    <th style="width: 10%">Unit</th>
                                    <th style="width: 2%" class="d-none">Quantity<br/>Ordered</th>
                                    <th style="width: 15%">Frequency</th>
                                    <th colspan="2" style="width: 16%" id="quantity_render_th"></th>
                                </tr>
                                </thead>
                                <tbody id="medication_list" style="font-size: 12px">
                                </tbody>
                            </table>
                        </div>
                    </div>
```

This new block is shared markup like the rest of this modal, but only `returned_prescription.blade.php` will ever populate `#approved_medication_list` or remove its `d-none` — every other page's JS never references those ids, so they stay empty and hidden everywhere else.

- [ ] **Step 2: Commit the markup**

```bash
git add resources/views/modals/prescription.blade.php
git commit -m "Add read-only approved-medicines block to the Rx modal"
```

- [ ] **Step 3: Populate the read-only block in `returned_prescription.blade.php`**

Change:

```js
    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        $("#microbiological_result_list").empty();
        fetch("{{ url('prescription/get') }}/" + id, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#medication_list").empty();
                if (data.code == 200) {
```

to:

```js
    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        $("#microbiological_result_list").empty();
        $("#approved_medication_list").empty();
        $("#approved_medication_container").addClass("d-none");
        fetch("{{ url('prescription/get') }}/" + id + "?status=1", {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.code == 200 && data.results.length > 0) {
                    $("#approved_medication_container").removeClass("d-none");
                    data.results.forEach(it => {
                        const tr = `<tr>
                                            <td>` + it.description + `</td>
                                            <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                            <td>` + it.unit + `</td>
                                            <td>` + it.frequency + `</td>
                                            <td><span class="badge bg-success">Approved</span></td>
                                         </tr>`;
                        $("#approved_medication_list").append(tr);
                    });
                }
            }).catch(err => {
            console.log(err);
        });

        fetch("{{ url('prescription/get') }}/" + id + "?status=2", {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#medication_list").empty();
                if (data.code == 200) {
```

- [ ] **Step 4: Verify Blade compiles cleanly**

Run: `php artisan view:cache` then `php artisan view:clear`.

- [ ] **Step 5: Commit**

```bash
git add resources/views/js/returned_prescription.blade.php
git commit -m "Show approved medicines read-only in the doctor's Returned view"
```

---

## Task 9: Manual end-to-end verification

**Files:** none (browser walkthrough only — this codebase has no JS test harness for Blade/jQuery views)

- [ ] **Step 1: Create a multi-medicine prescription**

As a doctor, create a new prescription (type Restricted or Non-Monitored) with 2+ medicines.
Expected: no "Maximum of 1 medication" error; all medicines save and the prescription reaches ICC's queue.

- [ ] **Step 2: Split decision in ICC**

As ICC, open that prescription. Expected: each medicine has its own Approve/Reject radio, defaulting to Approve; selecting Reject on a row reveals its own remarks textarea. Set one medicine to Approve, another to Reject with remarks, then click "SUBMIT DECISIONS".
Expected: the confirmation dialog shows the "No. days approved" section (since at least one medicine is approved) and the "Specific reason" section (since at least one is rejected); after confirming, a success toast appears and the prescription modal closes.

- [ ] **Step 3: Verify the split landed correctly**

Check Pharmacy's queue: the prescription should appear there with only the approved medicine visible. Check the doctor's Returned queue: the same prescription should appear there too, showing the approved medicine read-only ("Already approved, with Pharmacy") and the rejected medicine editable.

- [ ] **Step 4: Resubmit the rejected medicine**

As the doctor, edit the rejected medicine in the Returned view and resubmit.
Expected: success; the prescription reappears in ICC's queue showing only the resubmitted medicine (the already-approved one does not reappear for re-review).

- [ ] **Step 5: Verify type 6 is untouched**

Create (or use an existing) type-6 prescription and reject it as ICC via "Cancel pending".
Expected: identical behavior to before this change — the warning dialog, cascading cancellation of other pending prescriptions for that patient/physician, no per-medicine controls shown.

- [ ] **Step 6: Verify Pharmacy and Nurse flows are unaffected**

Run a normal single-medicine prescription through Pharmacy render and Nurse receipt.
Expected: identical to current production behavior — no per-medicine controls appear on those pages, `#common_remarks`/`#reject_outcome_container` behave as before.
