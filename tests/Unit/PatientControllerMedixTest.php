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
        Cache::forget('medix_refresh_token');

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

    public function testSearchMedixPatientReturnsEmptyResultsWhenMedixReturns404()
    {
        // MEDIX's GetPatientInformation returns HTTP 404 with body "No data found."
        // when a search has zero matches — this is MEDIX's normal "no results"
        // signal, not a real failure, and must not surface as an error.
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => Http::response('No data found.', 404),
        ]);

        $request = Request::create('/patient/medix/search', 'GET', ['lastname' => 'ZZZZNONEXISTENTNAME123']);
        $response = (new PatientController())->searchMedixPatient($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);
        $this->assertEquals([], $data['results']);
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

    public function testSearchMedixPatientCachesRefreshTokenFromLoginResponse()
    {
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600, 'refreshToken' => 'my-refresh-token'], 200),
            'medixhms-external.com/*' => Http::response([], 200),
        ]);

        (new PatientController())->searchMedixPatient(Request::create('/', 'GET', ['lastname' => 'LAGOS']));

        $this->assertEquals('my-refresh-token', Cache::get('medix_refresh_token'));
    }

    public function testGetMedixTokenUsesRefreshTokenWhenAccessTokenExpired()
    {
        $grant_types = [];
        Http::fake([
            'medixhms.com:3000/*' => function ($request) use (&$grant_types) {
                $grant_types[] = $request['grant_type'];
                if ($request['grant_type'] === 'refresh_token') {
                    return Http::response(['accessToken' => 'refreshed-token', 'expiresIn' => 3600], 200);
                }
                return Http::response(['accessToken' => 'initial-token', 'expiresIn' => 3600, 'refreshToken' => 'test-refresh-token'], 200);
            },
            'medixhms-external.com/*' => Http::response([], 200),
        ]);

        $controller = new PatientController();
        $controller->searchMedixPatient(Request::create('/', 'GET', ['lastname' => 'LAGOS']));

        // Simulate the access token expiring while the refresh token is still cached.
        Cache::forget('medix_access_token');

        $controller->searchMedixPatient(Request::create('/', 'GET', ['lastname' => 'LAGOS']));

        $this->assertEquals(['client_credentials', 'refresh_token'], $grant_types);
        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'GetPatientInformation')
                && $req->hasHeader('Authorization', 'Bearer refreshed-token');
        });
    }

    public function testGetMedixTokenFallsBackToClientCredentialsWhenRefreshFails()
    {
        $grant_types = [];
        Http::fake([
            'medixhms.com:3000/*' => function ($request) use (&$grant_types) {
                $grant_types[] = $request['grant_type'];
                if ($request['grant_type'] === 'refresh_token') {
                    return Http::response('invalid_grant', 401);
                }
                return Http::response(['accessToken' => 'cc-token', 'expiresIn' => 3600, 'refreshToken' => 'stale-refresh-token'], 200);
            },
            'medixhms-external.com/*' => Http::response([], 200),
        ]);

        $controller = new PatientController();
        $controller->searchMedixPatient(Request::create('/', 'GET', ['lastname' => 'LAGOS']));

        Cache::forget('medix_access_token');

        $controller->searchMedixPatient(Request::create('/', 'GET', ['lastname' => 'LAGOS']));

        $this->assertEquals(['client_credentials', 'refresh_token', 'client_credentials'], $grant_types);
        Http::assertSent(function ($req) {
            return str_contains($req->url(), 'GetPatientInformation')
                && $req->hasHeader('Authorization', 'Bearer cc-token');
        });
    }

    public function testGetMedixTokenDoesNotCacheRefreshTokenWhenAbsentFromResponse()
    {
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => Http::response([], 200),
        ]);

        (new PatientController())->searchMedixPatient(Request::create('/', 'GET', ['lastname' => 'LAGOS']));

        $this->assertNull(Cache::get('medix_refresh_token'));
    }
}
