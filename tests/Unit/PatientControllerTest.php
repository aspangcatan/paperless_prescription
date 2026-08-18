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
