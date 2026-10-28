<?php

namespace Tests\Feature;

use App\Models\ConcurrentTherapy;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Multiple medicines per Rx with per-medicine IPCC decisions. Runs on the local .env database;
 * every test rolls back, so the existing records are never changed.
 */
class PrescriptionWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    #REAL LOCAL USER IDS (REPORTS INNER-JOIN THE PRESCRIBER); THE ROLE ALWAYS COMES FROM THE SESSION
    private static $ids;
    const DOCTOR_ID = 0;
    const OTHER_DOCTOR_ID = 1;
    const ICC_ID = 2;
    const PHARMACIST_ID = 3;
    const NURSE_ID = 4;

    private function userId($slot)
    {
        if (!self::$ids) {
            self::$ids = DB::connection('users')->table('users')->orderBy('id')->limit(5)->pluck('id')->all();
        }
        return self::$ids[$slot];
    }

    private function actingAsRole($id, $role)
    {
        $user = new User;
        $user->id = $id;
        $user->fname = 'Test';
        $user->lname = $role;
        return $this->actingAs($user)->withSession(['access_rights' => $role]);
    }

    private function medicine($description)
    {
        return [
            'description' => $description,
            'unit' => 'vial',
            'dosage_strength' => '1',
            'dosage_unit' => 'g',
            'frequency' => 'EVERY 8 HOURS',
        ];
    }

    private function payload($type, array $medications, array $patient = [])
    {
        return [
            'patient' => array_merge([
                'hospital_id' => 'TEST-MULTI-0001',
                'name' => 'TESTPATIENT, MULTI MEDICINE',
                'birthdate' => '1980-01-01',
                'sex' => 'M',
                'station' => 'MEDICAL WARD (STATION 1)',
                'ward' => 'MEDICAL WARD (STATION 1)',
                'bed_no' => 'N/A',
                'attending_physician' => 'Dr. Test',
                'remarks' => 'Please review',
                'urgency' => 'Routine',
                'type' => $type,
                'for_extension' => '0',
                'drug_allergy' => '',
                'weight' => 60,
                'height' => 170,
                'serum_creatinine' => '',
                'creatinine_clearance' => '',
                'transaminases' => '',
                'sgpt' => '',
                'sgot' => '',
                'days_requested' => '',
                'additional_labs' => '',
                'outcome' => '',
                'diagnosis' => 'Pneumonia',
            ], $patient),
            'indication_use' => ['description' => 'Empiric'],
            'microbiologicals' => [],
            'concurrent_therapy' => [],
            'intervention' => ['concurrent_id' => '0'],
            'medications' => $medications,
        ];
    }

    /**
     * Creates an Rx as the test doctor and returns it with its medicines in insertion order.
     */
    private function prescribe($type, array $medications)
    {
        $before = Prescription::max('id');
        $this->actingAsRole($this->userId(self::DOCTOR_ID), 'Doctor')
            ->postJson('/insert_prescription', $this->payload($type, $medications))
            ->assertJson(['code' => 200]);

        $prescription = Prescription::where('id', '>', $before)->where('physician_id', $this->userId(self::DOCTOR_ID))->latest('id')->firstOrFail();
        $medicines = PrescribedMedicine::where('prescription_id', $prescription->id)->orderBy('id')->get();
        return [$prescription, $medicines];
    }

    private function decide($prescription_id, array $decisions)
    {
        return $this->actingAsRole($this->userId(self::ICC_ID), 'ICC')
            ->postJson('/icc/decide_prescription', ['id' => $prescription_id, 'medications' => $decisions]);
    }

    private function reviewStatus($prescription_id)
    {
        return (int)Prescription::find($prescription_id)->review_status;
    }

    // 1
    public function testRxWithOneMedicineStillWorks()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Meropenem 1 g vial')]);

        $this->assertCount(1, $meds);
        $this->assertEquals(PrescribedMedicine::STATUS_PENDING, $meds[0]->status);
        $this->assertDatabaseHas('icc_reviews', ['prescription_id' => $rx->id]);
        $this->assertEquals(Prescription::REVIEW_PENDING, $this->reviewStatus($rx->id));
    }

    // 2 and 3
    public function testRxWithSeveralMedicinesKeepsEachOnesDetails()
    {
        [$rx, $meds] = $this->prescribe('2', [
            $this->medicine('Medicine A'),
            array_merge($this->medicine('Medicine B'), ['dosage_strength' => '500', 'dosage_unit' => 'mg', 'frequency' => 'EVERY 12 HOURS']),
            $this->medicine('Medicine C'),
        ]);

        $this->assertCount(3, $meds);
        $this->assertEquals(['Medicine A', 'Medicine B', 'Medicine C'], $meds->pluck('description')->all());
        $this->assertEquals('500', $meds[1]->dosage_strength);
        $this->assertEquals('mg', $meds[1]->dosage_unit);
        $this->assertEquals('EVERY 12 HOURS', $meds[1]->frequency);
    }

    public function testMonitoredRxAutoApprovesEveryMedicineForSevenDays()
    {
        [$rx, $meds] = $this->prescribe('5', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);

        foreach ($meds as $med) {
            $this->assertEquals(PrescribedMedicine::STATUS_APPROVED, $med->status);
            $this->assertEquals(7, $med->days_approved);
            $this->assertDatabaseHas('concurrent_therapy', ['prescribed_medicine_id' => $med->id, 'days_approved' => 7]);
        }
        $this->assertDatabaseHas('pharmacy_reviews', ['prescription_id' => $rx->id]);
        $this->assertDatabaseMissing('icc_reviews', ['prescription_id' => $rx->id]);
        $this->assertEquals(Prescription::REVIEW_APPROVED, $this->reviewStatus($rx->id));
    }

    // 4 and Example A
    public function testEachMedicineGetsItsOwnApprovedDays()
    {
        [$rx, $meds] = $this->prescribe('2', [
            $this->medicine('Medicine A'), $this->medicine('Medicine B'), $this->medicine('Medicine C'),
        ]);

        $this->decide($rx->id, [
            ['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 21],
            ['id' => $meds[1]->id, 'decision' => 'approved', 'days_approved' => 14],
            ['id' => $meds[2]->id, 'decision' => 'approved', 'days_approved' => 14],
        ])->assertJson(['code' => 200, 'has_approved' => true, 'has_rejected' => false]);

        $this->assertEquals([21, 14, 14], PrescribedMedicine::whereIn('id', $meds->pluck('id'))->orderBy('id')->pluck('days_approved')->all());
        $this->assertEquals(21, ConcurrentTherapy::where('prescribed_medicine_id', $meds[0]->id)->value('days_approved'));
        $this->assertEquals(14, ConcurrentTherapy::where('prescribed_medicine_id', $meds[1]->id)->value('days_approved'));
        $this->assertEquals($this->userId(self::ICC_ID), PrescribedMedicine::find($meds[0]->id)->decided_by);
        $this->assertNotNull(PrescribedMedicine::find($meds[0]->id)->decided_at);
        $this->assertDatabaseHas('pharmacy_reviews', ['prescription_id' => $rx->id]);
        $this->assertDatabaseMissing('icc_reviews', ['prescription_id' => $rx->id]);
        $this->assertEquals(Prescription::REVIEW_APPROVED, $this->reviewStatus($rx->id));
    }

    // 5, 6 and Example B
    public function testRejectingOneMedicineLeavesTheOthersApproved()
    {
        [$rx, $meds] = $this->prescribe('2', [
            $this->medicine('Medicine A'), $this->medicine('Medicine B'), $this->medicine('Medicine C'),
        ]);

        $this->decide($rx->id, [
            ['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 21],
            ['id' => $meds[1]->id, 'decision' => 'rejected', 'remarks' => 'Wrong indication'],
            ['id' => $meds[2]->id, 'decision' => 'approved', 'days_approved' => 14],
        ])->assertJson(['code' => 200, 'has_approved' => true, 'has_rejected' => true]);

        $a = PrescribedMedicine::find($meds[0]->id);
        $b = PrescribedMedicine::find($meds[1]->id);
        $c = PrescribedMedicine::find($meds[2]->id);
        $this->assertEquals([PrescribedMedicine::STATUS_APPROVED, 21], [$a->status, $a->days_approved]);
        $this->assertEquals([PrescribedMedicine::STATUS_REJECTED, null, 'Wrong indication'], [$b->status, $b->days_approved, $b->decision_remarks]);
        $this->assertEquals([PrescribedMedicine::STATUS_APPROVED, 14], [$c->status, $c->days_approved]);
        $this->assertDatabaseMissing('concurrent_therapy', ['prescribed_medicine_id' => $b->id]);

        #APPROVED ONES MOVE ON TO PHARMACY WHILE THE REJECTED ONE GOES BACK TO THE DOCTOR
        $this->assertDatabaseHas('pharmacy_reviews', ['prescription_id' => $rx->id]);
        $this->assertDatabaseHas('returned_prescriptions', ['prescription_id' => $rx->id]);
        $this->assertEquals(Prescription::REVIEW_PARTIALLY_APPROVED, $this->reviewStatus($rx->id));
        $this->assertEquals(1, Prescription::find($rx->id)->status, 'The Rx itself stays active');
        $this->assertDatabaseHas('prescription_logs', ['prescription_id' => $rx->id, 'prescribed_medicine_id' => $b->id, 'released_to' => 'DOCTOR']);
    }

    // 7 and 9
    public function testApprovingOneMedicineLeavesUndecidedOnesPending()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);

        $this->decide($rx->id, [['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 7]])
            ->assertJson(['code' => 200]);

        $this->assertEquals(PrescribedMedicine::STATUS_APPROVED, PrescribedMedicine::find($meds[0]->id)->status);
        $this->assertEquals(PrescribedMedicine::STATUS_PENDING, PrescribedMedicine::find($meds[1]->id)->status);
        $this->assertNull(PrescribedMedicine::find($meds[1]->id)->days_approved);
        $this->assertDatabaseHas('icc_reviews', ['prescription_id' => $rx->id]);
        $this->assertEquals(Prescription::REVIEW_PARTIALLY_APPROVED, $this->reviewStatus($rx->id));

        $this->decide($rx->id, [['id' => $meds[1]->id, 'decision' => 'approved', 'days_approved' => 3]])
            ->assertJson(['code' => 200]);
        $this->assertDatabaseMissing('icc_reviews', ['prescription_id' => $rx->id]);
        $this->assertEquals(Prescription::REVIEW_APPROVED, $this->reviewStatus($rx->id));
    }

    // 8
    public function testAllRejectedMakesTheRxRejected()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);

        $this->decide($rx->id, [
            ['id' => $meds[0]->id, 'decision' => 'rejected', 'remarks' => 'Double request'],
            ['id' => $meds[1]->id, 'decision' => 'rejected', 'remarks' => 'Incomplete data'],
        ])->assertJson(['code' => 200, 'has_approved' => false]);

        $this->assertEquals(Prescription::REVIEW_REJECTED, $this->reviewStatus($rx->id));
        $this->assertDatabaseMissing('pharmacy_reviews', ['prescription_id' => $rx->id]);
        $this->assertDatabaseHas('returned_prescriptions', ['prescription_id' => $rx->id]);
    }

    // 10
    public function testExistingSingleMedicinePrescriptionsStayReadable()
    {
        $legacy = DB::table('prescribed_medicines')
            ->where('prescription_id', '<', 40000)
            ->where('status', PrescribedMedicine::STATUS_APPROVED)
            ->whereNotNull('days_approved')
            ->orderBy('id')
            ->first();
        $this->assertNotNull($legacy, 'Expected backfilled legacy medicines in the local database');

        $this->actingAsRole($this->userId(self::ICC_ID), 'ICC')
            ->getJson('/prescription/get/' . $legacy->prescription_id)
            ->assertJson(['code' => 200, 'review_status' => Prescription::REVIEW_APPROVED])
            ->assertJsonPath('results.0.id', $legacy->id)
            ->assertJsonPath('results.0.status', PrescribedMedicine::STATUS_APPROVED)
            ->assertJsonPath('results.0.days_approved', $legacy->days_approved);

        $this->actingAsRole($this->userId(self::ICC_ID), 'ICC')
            ->getJson('/prescription/get/logs/' . $legacy->prescription_id)
            ->assertJson(['code' => 200]);
    }

    // 11
    public function testOnlyTheRightRoleCanDecideOrDispense()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A')]);
        $decision = ['id' => $rx->id, 'medications' => [['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 7]]];

        foreach (['Doctor', 'Nurse', 'Pharmacist'] as $role) {
            $this->actingAsRole($this->userId(self::NURSE_ID), $role)->postJson('/icc/decide_prescription', $decision)
                ->assertJson(['code' => 500, 'message' => 'Permission denied']);
        }

        #A NURSE CAN'T APPROVE AS IPCC BY SENDING origin=ICC: THE STAGE COMES FROM THE ROLE
        $this->actingAsRole($this->userId(self::NURSE_ID), 'Nurse')
            ->postJson('/approve_prescription', ['id' => $rx->id, 'origin' => 'ICC', 'days_approved' => 7])
            ->assertJson(['code' => 500]);
        $this->actingAsRole($this->userId(self::ICC_ID), 'ICC')
            ->postJson('/approve_prescription', ['id' => $rx->id, 'origin' => 'ICC', 'days_approved' => 7])
            ->assertJson(['code' => 500, 'message' => 'Permission denied']);
        $this->actingAsRole($this->userId(self::DOCTOR_ID), 'Doctor')
            ->postJson('/render_prescription', ['id' => $rx->id, 'medications' => []])
            ->assertJson(['code' => 500, 'message' => 'Permission denied']);

        $this->assertEquals(PrescribedMedicine::STATUS_PENDING, PrescribedMedicine::find($meds[0]->id)->status);
        $this->assertDatabaseHas('icc_reviews', ['prescription_id' => $rx->id]);
    }

    public function testDoctorsCanOnlyResubmitTheirOwnPrescriptions()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A')]);
        $this->decide($rx->id, [['id' => $meds[0]->id, 'decision' => 'rejected', 'remarks' => 'Wrong indication']]);

        $payload = $this->payload('2', [array_merge($this->medicine('Medicine A'), ['id' => $meds[0]->id])]);
        $payload['id'] = $rx->id;
        $this->actingAsRole($this->userId(self::OTHER_DOCTOR_ID), 'Doctor')->postJson('/update_prescription', $payload)
            ->assertJson(['code' => 500, 'message' => 'Permission denied']);
        $this->actingAsRole($this->userId(self::OTHER_DOCTOR_ID), 'Doctor')->deleteJson('/prescription/delete', ['id' => $rx->id])
            ->assertJson(['code' => 500, 'message' => 'Permission denied']);

        $this->assertEquals(PrescribedMedicine::STATUS_REJECTED, PrescribedMedicine::find($meds[0]->id)->status);
    }

    // 12
    public function testInvalidApprovedDaysAreRefusedAndNothingIsWritten()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);

        foreach ([0, -3, 'abc', 2.5, null] as $days) {
            $this->decide($rx->id, [
                ['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 7],
                ['id' => $meds[1]->id, 'decision' => 'approved', 'days_approved' => $days],
            ])->assertJson(['code' => 500]);
        }

        $this->decide($rx->id, [['id' => $meds[0]->id, 'decision' => 'rejected', 'remarks' => '  ']])
            ->assertJson(['code' => 500, 'message' => 'A rejection reason is required for every rejected medicine']);

        $this->assertEquals(0, PrescribedMedicine::whereIn('id', $meds->pluck('id'))->where('status', '!=', PrescribedMedicine::STATUS_PENDING)->count());
        $this->assertEquals(0, ConcurrentTherapy::whereIn('prescribed_medicine_id', $meds->pluck('id'))->count());
    }

    // 13
    public function testAStaleDecisionCannotOverwriteAnotherReviewersDecision()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);

        $this->decide($rx->id, [['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 14]])->assertJson(['code' => 200]);

        #A SECOND REVIEWER STILL HOLDING THE OLD SCREEN TRIES TO REJECT A AND APPROVE B
        $this->decide($rx->id, [
            ['id' => $meds[0]->id, 'decision' => 'rejected', 'remarks' => 'Too long'],
            ['id' => $meds[1]->id, 'decision' => 'approved', 'days_approved' => 3],
        ])->assertJson(['code' => 500]);

        $a = PrescribedMedicine::find($meds[0]->id);
        $this->assertEquals([PrescribedMedicine::STATUS_APPROVED, 14], [$a->status, $a->days_approved]);
        $this->assertEquals(PrescribedMedicine::STATUS_PENDING, PrescribedMedicine::find($meds[1]->id)->status);

        #AND A MEDICINE FROM ANOTHER RX CAN'T BE SLIPPED IN
        [$other, $other_meds] = $this->prescribe('2', [$this->medicine('Medicine Z')]);
        $this->decide($rx->id, [['id' => $other_meds[0]->id, 'decision' => 'approved', 'days_approved' => 3]])
            ->assertJson(['code' => 500, 'message' => 'One or more medicines do not belong to this prescription']);
        $this->assertEquals(PrescribedMedicine::STATUS_PENDING, PrescribedMedicine::find($other_meds[0]->id)->status);
    }

    // 14
    public function testAFailedSaveLeavesNoPartialPrescription()
    {
        $prescriptions = Prescription::count();
        $medicines = PrescribedMedicine::count();

        #THE PRESCRIPTION ROW IS WRITTEN FIRST; THE INVALID BIRTHDATE THEN FAILS PATIENT HISTORY
        $this->actingAsRole($this->userId(self::DOCTOR_ID), 'Doctor')
            ->postJson('/insert_prescription', $this->payload('2', [$this->medicine('Medicine A'), $this->medicine('Medicine B')], ['birthdate' => 'not-a-date']))
            ->assertJson(['code' => 500, 'message' => 'Something went wrong']);

        $this->assertEquals($prescriptions, Prescription::count());
        $this->assertEquals($medicines, PrescribedMedicine::count());
    }

    public function testResubmittingTouchesOnlyRejectedMedicines()
    {
        [$rx, $meds] = $this->prescribe('2', [
            $this->medicine('Medicine A'), $this->medicine('Medicine B'), $this->medicine('Medicine C'),
        ]);
        $this->decide($rx->id, [
            ['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 21],
            ['id' => $meds[1]->id, 'decision' => 'rejected', 'remarks' => 'Wrong dose'],
            ['id' => $meds[2]->id, 'decision' => 'rejected', 'remarks' => 'Double request'],
        ]);

        #CORRECT B, DROP C, ADD D
        $payload = $this->payload('2', [
            array_merge($this->medicine('Medicine B'), ['id' => $meds[1]->id, 'dosage_strength' => '2']),
            $this->medicine('Medicine D'),
        ]);
        $payload['id'] = $rx->id;
        $this->actingAsRole($this->userId(self::DOCTOR_ID), 'Doctor')->postJson('/update_prescription', $payload)->assertJson(['code' => 200]);

        $a = PrescribedMedicine::find($meds[0]->id);
        $b = PrescribedMedicine::find($meds[1]->id);
        $this->assertEquals([PrescribedMedicine::STATUS_APPROVED, 21], [$a->status, $a->days_approved], 'Approved medicine untouched');
        $this->assertEquals([PrescribedMedicine::STATUS_PENDING, '2', null, null], [$b->status, $b->dosage_strength, $b->days_approved, $b->decision_remarks]);
        $this->assertEquals(PrescribedMedicine::STATUS_WITHDRAWN, PrescribedMedicine::find($meds[2]->id)->status);
        $this->assertDatabaseHas('prescribed_medicines', ['prescription_id' => $rx->id, 'description' => 'Medicine D', 'status' => PrescribedMedicine::STATUS_PENDING]);
        $this->assertDatabaseHas('icc_reviews', ['prescription_id' => $rx->id]);
        $this->assertDatabaseMissing('returned_prescriptions', ['prescription_id' => $rx->id]);
        $this->assertEquals(Prescription::REVIEW_PARTIALLY_APPROVED, $this->reviewStatus($rx->id));

        #AN APPROVED MEDICINE CAN'T BE EDITED THROUGH A RESUBMIT
        $this->decide($rx->id, [['id' => $b->id, 'decision' => 'rejected', 'remarks' => 'Still wrong']]);
        $payload = $this->payload('2', [array_merge($this->medicine('Medicine A'), ['id' => $a->id, 'dosage_strength' => '9'])]);
        $payload['id'] = $rx->id;
        $this->actingAsRole($this->userId(self::DOCTOR_ID), 'Doctor')->postJson('/update_prescription', $payload)->assertJson(['code' => 500]);
        $this->assertEquals('1', PrescribedMedicine::find($a->id)->dosage_strength);
    }

    public function testDeletingAPartlyApprovedRxOnlyWithdrawsTheRejectedMedicines()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);
        $this->decide($rx->id, [
            ['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 7],
            ['id' => $meds[1]->id, 'decision' => 'rejected', 'remarks' => 'Wrong indication'],
        ]);

        $this->actingAsRole($this->userId(self::DOCTOR_ID), 'Doctor')->deleteJson('/prescription/delete', ['id' => $rx->id, 'remarks' => 'Not needed'])
            ->assertJson(['code' => 200, 'message' => 'Rejected medicines withdrawn']);

        $this->assertEquals(1, Prescription::find($rx->id)->status, 'Approved medicine keeps the Rx active');
        $this->assertEquals(PrescribedMedicine::STATUS_APPROVED, PrescribedMedicine::find($meds[0]->id)->status);
        $this->assertEquals(PrescribedMedicine::STATUS_WITHDRAWN, PrescribedMedicine::find($meds[1]->id)->status);
        $this->assertEquals(Prescription::REVIEW_APPROVED, $this->reviewStatus($rx->id));
    }

    public function testPharmacyDispensesOnlyApprovedMedicinesAndANurseCanReceiveTwice()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);
        $this->decide($rx->id, [['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 7]]);

        #B IS STILL PENDING, SO IT CAN'T BE DISPENSED
        $this->actingAsRole($this->userId(self::PHARMACIST_ID), 'Pharmacist')->postJson('/render_prescription', [
            'id' => $rx->id, 'remarks' => 'ok',
            'medications' => [['id' => $meds[0]->id, 'quantity_rendered' => 1], ['id' => $meds[1]->id, 'quantity_rendered' => 1]],
        ])->assertJson(['code' => 500]);
        $this->assertNull(PrescribedMedicine::find($meds[1]->id)->date_rendered);

        $this->actingAsRole($this->userId(self::PHARMACIST_ID), 'Pharmacist')->postJson('/render_prescription', [
            'id' => $rx->id, 'remarks' => 'ok', 'medications' => [['id' => $meds[0]->id, 'quantity_rendered' => 1]],
        ])->assertJson(['code' => 200]);
        $this->actingAsRole($this->userId(self::NURSE_ID), 'Nurse')->postJson('/approve_prescription', ['id' => $rx->id, 'remarks' => 'received'])
            ->assertJson(['code' => 200]);

        #SECOND CYCLE FOR B
        $this->decide($rx->id, [['id' => $meds[1]->id, 'decision' => 'approved', 'days_approved' => 3]]);
        $this->actingAsRole($this->userId(self::PHARMACIST_ID), 'Pharmacist')->postJson('/render_prescription', [
            'id' => $rx->id, 'remarks' => 'ok', 'medications' => [['id' => $meds[1]->id, 'quantity_rendered' => 1]],
        ])->assertJson(['code' => 200]);
        $this->actingAsRole($this->userId(self::NURSE_ID), 'Nurse')->postJson('/approve_prescription', ['id' => $rx->id, 'remarks' => 'received'])
            ->assertJson(['code' => 200]);

        $this->assertEquals(1, DB::table('rendered_prescriptions')->where('prescription_id', $rx->id)->count());
        $this->assertNotNull(PrescribedMedicine::find($meds[1]->id)->date_rendered);
    }

    public function testPharmacyReturnRejectsOnlyTheMedicinesWaitingToBeDispensed()
    {
        [$rx, $meds] = $this->prescribe('5', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);

        $this->actingAsRole($this->userId(self::PHARMACIST_ID), 'Pharmacist')->postJson('/reject_prescription', ['id' => $rx->id, 'remarks' => 'Out of stock'])
            ->assertJson(['code' => 200]);

        foreach ($meds as $med) {
            $fresh = PrescribedMedicine::find($med->id);
            $this->assertEquals([PrescribedMedicine::STATUS_REJECTED, 'Out of stock'], [$fresh->status, $fresh->decision_remarks]);
            $this->assertDatabaseMissing('concurrent_therapy', ['prescribed_medicine_id' => $med->id]);
        }
        $this->assertDatabaseHas('returned_prescriptions', ['prescription_id' => $rx->id]);
        $this->assertEquals(Prescription::REVIEW_REJECTED, $this->reviewStatus($rx->id));
    }

    // 15
    public function testAmsSummaryReportsEachMedicinesOwnDays()
    {
        [$rx, $meds] = $this->prescribe('2', [$this->medicine('Medicine A'), $this->medicine('Medicine B')]);
        $this->decide($rx->id, [
            ['id' => $meds[0]->id, 'decision' => 'approved', 'days_approved' => 5],
            ['id' => $meds[1]->id, 'decision' => 'approved', 'days_approved' => 10],
        ]);

        $today = now()->format('Y-m-d');
        $rows = $this->actingAsRole($this->userId(self::ICC_ID), 'ICC')
            ->getJson('/ams/summary?filter_patient=TESTPATIENT, MULTI MEDICINE&filter_description=&filter_type=&from_date=' . $today . '&to_date=' . now()->addDays(30)->format('Y-m-d'))
            ->assertJson(['code' => 200])
            ->json('results');

        $days = collect($rows)->pluck('days_approved', 'antimicrobial')->all();
        $this->assertEquals(['Medicine A' => 5, 'Medicine B' => 10], $days);

        #DATES ARE BOUND PARAMETERS NOW: AN INJECTION ATTEMPT IS JUST AN INVALID DATE
        $this->actingAsRole($this->userId(self::ICC_ID), 'ICC')
            ->getJson("/ams/summary?filter_patient=TESTPATIENT&from_date=2026-01-01') OR ('1'='1&to_date=2026-12-31")
            ->assertJson(['code' => 200]);
    }

    public function testActivityLogEscapesRemarks()
    {
        $payload = $this->payload('2', [$this->medicine('Medicine A')], ['remarks' => '<img src=x onerror=alert(1)>']);
        $this->actingAsRole($this->userId(self::DOCTOR_ID), 'Doctor')->postJson('/insert_prescription', $payload)->assertJson(['code' => 200]);

        $remarks = DB::table('prescription_logs')->where('released_by', $this->userId(self::DOCTOR_ID))->latest('id')->value('remarks');
        $this->assertStringNotContainsString('<img', $remarks);
        $this->assertStringContainsString('&lt;img', $remarks);
    }
}
