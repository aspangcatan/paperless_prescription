# MEDIX Token Refresh & 401 Retry Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `PatientController` reuse MEDIX's refresh token instead of always re-logging in with `client_credentials`, and recover automatically from a `401` on the patient-search call by retrying once with a fresh token.

**Architecture:** All changes are in `app\Http\Controllers\PatientController.php`. `getMedixToken()` is restructured around a new private helper `requestMedixToken(array $form_params)` that posts to the token endpoint and caches both the access token and (if present) the refresh token; `getMedixToken()` tries a cached refresh token first on a cache miss, falling back to `client_credentials`. `searchMedixPatient()` gets a new private helper `getMedixPatientInformation($token, $query)` (extracted so the GET call isn't duplicated) and a retry-once-on-401 branch.

**Tech Stack:** Laravel 8, `Illuminate\Support\Facades\Http` (faked in tests via `Http::fake`), `Illuminate\Support\Facades\Cache` (array driver in tests, file driver in production), PHPUnit.

**Spec:** [2026-08-20-medix-token-refresh-design.md](../specs/2026-08-20-medix-token-refresh-design.md)

---

### Task 1: Refresh-token support in `getMedixToken()`

**Files:**
- Modify: `app/Http/Controllers/PatientController.php:22-49` (the `getMedixToken()` method)
- Test: `tests/Unit/PatientControllerMedixTest.php`

- [ ] **Step 1: Add `Cache::forget('medix_refresh_token')` to test `setUp()`**

The array cache driver persists across test methods within a single PHPUnit run (it's per-process, not per-test), so the new `medix_refresh_token` cache key needs the same reset-in-setUp treatment `medix_access_token` already gets.

In `tests/Unit/PatientControllerMedixTest.php`, change:

```php
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('medix_access_token');

        config([
```

to:

```php
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('medix_access_token');
        Cache::forget('medix_refresh_token');

        config([
```

- [ ] **Step 2: Write the failing tests**

Append these four test methods to `tests/Unit/PatientControllerMedixTest.php`, just before the final closing `}` of the class:

```php
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
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `php artisan test --filter=PatientControllerMedixTest`

Expected: The four new tests FAIL (refresh-token behavior doesn't exist yet — `testGetMedixTokenUsesRefreshTokenWhenAccessTokenExpired` and `testGetMedixTokenFallsBackToClientCredentialsWhenRefreshFails` will see `$grant_types` containing only `'client_credentials'` entries, never `'refresh_token'`). The pre-existing tests in this file should still PASS.

- [ ] **Step 4: Implement `requestMedixToken()` and refactor `getMedixToken()`**

In `app/Http/Controllers/PatientController.php`, replace the entire current `getMedixToken()` method (lines 22-49):

```php
    private function getMedixToken()
    {
        $token = Cache::get('medix_access_token');
        if ($token) {
            return $token;
        }

        // Medix's server presents IIS's auto-generated self-signed default cert
        // (CN WMSvc-SHA2-LENOVO-LA0X1192), not one issued for their domain — a
        // vendor-side misconfiguration, not fixable via a local CA bundle update.
        $response = Http::withOptions(['verify' => false])->asForm()->post(config('services.medix.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.medix.client_id'),
            'client_secret' => config('services.medix.client_secret'),
        ]);

        if (!$response->successful()) {
            return null;
        }

        $access_token = $response->json('accessToken');
        $expires_in = $response->json('expiresIn');
        $ttl = $expires_in ? max($expires_in - 60, 60) : 300;

        Cache::put('medix_access_token', $access_token, $ttl);

        return $access_token;
    }
```

with:

```php
    private function getMedixToken()
    {
        $token = Cache::get('medix_access_token');
        if ($token) {
            return $token;
        }

        $refresh_token = Cache::get('medix_refresh_token');
        if ($refresh_token) {
            $refreshed = self::requestMedixToken([
                'grant_type' => 'refresh_token',
                'client_id' => config('services.medix.client_id'),
                'client_secret' => config('services.medix.client_secret'),
                'refresh_token' => $refresh_token,
            ]);
            if ($refreshed) {
                return $refreshed;
            }
        }

        return self::requestMedixToken([
            'grant_type' => 'client_credentials',
            'client_id' => config('services.medix.client_id'),
            'client_secret' => config('services.medix.client_secret'),
        ]);
    }

    private function requestMedixToken($form_params)
    {
        // Medix's server presents IIS's auto-generated self-signed default cert
        // (CN WMSvc-SHA2-LENOVO-LA0X1192), not one issued for their domain — a
        // vendor-side misconfiguration, not fixable via a local CA bundle update.
        $response = Http::withOptions(['verify' => false])->asForm()->post(config('services.medix.token_url'), $form_params);

        if (!$response->successful()) {
            return null;
        }

        $access_token = $response->json('accessToken');
        $expires_in = $response->json('expiresIn');
        $refresh_token = $response->json('refreshToken');
        $ttl = $expires_in ? max($expires_in - 60, 60) : 300;

        Cache::put('medix_access_token', $access_token, $ttl);
        if ($refresh_token) {
            Cache::put('medix_refresh_token', $refresh_token, 60 * 60 * 24 * 7);
        }

        return $access_token;
    }
```

- [ ] **Step 5: Run the full test suite to verify everything passes**

Run: `php artisan test`

Expected: All tests PASS, including the 4 new ones and every pre-existing test in `PatientControllerMedixTest` and `PatientControllerTest`.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PatientController.php tests/Unit/PatientControllerMedixTest.php
git commit -m "feat: use MEDIX refresh token before falling back to client_credentials"
```

---

### Task 2: Retry once on a 401 in `searchMedixPatient()`

**Files:**
- Modify: `app/Http/Controllers/PatientController.php:51-85` (the `searchMedixPatient()` method, as left after Task 1)
- Test: `tests/Unit/PatientControllerMedixTest.php`

- [ ] **Step 1: Write the failing tests**

Append these three test methods to `tests/Unit/PatientControllerMedixTest.php`, just before the final closing `}` of the class:

```php
    public function testSearchMedixPatientRetriesOnceOn401AndSucceeds()
    {
        $search_call_count = 0;
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => function () use (&$search_call_count) {
                $search_call_count++;
                if ($search_call_count === 1) {
                    return Http::response('Unauthorized', 401);
                }
                return Http::response([['hospitalNo' => '00000744', 'lastName' => 'LAGOS']], 200);
            },
        ]);

        $request = Request::create('/', 'GET', ['lastname' => 'LAGOS']);
        $response = (new PatientController())->searchMedixPatient($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $data['code']);
        $this->assertCount(1, $data['results']);
        $this->assertEquals(2, $search_call_count);
    }

    public function testSearchMedixPatientReturnsErrorWhenRetryAlsoReturns401()
    {
        $search_call_count = 0;
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => function () use (&$search_call_count) {
                $search_call_count++;
                return Http::response('Unauthorized', 401);
            },
        ]);

        $request = Request::create('/', 'GET', ['lastname' => 'LAGOS']);
        $response = (new PatientController())->searchMedixPatient($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
        $this->assertEquals(2, $search_call_count);
    }

    public function testSearchMedixPatientDoesNotRetryOnNon401Failure()
    {
        $search_call_count = 0;
        Http::fake([
            'medixhms.com:3000/*' => Http::response(['accessToken' => 'test-token', 'expiresIn' => 3600], 200),
            'medixhms-external.com/*' => function () use (&$search_call_count) {
                $search_call_count++;
                return Http::response('Server error', 500);
            },
        ]);

        $request = Request::create('/', 'GET', ['lastname' => 'LAGOS']);
        $response = (new PatientController())->searchMedixPatient($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(500, $data['code']);
        $this->assertEquals(1, $search_call_count);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=PatientControllerMedixTest`

Expected: `testSearchMedixPatientRetriesOnceOn401AndSucceeds` and `testSearchMedixPatientReturnsErrorWhenRetryAlsoReturns401` FAIL (no retry logic exists yet, so `$search_call_count` stops at 1 and the first test gets `code => 500` instead of `200`). `testSearchMedixPatientDoesNotRetryOnNon401Failure` should already PASS (nothing to regress there yet), which is fine — it's a regression guard for the step that follows.

- [ ] **Step 3: Implement the retry**

In `app/Http/Controllers/PatientController.php`, replace the entire current `searchMedixPatient()` method (as left after Task 1):

```php
    public function searchMedixPatient(Request $request)
    {
        try {
            if (empty($request->lastname)) {
                return response(['code' => 500, 'message' => 'Lastname is required']);
            }

            $token = self::getMedixToken();
            if (!$token) {
                return response(['code' => 500, 'message' => 'Unable to authenticate with MEDIX']);
            }

            $query = ['LastName' => $request->lastname];
            if (!empty($request->firstname)) {
                $query['FirstName'] = $request->firstname;
            }

            // See the TLS comment in getMedixToken() above.
            $response = Http::withOptions(['verify' => false])->withToken($token)->get(config('services.medix.base_url') . '/GetPatientInformation', $query);

            // MEDIX responds 404 "No data found." for a search with zero matches —
            // that's their normal empty-result signal, not a failure.
            if ($response->status() === 404) {
                return response(['code' => 200, 'results' => []]);
            }

            if (!$response->successful()) {
                return response(['code' => 500, 'message' => 'MEDIX request failed']);
            }

            return response(['code' => 200, 'results' => $response->json()]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }
```

with:

```php
    public function searchMedixPatient(Request $request)
    {
        try {
            if (empty($request->lastname)) {
                return response(['code' => 500, 'message' => 'Lastname is required']);
            }

            $token = self::getMedixToken();
            if (!$token) {
                return response(['code' => 500, 'message' => 'Unable to authenticate with MEDIX']);
            }

            $query = ['LastName' => $request->lastname];
            if (!empty($request->firstname)) {
                $query['FirstName'] = $request->firstname;
            }

            $response = self::getMedixPatientInformation($token, $query);

            if ($response->status() === 401) {
                Cache::forget('medix_access_token');
                $token = self::getMedixToken();
                if (!$token) {
                    return response(['code' => 500, 'message' => 'Unable to authenticate with MEDIX']);
                }
                $response = self::getMedixPatientInformation($token, $query);
            }

            // MEDIX responds 404 "No data found." for a search with zero matches —
            // that's their normal empty-result signal, not a failure.
            if ($response->status() === 404) {
                return response(['code' => 200, 'results' => []]);
            }

            if (!$response->successful()) {
                return response(['code' => 500, 'message' => 'MEDIX request failed']);
            }

            return response(['code' => 200, 'results' => $response->json()]);
        } catch (\Exception $exception) {
            return response(['code' => 500, 'message' => $exception->getMessage()]);
        }
    }

    private function getMedixPatientInformation($token, $query)
    {
        // See the TLS comment in requestMedixToken() above.
        return Http::withOptions(['verify' => false])->withToken($token)->get(config('services.medix.base_url') . '/GetPatientInformation', $query);
    }
```

- [ ] **Step 4: Run the full test suite to verify everything passes**

Run: `php artisan test`

Expected: All tests PASS, including the 3 new ones from this task, the 4 from Task 1, and every other pre-existing test in the suite.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PatientController.php tests/Unit/PatientControllerMedixTest.php
git commit -m "feat: retry MEDIX patient search once on a 401"
```
