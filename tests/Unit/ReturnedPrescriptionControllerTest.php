<?php

namespace Tests\Unit;

use App\Http\Controllers\ReturnedPrescriptionController;
use App\Models\IccReview;
use App\Models\PatientHistory;
use App\Models\PrescribedMedicine;
use App\Models\Prescription;
use App\Models\ReturnedPrescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ReturnedPrescriptionControllerTest extends TestCase
{
    private $test_hospital_id = 'TEST-RETURNED-UPDATE-0001';

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
                'weight' => null,
                'height' => null,
                'serum_creatinine' => '',
                'creatinine_clearance' => '',
                'transaminases' => '',
                'sgpt' => '',
                'sgot' => '',
                'days_requested' => null,
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
                'weight' => null,
                'height' => null,
                'serum_creatinine' => '',
                'creatinine_clearance' => '',
                'transaminases' => '',
                'sgpt' => '',
                'sgot' => '',
                'days_requested' => null,
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

    public function testUpdatePrescriptionMarksResubmittedMedicinesApprovedForIccSkipTypes()
    {
        $prescription = $this->makeTestPrescription('5');
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

        $resubmitted_medicine = $remaining_medicines->firstWhere('description', 'NEW REJECTED MEDICINE');
        $this->assertNotNull($resubmitted_medicine);
        $this->assertEquals(1, $resubmitted_medicine->status);
        $this->assertFalse($remaining_medicines->contains('description', 'OLD REJECTED MEDICINE'));

        $this->assertNotNull(\App\Models\PharmacyReview::where('prescription_id', $prescription->id)->first());
    }
}
