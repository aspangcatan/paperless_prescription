# Per-Medicine Days Approved & Notes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** In ICC's per-medicine review, "days approved" and notes become per-medicine instead of one shared value applied to every approved medicine in a submission — each medicine gets its own days-approved count (used for its own `ConcurrentTherapy` record) and its own notes textarea (always visible, required only when rejecting).

**Architecture:** `PrescriptionController::decidePrescriptionMedications` moves `days_approved` from a submission-level request field to a per-medication payload field, validated and applied inside the existing per-medicine loop instead of once beforehand. `icc_review.blade.php`'s medicine rows gain their own days-approved radio group (mirroring the design of the shared one being removed) shown only when that row is set to Approve; the remarks textarea drops its show/hide-on-reject toggle and is simply always visible. The now-fully-superseded shared `#days_approved_container` markup (already ICC-only, single consumer) is deleted from `modals/common.blade.php`, and the confirmation dialog's `#common_remarks`/`#remarks_message` are hidden (not deleted — still used by Pharmacy/Nurse's own flows) rather than read, for this flow specifically.

**Tech Stack:** Laravel 8, Blade, jQuery, moment.js — all already used in these exact files.

**Design doc:** [docs/superpowers/specs/2026-08-19-per-medicine-days-approved-design.md](../specs/2026-08-19-per-medicine-days-approved-design.md)

---

## Task 1: Backend — per-medicine `days_approved` (TDD)

**Files:**
- Modify: `app\Http\Controllers\PrescriptionController.php` (`decidePrescriptionMedications`)
- Modify: `tests\Unit\PrescriptionControllerTest.php`

- [ ] **Step 1: Update existing tests to move `days_approved` into each approved medication, and add a new test for per-medicine values**

In `tests\Unit\PrescriptionControllerTest.php`, add `ConcurrentTherapy` to the imports. Change:

```php
use App\Http\Controllers\PrescriptionController;
use App\Models\IccReview;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedPrescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
```

to:

```php
use App\Http\Controllers\PrescriptionController;
use App\Models\ConcurrentTherapy;
use App\Models\IccReview;
use App\Models\PharmacyReview;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\PrescriptionLog;
use App\Models\ReturnedPrescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
```

Change `testDecideRejectsWhenAMedicationHasNoDecisionOrRejectedHasNoRemarks` (this medication is rejected-only, so the stale submission-level `days_approved` key is just dead weight — remove it):

```php
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

    public function testDecideRejectsWhenMedicationDoesNotBelongToPrescription()
```

to:

```php
        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'medications' => [
                ['id' => $medicine->id, 'decision' => 'rejected', 'remarks' => ''],
            ],
        ]);

        $response = (new PrescriptionController())->decidePrescriptionMedications($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
        $this->assertEquals(0, PrescribedMedicine::find($medicine->id)->status);
    }

    public function testDecideRejectsWhenMedicationDoesNotBelongToPrescription()
```

Change `testDecideRejectsWhenMedicationDoesNotBelongToPrescription` (move `days_approved` into the medication entry):

```php
        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'days_approved' => 7,
            'medications' => [
                ['id' => $unrelated_medicine->id, 'decision' => 'approved', 'remarks' => ''],
            ],
        ]);
```

to:

```php
        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'medications' => [
                ['id' => $unrelated_medicine->id, 'decision' => 'approved', 'remarks' => '', 'days_approved' => 7],
            ],
        ]);
```

Change `testDecideRejectsWhenMedicationAlreadyHasADecision` (this medication's decision is `rejected`, so the stale submission-level `days_approved` key is dead weight — remove it):

```php
        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'days_approved' => 7,
            'medications' => [
                ['id' => $already_approved_medicine->id, 'decision' => 'rejected', 'remarks' => 'trying to flip it back'],
            ],
        ]);
```

to:

```php
        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'medications' => [
                ['id' => $already_approved_medicine->id, 'decision' => 'rejected', 'remarks' => 'trying to flip it back'],
            ],
        ]);
```

`testDecideRequiresDaysApprovedWhenAnyMedicationIsApproved` needs no change — it already omits `days_approved` entirely (previously testing the submission-level requirement; now tests the identical outcome for the per-medication requirement, since the medication entry `['id' => $medicine->id, 'decision' => 'approved', 'remarks' => '']` has no `days_approved` key either way).

Change `testDecideSplitsApprovedAndRejectedMedicinesInOneSubmission` (move `days_approved` into the approved medication's entry):

```php
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
```

to:

```php
        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'outcome' => 'Wrong indication',
            'medications' => [
                ['id' => $approved_medicine->id, 'decision' => 'approved', 'remarks' => '', 'days_approved' => 7],
                ['id' => $rejected_medicine->id, 'decision' => 'rejected', 'remarks' => 'Dosage exceeds weight-based max'],
            ],
        ]);
```

`testDecideDoesNotCreatePharmacyReviewWhenAllMedicinesRejected` needs no change — its only medication is `rejected`, no `days_approved` key was ever present.

Change `testDecideDoesNotCreateReturnedPrescriptionWhenAllMedicinesApproved` (move `days_approved` into the medication entry):

```php
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

    public function testInsertPrescriptionMarksMedicinesApprovedForIccSkipTypes()
```

to:

```php
        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'medications' => [
                ['id' => $medicine->id, 'decision' => 'approved', 'remarks' => '', 'days_approved' => 7],
            ],
        ]);

        (new PrescriptionController())->decidePrescriptionMedications($request);

        $this->assertNotNull(PharmacyReview::where('prescription_id', $prescription->id)->first());
        $this->assertNull(ReturnedPrescription::where('prescription_id', $prescription->id)->first());
    }

    public function testDecideAppliesDifferentDaysApprovedPerMedicine()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestIccReview($prescription->id);
        $medicine_a = $this->makeTestMedicine($prescription->id);
        $medicine_b = $this->makeTestMedicine($prescription->id);

        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'medications' => [
                ['id' => $medicine_a->id, 'decision' => 'approved', 'remarks' => '', 'days_approved' => 3],
                ['id' => $medicine_b->id, 'decision' => 'approved', 'remarks' => '', 'days_approved' => 14],
            ],
        ]);

        $response = (new PrescriptionController())->decidePrescriptionMedications($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);

        $concurrent_therapies = ConcurrentTherapy::where('prescription_id', $prescription->id)->orderBy('id')->get();
        $this->assertCount(2, $concurrent_therapies);
        $this->assertEquals(3, $concurrent_therapies[0]->days_approved);
        $this->assertEquals(14, $concurrent_therapies[1]->days_approved);
        $this->assertNotEquals($concurrent_therapies[0]->stop_date, $concurrent_therapies[1]->stop_date);
    }

    public function testInsertPrescriptionMarksMedicinesApprovedForIccSkipTypes()
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PrescriptionControllerTest`
Expected: `testDecideAppliesDifferentDaysApprovedPerMedicine` FAILS (the controller doesn't read per-medication `days_approved` yet, so both `ConcurrentTherapy` rows would get created with `days_approved = null`/an error, or the request would be rejected outright since the medications now lack the submission-level `days_approved` field the current code still requires — either way, red). `testDecideSplitsApprovedAndRejectedMedicinesInOneSubmission` and `testDecideDoesNotCreateReturnedPrescriptionWhenAllMedicinesApproved` also FAIL, since they no longer send the submission-level `days_approved` the current code still requires.

- [ ] **Step 3: Move `days_approved` from submission-level to per-medication in `decidePrescriptionMedications`**

In `app\Http\Controllers\PrescriptionController.php`, change:

```php
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
```

to:

```php
                if ($medication['decision'] == 'rejected' && empty($medication['remarks'])) {
                    return response(['code' => 500, 'message' => 'Remarks are required for a rejected medication']);
                }
                if ($medication['decision'] == 'approved' && empty($medication['days_approved'] ?? null)) {
                    return response(['code' => 500, 'message' => 'Please specify number of days approved for each approved medication']);
                }
                if ($medication['decision'] == 'approved') {
                    $has_approved = true;
                } else {
                    $has_rejected = true;
                }
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

            foreach ($medications as $medication) {
                $prescribed_medicine = PrescribedMedicine::find($medication['id']);
                if (!$prescribed_medicine) {
                    continue;
                }

                if ($medication['decision'] == 'approved') {
                    $prescribed_medicine->status = 1;
                    $prescribed_medicine->save();

                    $date = Carbon::now();
                    $date = $date->addDays($medication['days_approved'] - 1);
                    $stop_date = date('Y-m-d', strtotime($date));

                    $concurrent = new ConcurrentTherapy;
                    $concurrent->prescription_id = $request->id;
                    $concurrent->hospital_id = $request->hospital_id;
                    $concurrent->concurrent_date = $date_approved;
                    $concurrent->antimicrobial = $prescribed_medicine->description;
                    $concurrent->dosing_regimen = $prescribed_medicine->dosage_strength . '' . $prescribed_medicine->dosage_unit . ' ' . $prescribed_medicine->frequency;
                    $concurrent->start_date = $date_approved;
                    $concurrent->stop_date = $stop_date;
                    $concurrent->days_approved = $medication['days_approved'];
                    $concurrent->approved_by = Auth::id();
                    $concurrent->date_approved = $date_approved;
                    $concurrent->save();

                    $released_to = 'PHARMACY';
                    $badge = "<span class='badge bg-success text-white'>Approved <i class='bi bi-hand-thumbs-up-fill'></i></span>";
                } else {
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=PrescriptionControllerTest`
Expected: PASS (13 tests — 12 existing + 1 new)

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: same pre-existing unrelated `Tests\Feature\ExampleTest` failure only, all else passing.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PrescriptionController.php tests/Unit/PrescriptionControllerTest.php
git commit -m "Move ICC's days-approved from per-submission to per-medicine"
```

---

## Task 2: Frontend — per-row days-approved control and always-visible notes

**Files:**
- Modify: `resources/views/js/icc_review.blade.php`

- [ ] **Step 1: Add the per-row days-approved control and make remarks always visible**

Change:

```js
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
```

to:

```js
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
                                                <div class="medicine_days_approved_container mt-2">
                                                    <div class="small text-muted">No. days approved</div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input medicine_days_approved" type="radio" name="days_approved_` + index + `" value="1">
                                                        <label class="form-check-label">1</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input medicine_days_approved" type="radio" name="days_approved_` + index + `" value="3">
                                                        <label class="form-check-label">3</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input medicine_days_approved" type="radio" name="days_approved_` + index + `" value="7">
                                                        <label class="form-check-label">7</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input medicine_days_approved" type="radio" name="days_approved_` + index + `" value="14">
                                                        <label class="form-check-label">14</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input medicine_days_approved" type="radio" name="days_approved_` + index + `" value="other">
                                                        <label class="form-check-label">Other</label>
                                                        <input type="number" class="form-control form-control-sm d-inline-block medicine_days_approved_custom" style="width: 70px;" min="1"/>
                                                    </div>
                                                </div>
                                                <textarea class="form-control mt-2 medicine_remarks" placeholder="Notes (required if rejecting)" rows="2"></textarea>
                                            </td>
                                         </tr>`;

                        $("#medication_list").append(tr);
                    });

                    if (type != "6") {
                        $(".medicine_decision").change(function () {
                            const row = $(this).closest("tr");
                            const is_approved = $(this).val() === "approved";
                            row.find(".medicine_days_approved_container").toggleClass("d-none", !is_approved);
                            if (!is_approved) {
                                row.find(".medicine_days_approved").prop("checked", false);
                                row.find(".medicine_days_approved_custom").val("");
                            }
                        });
                    }
```

- [ ] **Step 2: Commit this piece**

```bash
git add resources/views/js/icc_review.blade.php
git commit -m "Add per-medicine days-approved control to ICC review rows"
```

- [ ] **Step 3: Validate per-row days-approved on `#btn_submit`, and hide the now-unused shared notes field**

Change:

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
                    const days_approved_choice = $(this).find(".medicine_days_approved:checked").val();
                    if (!days_approved_choice) {
                        is_valid = false;
                    } else if (days_approved_choice === "other") {
                        const custom_days = $(this).find(".medicine_days_approved_custom").val();
                        if (custom_days === "" || custom_days < 1) {
                            is_valid = false;
                        }
                    }
                }
            });

            if (!is_valid) {
                alert("Please specify remarks for every rejected medicine, and number of days approved for every approved medicine");
                return;
            }

            button_clicked = "SUBMIT_DECISIONS";
            prescription_id = $(this).val();

            $("#remarks_message, #common_remarks").addClass("d-none");
            $("#reject_outcome_container").toggleClass("d-none", !has_rejected);
            $("input[name='reject_outcome']").prop("checked", false).change();
            remarks_modal.toggle();
        });
