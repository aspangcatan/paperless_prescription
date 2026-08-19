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
}
