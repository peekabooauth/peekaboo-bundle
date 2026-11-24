# Changelog

All notable changes to `peekabooauth/peekaboo-bundle`. Two lines are maintained: `main` (PHP 8.4+, Symfony 7.4+/8.0+)
and `1.8` (PHP 8.1+, Symfony 6.2–6.4); a 1.8.x entry after 1.9.0 is a backport and names its `main` counterpart.
The `## Upgrading to X.Y.Z` sections in the README have migration details. Format:
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [1.12.1] - 2026-11-24
### Fixed
- Log Peekaboo's HTTP 429 ("Too many failed API key attempts") on `/api/user-by-key` as a `peekaboo_auth_error` **warning**, like 401/404, instead of an error. It means a caller keeps sending wrong API keys (its credentials, not a fault), and at ERROR it reached Sentry on every request while the caller was throttled. 403, 5xx and unexpected statuses stay errors.

## [1.12.0] - 2026-11-24
### Changed
- **BREAKING:** `UserDTO::getRoles()` no longer adds `ROLE_USER`; a user holds exactly the roles Peekaboo granted for the application. Before, every authenticated identity got `ROLE_USER` on top: API-key and basic-auth machine users (Prometheus included), `ROLE_DISABLED` users and users with no role in the application, so a `ROLE_USER` access rule, `isGranted('ROLE_USER')` or a `ROLE_USER: [...]` hierarchy entry let all of them in. Apps that gate on `ROLE_USER` must grant it through `role_hierarchy` (e.g. `ROLE_MANAGER: [ ROLE_USER ]`) or in Peekaboo; see "Upgrading to 1.12.0" in the README.
- `getRoles()` always returns a list (Peekaboo's object-shaped `{"1":"ROLE_X"}` roles are re-indexed).

## [1.11.2] - 2026-11-23
### Fixed
- Strip the `Bearer ` scheme (any case) from the `Authorization` header before calling Peekaboo. Previously every `Authorization: Bearer <jwt>` request, as sent by OAuth clients, failed with 401/403 without reaching Peekaboo. A bare token in the header and the `bearer` query/body parameter work as before.

## [1.11.1] - 2026-11-19
### Fixed
- Stop the endless `/peekaboo/auth` redirect loop when the login code exchange keeps failing (Peekaboo unreachable, wrong `PEEKABOO_APPLICATION_SECRET`). The first failure still retries once; a second failure in a row shows a plain "Login failed" page (HTTP 503) and logs `peekaboo_login_failed`. The counter is the 5-minute `__peekaboo_auth_failures` cookie.
- `AuthController::auth()` now takes the `Request`, and the constructor gained an optional logger (matters only if you extend or decorate the controller).

## [1.11.0] - 2026-11-18
### Added
- JWT expiry handling: the bundle reads the token's `exp` claim (without verifying the signature) and rejects an expired JWT locally, so the user is sent to log in again and Peekaboo no longer logs a failed `jwt` login for every expired token.

### Changed
- The `__peekaboo_token` cookie now expires together with the JWT (still at most 24 hours), and a cached user is never kept past the token's expiry.

## [1.10.1] - 2026-11-17
### Fixed
- Stop the endless `/peekaboo/auth` redirect loop after Peekaboo rejects a stored token: a failed login now also expires the `__peekaboo_token` cookie, and `/peekaboo/auth` no longer runs cookie/session authentication (bearer, API key and basic credentials are still checked there).
- Log rejected credentials (Peekaboo answering 401 or 404) as a warning instead of an error; 5xx, 403 and other failures stay errors.

## [1.10.0] - 2026-11-16
### Added
- Send the bundle version (`v` parameter on the login redirect) and a `User-Agent: peekaboo-bundle/<version>` header on every request to Peekaboo.
- Forward the user's IP (`x-client-ip`) when exchanging the login code, and log failed code exchanges.

### Changed
- **BREAKING:** SSO login now uses a one-time code. After login the app receives `__peekaboo_code` and exchanges it server-to-server (`/api/token-by-code`) for the JWT, so the JWT no longer travels in the URL. Requires a Peekaboo server that has `/api/token-by-code`; against an older server login fails with "wrong signature". A `__peekaboo_token` query parameter is still accepted as a fallback.
- `TokenStorage`'s constructor gained `Client` and an optional logger (matters only if you construct or decorate it yourself).

## [1.9.0] - 2026-10-11
### Fixed
- Send `app` and `signature` with API key lookups, so Peekaboo only looks a key up within this application.

## [1.8.9] - 2026-11-24
Maintenance release on the 1.8 line (Symfony 6.4 / PHP 8.1).
### Changed
- **BREAKING:** Backport of 1.12.0: `UserDTO::getRoles()` no longer adds `ROLE_USER`.

## [1.8.8] - 2026-11-23
Maintenance release on the 1.8 line (Symfony 6.4 / PHP 8.1).
### Fixed
- Backport of 1.11.2: strip the `Bearer ` scheme from the `Authorization` header before calling Peekaboo.

## [1.8.7] - 2026-11-19
Maintenance release on the 1.8 line (Symfony 6.4 / PHP 8.1).
### Fixed
- Backport of 1.11.1: stop the `/peekaboo/auth` loop when the login code exchange keeps failing (second failure shows a 503 "Login failed" page).

## [1.8.6] - 2026-11-18
Maintenance release on the 1.8 line (Symfony 6.4 / PHP 8.1).
### Added
- Backport of 1.11.0: reject expired JWTs locally and end the cookie and user cache with the token's expiry.

## [1.8.5] - 2026-11-17
Maintenance release on the 1.8 line (Symfony 6.4 / PHP 8.1).
### Changed
- Backport: log rejected credentials (Peekaboo 401/404) as a warning instead of an error.

## [1.8.4] - 2026-11-17
Maintenance release on the 1.8 line (Symfony 6.4 / PHP 8.1).
### Fixed
- Backport of 1.10.1: stop the `/peekaboo/auth` redirect loop after a rejected token; API credentials are still checked on that route.

## [1.8.3] - 2026-11-16
Maintenance release on the 1.8 line (Symfony 6.4 / PHP 8.1).
### Changed
- Backport of 1.10.0: **BREAKING** one-time login code exchange (`__peekaboo_code`, requires a Peekaboo server with `/api/token-by-code`), signed bundle version on login redirect, `User-Agent` header, client IP forwarded on code exchange.

## [1.8.2] - 2026-10-12
Maintenance release on the 1.8 line (Symfony 6.4 / PHP 8.1).
### Changed
- **BREAKING:** new legacy line for applications that cannot move to PHP 8.4 / Symfony 7.4: requires PHP `^8.1` and Symfony `^6.2` (6.2-6.4), adds `symfony/yaml`, and uses `Routing\Annotation\Route`. Behaviour is the same as 1.9.0.

## [1.8.1] - 2026-03-06
### Changed
- Internal: upgrade PHPUnit to 13.0 (dev dependency).

## [1.8.0] - 2026-03-06
### Added
- `PEEKABOO_DEV_ROLES` env var (comma-separated) overrides the roles of the dev user returned in dev mode (`IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.dev`). Default roles are unchanged.

## [1.7.0] - 2026-01-19
### Fixed
- Symfony 8 compatibility: the logout listener works with any `FirewallMapInterface`, not only the concrete `FirewallMap`.

## [1.6.0] - 2026-01-19
### Added
- `symfony/security-bundle` is now a declared dependency.
### Changed
- Remove deprecation warnings; rewrite the README (requirements, authentication methods and their priority, env variables).

## [1.5.0] - 2026-01-09
### Changed
- **BREAKING:** require PHP `>=8.4` and Symfony `^7.4|^8.0`; `psr/log` `^3.0` only. Supports Symfony 8.0.
- The one-time `__peekaboo_token` is read from the query string only, and `UserProvider::loadUserByIdentifier()` takes a required string.

## [1.4.1] - 2025-01-12
### Fixed
- PHP 8.4 compatibility (explicit nullable parameter types).

## [1.4.0] - 2024-12-23
### Changed
- **BREAKING:** the argument resolver uses `ValueResolverInterface` instead of the deprecated `ArgumentValueResolverInterface`; require Symfony `^6.2|^7.0`.

## [1.3.6] - 2024-02-23
### Changed
- Same commit as 1.3.5 (no changes).

## [1.3.5] - 2024-02-23
### Fixed
- Roll back 1.3.4: the argument resolver is back on `ArgumentValueResolverInterface`, so Symfony 6.1 keeps working.

## [1.3.4] - 2024-02-23
### Changed
- Argument resolver moved to `ValueResolverInterface` (the old interface is removed in Symfony 7.0); rolled back in 1.3.5 because it needs Symfony 6.2.

## [1.3.3] - 2024-02-09
### Fixed
- Send the `x-api-key` header again when looking up a user by API key (it was dropped in 1.2.4, so API key auth failed).

## [1.3.2] - 2024-01-26
### Fixed
- Symfony 7: `UserDTO::eraseCredentials()` declares a `void` return type.

## [1.3.1] - 2024-01-26
### Fixed
- Symfony 7: route loading uses `type: attribute` instead of `annotation`.

## [1.3.0] - 2024-01-26
### Added
- Allow Symfony 7 (`^6.1|^7.0`).

## [1.2.7] - 2023-11-24
### Changed
- Docs only: describe `PEEKABOO_AUTOLOGIN`.

## [1.2.6] - 2023-09-28
### Fixed
- `PEEKABOO_AUTOLOGIN` is optional again (an empty value no longer fails validation).

## [1.2.5] - 2023-09-22
### Added
- `PEEKABOO_AUTOLOGIN` env var (`google`, `facebook`, `google_js`, `facebook_js`) is sent to Peekaboo with the login redirect to skip the login form or auto-click the social login button.

## [1.2.4] - 2023-09-21
### Added
- Send the client IP (`x-client-ip`) to Peekaboo when loading a user by JWT or API key.
### Fixed
- Regression: the `x-api-key` header was no longer sent on API key lookups (fixed in 1.3.3).

## [1.2.3] - 2023-09-13
### Changed
- Allow `psr/log` 1, 2 and 3.

## [1.2.1] - 2023-03-19
### Fixed
- Dev mode (`IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.dev`) is also honoured by the authenticator and token storage: a dev token is always present, so no redirect to the identity server happens.

## [1.2.0] - 2023-03-19
### Added
- Local dev mode: with `IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.dev` the bundle returns a built-in dev user (`admin@localhost.net`, roles `ROLE_ADMIN`, `ROLE_USER`, `ROLE_API`, `ROLE_DEV`) without calling the identity server.
- Internal: PHPUnit test suite.

## [1.1.10] - 2023-01-16
### Changed
- 401 and 403 responses are JSON (`{"error": "unauthorized"}` / `{"error": "forbidden"}`) for JSON/XHR requests and short plain text otherwise.

## [1.1.9] - 2023-01-12
### Fixed
- Read the API key from the `X-Api-Key` header in either letter case.

## [1.1.8] - 2023-01-12
### Changed
- **BREAKING:** the API key query/body parameter is now `apikey` instead of `x-api-key` (the `x-api-key` header is unchanged).

## [1.1.7] - 2023-01-11
### Changed
- Allow `psr/log` 2.0 (`>=2.0`).

## [1.1.6] - 2023-01-04
### Fixed
- Logout also deletes the `__peekaboo_token` cookie.

## [1.1.5] - 2023-01-03
### Changed
- The token received after login is also stored in an HTTP-only `__peekaboo_token` cookie (24 hours), so login survives apps without a persistent session; a failed login redirects back to Peekaboo.
- Cache of loaded users raised from 5 to 10 minutes; failed logins are logged as `peekaboo_bad_auth`.

## [1.1.4] - 2022-12-21
### Added
- Basic auth: `?auth=basic` makes the server ask the browser for credentials (`WWW-Authenticate`), so Basic auth also works from a browser, not only from CLI clients.

## [1.1.3] - 2022-12-21
### Fixed
- `Authorization: Basic ...` is no longer taken by the JWT loader; only the basic-auth loader handles it.

## [1.1.2] - 2022-12-20
### Added
- Basic auth user loader: the HTTP basic username (and optional password) is used as the API key; the loaded user is cached for 5 minutes.

## [1.1.1] - 2022-12-16
### Changed
- Code style only (no behaviour change intended).

## [1.1.0] - 2022-12-07
### Added
- User loaders with priority (JWT bearer token, API key, token storage/session) behind a `peekaboo.user.loader` service tag; API keys sent via the `x-api-key` header are resolved to a user.
- Requests to Peekaboo and the login redirect are signed with `PEEKABOO_APPLICATION_SECRET` (`signature` parameter).
### Changed
- **BREAKING:** the secret is no longer sent in the redirect (`secret` replaced by `signature`); requires a matching Peekaboo server.

## [1.0.18] - 2022-11-25
### Fixed
- Remove debug output from failed API auth; log it as a `peekaboo_bad_auth` warning and answer a plain 403.

## [1.0.17] - 2022-11-25
### Changed
- One authenticator now handles both browser (token/session) and API (bearer) authentication; `ApiPeekabooAuthenticator` and `ApiUserProvider` are removed.

## [1.0.16] - 2022-11-24
### Changed
- Replace `guzzlehttp/guzzle` with `symfony/http-client` (and `psr/log`); the JWT is sent as an `Authorization` bearer header, and non-200 answers from Peekaboo are logged.

## [1.0.15] - 2022-11-24
### Added
- API requests can authenticate with a login token too (`ApiPeekabooAuthenticator` extends the normal authenticator).

## [1.0.14] - 2022-11-23
### Fixed
- Revert 1.0.13: the JWT is sent to Peekaboo as `?bearer=` in the URL again.

## [1.0.13] - 2022-11-23
### Changed
- The JWT is sent to Peekaboo in the `Authorization: Bearer` header instead of the URL (reverted in 1.0.14); the API authenticator also accepts `bearer` in the query or body.

## [1.0.12] - 2022-11-08
### Fixed
- Do not break when there is no session (for example in console commands).

## [1.0.11] - 2022-11-08
### Fixed
- Do not break when there is no request (for example in console commands).

## [1.0.10] - 2022-08-18
### Fixed
- Redirect back to the previously requested URL after login (target path lookup falls back to the default `peekaboo` firewall).
### Changed
- Declare all used Symfony libraries in `composer.json` (`^6.1`).

## [1.0.9] - 2022-08-10
### Added
- Default values for env variables (`IDENTITY_SERVER_URL_EXTERNAL`, `IDENTITY_SERVER_AUTH_PATH`, `IDENTITY_SERVER_LOGOUT_PATH`, `ROUTE_AFTER_REDIRECT`, `IDENTITY_SERVER_URL_INTERNAL`, `PEEKABOO_APPLICATION_NAME`).

## [v1.0.8] - 2022-08-03
### Fixed
- Remove the leftover duplicate user provider.

## [v1.0.6] - 2022-08-02
### Added
- API authentication: `ApiPeekabooAuthenticator` and `ApiUserProvider` authenticate requests carrying a JWT in the `Authorization` header.

## [v1.0.5] - 2022-07-15
### Changed
- The token cookie/session name is fixed to `__peekaboo_token`; the `JWT_TOKEN_NAME` env var is no longer used.

## [v1.0.4] - 2022-07-13
### Added
- Roles are loaded per application (`/api/user/<PEEKABOO_APPLICATION_NAME>`); the user has a `name`.

## [v1.0.3] - 2022-07-12
### Added
- `PEEKABOO_APPLICATION_NAME` and `PEEKABOO_APPLICATION_SECRET` env vars identify the application in the login redirect.

## [v1.0.2] - 2022-06-28
### Changed
- **BREAKING:** package renamed to `peekabooauth/peekaboo-bundle`, namespace `Gupalo\PeekabooBundle` to `Peekabooauth\PeekabooBundle`.
### Added
- `/peekaboo/logout` route, `IDENTITY_SERVER_LOGOUT_PATH` and `ROUTE_AFTER_REDIRECT` env vars.

## [v1.0.1] - 2022-06-25
### Added
- Symfony Security integration: `PeekabooAuthenticator`, `UserProvider` that refreshes the user from the identity server, token storage and redirect builders.

## [v1.0.0] - 2022-06-23
### Added
- Initial release: login redirect to the Peekaboo identity server, `/peekaboo/auth` callback controller, user DTO and a Guzzle client.