```

Note: `#remarks_message`/`#common_remarks` are hidden (not deleted) — they're shared markup Pharmacy/Nurse's own separate pages still use unmodified. This page never shows them again afterward (type 6 uses a different dialog entirely; the old whole-prescription `REJECT` path is unreachable since `#btn_reject` stays hidden for every non-6 type — see Task 6 of the original per-medicine ICC review plan), so no restore logic is needed.

- [ ] **Step 4: Read per-row `days_approved` when building the submission payload**

Change:

```js
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

to:

```js
            if (button_clicked == "SUBMIT_DECISIONS") {
                const medications = [];

                $("#medication_list tr[data-medicine-id]").each(function () {
                    const decision = $(this).find(".medicine_decision:checked").val();
                    const medication = {
                        id: $(this).data("medicine-id"),
                        decision: decision,
                        remarks: $(this).find(".medicine_remarks").val().trim()
                    };

                    if (decision === "approved") {
                        let days_approved = $(this).find(".medicine_days_approved:checked").val();
                        if (days_approved === "other") {
                            days_approved = $(this).find(".medicine_days_approved_custom").val();
                        }
                        medication.days_approved = days_approved;
                    }

                    medications.push(medication);
                });

                params.medications = medications;
                decidePrescriptionMedications(params);
            } else if (button_clicked == "REJECT") {
                rejectPrescription(params);
            }
        });
