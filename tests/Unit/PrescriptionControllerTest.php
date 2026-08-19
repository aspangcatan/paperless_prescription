<?php

namespace Tests\Unit;

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

class PrescriptionControllerTest extends TestCase
{
    private $test_hospital_id = 'TEST-ICC-DECIDE-0001';

    protected function setUp(): void
    {
        parent::setUp();

        $user = new User();
        $user->id = 1;
        Auth::setUser($user);
    }

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

    public function testGetPrescriptionDetailsFiltersByStatusZeroWhenProvided()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestMedicine($prescription->id, 0);
        $this->makeTestMedicine($prescription->id, 1);
        $this->makeTestMedicine($prescription->id, 2);

        $response = (new PrescriptionController())->getPrescriptionDetails(Request::create('/prescription/get/' . $prescription->id, 'GET', ['status' => 0]), $prescription->id);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);
        $this->assertCount(1, $data['results']);
        $this->assertEquals(0, $data['results'][0]['status']);
    }

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

    public function testDecideRejectsWhenMedicationDoesNotBelongToPrescription()
    {
        $prescription = $this->makeTestPrescription();
        $this->makeTestIccReview($prescription->id);

        $other_prescription = $this->makeTestPrescription();
        $unrelated_medicine = $this->makeTestMedicine($other_prescription->id);

        $request = Request::create('/icc/decide_prescription', 'POST', [
            'id' => $prescription->id,
            'hospital_id' => $this->test_hospital_id,
            'type' => '2',
            'days_approved' => 7,
            'medications' => [
                ['id' => $unrelated_medicine->id, 'decision' => 'approved', 'remarks' => ''],
            ],
        ]);

        $response = (new PrescriptionController())->decidePrescriptionMedications($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
        $this->assertEquals(0, PrescribedMedicine::find($unrelated_medicine->id)->status);
        $this->assertNull(\App\Models\PharmacyReview::where('prescription_id', $prescription->id)->first());
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

    public function testInsertPrescriptionMarksMedicinesApprovedForIccSkipTypes()
    {
        $request = Request::create('/insert_prescription', 'POST', [
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
                'type' => '5',
                'drug_allergy' => '',
                'weight' => null,
                'height' => null,
                'serum_creatinine' => '',
                'creatinine_clearance' => '',
                'transaminases' => '',
                'sgpt' => '',
                'sgot' => '',
                'days_requested' => null,
                'additional_labs' => '',
                'outcome' => '',
                'diagnosis' => '',
                'for_extension' => 0,
                'remarks' => 'New prescription',
            ],
            'indication_use' => ['description' => 'Empiric'],
            'microbiologicals' => [],
            'concurrent_therapy' => [],
            'medications' => [
                ['description' => 'TEST MEDICINE', 'unit' => 'TABLET', 'dosage_strength' => '500', 'dosage_unit' => 'MG', 'frequency' => 'BID', 'quantity_ordered' => 0],
            ],
        ]);

        $response = (new PrescriptionController())->insertPrescription($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);

        $prescription = Prescription::where('hospital_id', $this->test_hospital_id)->first();
        $medicine = PrescribedMedicine::where('prescription_id', $prescription->id)->first();
        $this->assertEquals(1, $medicine->status);
        $this->assertNotNull(\App\Models\PharmacyReview::where('prescription_id', $prescription->id)->first());
    }

    public function testInsertPrescriptionLeavesMedicinesPendingForIccReviewTypes()
    {
        $request = Request::create('/insert_prescription', 'POST', [
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
                'weight' => null,
                'height' => null,
                'serum_creatinine' => '',
                'creatinine_clearance' => '',
                'transaminases' => '',
                'sgpt' => '',
                'sgot' => '',
                'days_requested' => null,
                'additional_labs' => '',
                'outcome' => '',
                'diagnosis' => '',
                'for_extension' => 0,
                'remarks' => 'New prescription',
            ],
            'indication_use' => ['description' => 'Definitive'],
            'microbiologicals' => [['date' => '2026-08-19', 'specimen' => 'Blood', 'pathogen' => 'E. coli']],
            'concurrent_therapy' => [],
            'medications' => [
                ['description' => 'TEST MEDICINE', 'unit' => 'TABLET', 'dosage_strength' => '500', 'dosage_unit' => 'MG', 'frequency' => 'BID', 'quantity_ordered' => 0],
            ],
        ]);

        $response = (new PrescriptionController())->insertPrescription($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);

        $prescription = Prescription::where('hospital_id', $this->test_hospital_id)->first();
        $medicine = PrescribedMedicine::where('prescription_id', $prescription->id)->first();
        $this->assertEquals(0, $medicine->status);
        $this->assertNull(\App\Models\PharmacyReview::where('prescription_id', $prescription->id)->first());
    }
}
