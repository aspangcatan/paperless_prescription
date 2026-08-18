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
}