```

`#btn_submit`'s validation (Step 3) already guarantees every approved row has a usable days-approved value before the modal even opens, so this step doesn't need to re-validate — same pattern the existing remarks-required-on-reject check already follows (validated once at `#btn_submit`, trusted by the time `#btn_proceed` runs).

- [ ] **Step 5: Verify Blade compiles cleanly**

Run: `php artisan view:cache` then `php artisan view:clear`.

- [ ] **Step 6: Commit**

```bash
git add resources/views/js/icc_review.blade.php
git commit -m "Read per-medicine days-approved when submitting ICC decisions"
```

---

## Task 3: Remove the now-dead shared "days approved" markup

**Files:**
- Modify: `resources/views/modals/common.blade.php`

The `#days_approved_container` block was already gated to ICC-only sessions (`@if(session('access_rights') == "ICC")`) with exactly one consumer — the submission-level days-approved flow just replaced in Task 2. Nothing renders or reads it anymore, so it's deleted rather than left as dead markup.

- [ ] **Step 1: Remove the block**

Change:

```blade
                @if(session('access_rights') == "ICC")
                    <div id="days_approved_container" class="mt-3">
                        <h6>No. days approved</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input  type="radio" name="days_approved">
                                    <label class="form-check-label">1</label>
                                </div>
                                <div class="form-check">
                                    <input  type="radio" name="days_approved">
                                    <label class="form-check-label">3</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input  type="radio" name="days_approved">
                                    <label class="form-check-label">7</label>
                                </div>
                                <div class="form-check">
                                    <input  type="radio" name="days_approved">
                                    <label class="form-check-label">14</label>
                                </div>
                                <div class="form-check">
                                    <input  type="radio" name="days_approved">
                                    <div class="d-flex align-items-center">
                                        <label class="form-check-label">Others</label>
                                        <input type="number" class="underline ms-1" id="days_approved"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <hr/>
                <button class="btn btn-primary mt-2 float-end" id="btn_proceed">Proceed</button>
```

