# MEDIX Token Refresh & 401 Retry — Design

## Purpose

`PatientController::getMedixToken()` currently re-authenticates from scratch (`grant_type=client_credentials`) every time its cache entry expires, even though MEDIX's response already includes a `refreshToken` that's never used. It also has no recovery path if MEDIX invalidates a token earlier than the cache's TTL expects — a search just fails with a generic error until the cache naturally expires. This adds proper refresh-token usage and a one-time retry on a `401` from the actual search call.

## Background

Confirmed via a live Postman request against MEDIX's token endpoint (`POST https://medixhms.com:3000/api/auth/identity/token`) that it accepts a second grant type:
```
grant_type=refresh_token
client_id=<...>
client_secret=<...>
refresh_token=<...>
```
This lines up with the `refreshToken` field already present in MEDIX's `client_credentials` response (captured in the original MEDIX integration's design doc) — the app has always received a refresh token, just never stored or used it.

## Changes

All in `app\Http\Controllers\PatientController.php` — no schema, route, or frontend changes.

### `getMedixToken()` — restructured around a shared token-request helper

Extract a new private helper, `requestMedixToken(array $form_params)`, that does what `getMedixToken()`'s HTTP call does today (POST to `services.medix.token_url` with `Http::withOptions(['verify' => false])` per the existing TLS note), but caches **both** tokens from the response:
- `medix_access_token` — cached for `expires_in - 60` seconds (existing logic, floor 300s if `expires_in` is missing).
- `medix_refresh_token` — cached for a fixed 7-day TTL if MEDIX returns a `refreshToken` in the response. This duration is a conservative guess (MEDIX doesn't document refresh-token lifetime) — safe either way, since a refresh attempt against a token MEDIX has actually invalidated just fails and falls through to a full login, so guessing wrong only costs an extra login call, never a broken state.

Returns the new access token, or `null` on failure — same contract as today.

`getMedixToken()` becomes:
1. Return the cached access token if present (unchanged).
2. Otherwise, if a `medix_refresh_token` is cached, call `requestMedixToken` with `grant_type=refresh_token`. If that succeeds, return the new access token.
3. Otherwise (no cached refresh token, or the refresh attempt failed), call `requestMedixToken` with `grant_type=client_credentials` (today's only path) and return its result.

### `searchMedixPatient()` — retry once on a 401

The actual `GetPatientInformation` call gets extracted into a small private helper (`getMedixPatientInformation($token, $query)`) so it can be called twice without duplicating the request. After the first call: if the response status is `401`, clear `medix_access_token` from cache, get a fresh token via `getMedixToken()` (which will itself try the refresh-token path first), and retry the search once with the new token. Every other status (404, other failures) behaves exactly as it does today — only a 401 triggers this, so a genuine MEDIX outage or malformed request doesn't become a retry loop.

## Out of Scope

- No change to the existing `expires_in - 60` / 300s-floor access-token TTL logic.
- No handling beyond a single retry — if the retried request also 401s, it's reported as a normal failure (`"MEDIX request failed"`), same as any other non-2xx response.
- No change to how `searchMedixPatient` handles a missing `lastname` or a 404 (empty results).