to:

```blade
                <hr/>
                <button class="btn btn-primary mt-2 float-end" id="btn_proceed">Proceed</button>
```

- [ ] **Step 2: Verify Blade compiles cleanly**

Run: `php artisan view:cache` then `php artisan view:clear`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/modals/common.blade.php
git commit -m "Remove the superseded shared days-approved markup"
```

---

## Task 4: Manual end-to-end verification

**Files:** none (browser walkthrough only — this codebase has no JS test harness)

- [ ] **Step 1: Verify per-row controls render correctly**

As ICC, open a prescription with 2+ medicines (any non-type-6 type). Expected: each row shows Approve (default-selected)/Reject, and — since Approve is selected by default — the days-approved radio group (1/3/7/14/Other) visible immediately, plus an always-visible notes textarea.

- [ ] **Step 2: Verify Reject hides days-approved, keeps notes visible**

Switch one row to Reject. Expected: that row's days-approved group disappears (and any prior selection there is cleared); the notes textarea stays visible and is now the required rejection reason.

- [ ] **Step 3: Verify submit-time validation**

Click "SUBMIT DECISIONS" leaving an approved row's days-approved unselected. Expected: blocked with an alert, no modal opens. Fill it in (try both a canned value and "Other" with a custom number), leave a rejected row's notes empty. Expected: blocked with an alert. Fill in both correctly and submit.

- [ ] **Step 4: Verify the confirmation dialog no longer shows the old shared fields**

When the dialog opens: expected no "Additional notes" textarea and no shared "No. days approved" section — only the canned rejection-reason picker (if anything was rejected) and the Proceed button.

- [ ] **Step 5: Verify different days-approved values actually apply per medicine**

Approve two medicines with different day counts (e.g. 3 and 14). After submitting, check each medicine's resulting stewardship/concurrent-therapy record (wherever that's visible in the app, e.g. the AMS summary or concurrent therapy list) shows its own distinct day count and stop date, not a shared one.

- [ ] **Step 6: Verify Pharmacy/Nurse/type-6 are unaffected**

Confirm Pharmacy's and Nurse's own approve/render flows still work exactly as before (they never used `#days_approved_container`, so this should be a non-event) — and confirm type 6's "Cancel pending" flow still shows its own dialog exactly as before, no per-medicine controls.
