Peekaboo Bundle
===============

Symfony bundle for authentication integration with the Peekaboo identity server.

## Requirements

- PHP 8.4+
- Symfony 7.4+ or 8.0+

## Install

```bash
composer require peekabooauth/peekaboo-bundle
```

Add to `config/bundles.php`

```php
Peekabooauth\PeekabooBundle\PeekabooBundle::class => ['all' => true]
```

Add to `config/routes/annotations.yaml`

```yaml
peekaboo:
    resource: '@PeekabooBundle/Resources/config/routes.yaml'
```

Add to `config/packages/security.yaml`

```yaml
#......
providers:
    peekaboo_user_provider:
        id: Peekabooauth\PeekabooBundle\UserProvider\UserProvider
#......
firewalls:
    peekaboo:
        pattern: ^/
        custom_authenticators:
            - Peekabooauth\PeekabooBundle\Security\PeekabooAuthenticator
        provider: peekaboo_user_provider
#.......
access_control:
    - { path: ^/peekaboo/auth, roles: PUBLIC_ACCESS }
```

## Authentication Methods

The bundle supports multiple authentication methods, checked in priority order:

| Priority | Method     | How to use                                                               |
|----------|------------|--------------------------------------------------------------------------|
| 1        | JWT Token  | `Authorization: Bearer <token>` header, or `bearer` query/body parameter |
| 2        | API Key    | `x-api-key` header, or `apikey` query/body parameter                     |
| 3        | Basic Auth | `Authorization: Basic <credentials>` header                              |
| 4        | Session    | Automatic after OAuth redirect flow                                      |

### JWT Token Authentication

```bash
# Via Authorization header
curl -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIs..." https://app.example.com/api/endpoint

# Via query parameter
curl "https://app.example.com/api/endpoint?bearer=eyJhbGciOiJIUzI1NiIs..."
```

### API Key Authentication

```bash
# Via x-api-key header
curl -H "x-api-key: your-api-key" https://app.example.com/api/endpoint

# Via query parameter
curl "https://app.example.com/api/endpoint?apikey=your-api-key"
```

### Basic Auth

```bash
curl -u "username:password" https://app.example.com/api/endpoint
```

### Getting a JWT Token

```bash
curl -X POST -H "Content-Type: application/json" \
    https://peekabooauth.com/api/login_check \
    -d '{"username":"user@example.com","password":"123456"}'
```

## Environment Variables

| Variable                       | Description                                                  | Default                    |
|--------------------------------|--------------------------------------------------------------|----------------------------|
| `IDENTITY_SERVER_URL_EXTERNAL` | Public URL of identity server (for browser redirects)        | `https://peekabooauth.com` |
| `IDENTITY_SERVER_URL_INTERNAL` | Internal URL of identity server (for server-to-server calls) | `https://peekabooauth.com` |
| `IDENTITY_SERVER_AUTH_PATH`    | Path to authentication endpoint                              | `/identity/auth`           |
| `IDENTITY_SERVER_LOGOUT_PATH`  | Path to logout endpoint                                      | `/identity/logout`         |
| `ROUTE_AFTER_REDIRECT`         | Route name to redirect after successful auth                 | `homepage`                 |
| `PEEKABOO_APPLICATION_NAME`    | Your application name registered with Peekaboo               | `dev`                      |
| `PEEKABOO_APPLICATION_SECRET`  | Your application secret                                      | (required)                 |
| `PEEKABOO_AUTOLOGIN`           | Auto-login provider (see below)                              | (empty)                    |
| `PEEKABOO_DEV_ROLES`           | Override dev user roles (comma-separated)                    | (empty = default roles)    |

Example `.env`:

```bash
IDENTITY_SERVER_URL_EXTERNAL=https://peekabooauth.com
IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.com
IDENTITY_SERVER_AUTH_PATH=/identity/auth
IDENTITY_SERVER_LOGOUT_PATH=/identity/logout
ROUTE_AFTER_REDIRECT=homepage
PEEKABOO_APPLICATION_NAME=myapp
PEEKABOO_APPLICATION_SECRET=your-secret-key
PEEKABOO_AUTOLOGIN=
```

## Local Development

To test your app without connecting to the real identity server (useful for offline development), set:

```bash
IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.dev
```

This returns a hardcoded dev user with the following properties:

- Email: `admin@localhost.net`
- Name: `dev`
- Roles: `ROLE_ADMIN`, `ROLE_USER`, `ROLE_API`, `ROLE_DEV`

To test with different roles, set `PEEKABOO_DEV_ROLES` in `.env.local`:

```bash
# Test as a specific role
PEEKABOO_DEV_ROLES=ROLE_QA

# Test with multiple roles
PEEKABOO_DEV_ROLES=ROLE_QA,ROLE_USER

# Back to default (all dev roles) — remove the line or leave empty
PEEKABOO_DEV_ROLES=
```

## Autologin

Set `PEEKABOO_AUTOLOGIN` to skip the login form and authenticate automatically:

| Value         | Behavior                                    |
|---------------|---------------------------------------------|
| `google`      | Redirect directly to Google login           |
| `facebook`    | Redirect directly to Facebook login         |
| `google_js`   | Show login page, auto-click Google button   |
| `facebook_js` | Show login page, auto-click Facebook button |
| (empty)       | Show login form without autologin           |

## Upgrading to 1.11.0

- The bundle now reads the JWT's `exp` claim. An expired token is rejected locally, without calling Peekaboo:
  a browser is sent to log in again at once, and Peekaboo no longer logs (and alerts on) a failed `jwt` login
  each time a user's token runs out. The claim is read without checking the signature, so it is only used to
  give up on a token, never to accept one; every other token is still verified by Peekaboo.
- The `__peekaboo_token` cookie now expires together with the JWT instead of after a fixed 24 hours, and a
  cached user is never kept past the token's expiry (it used to live up to 10 more minutes).
- No configuration or constructor changes.

## Upgrading to 1.10.1

- Fixes an endless redirect loop on `/peekaboo/auth` (the browser's "redirected you too many times") after the
  Peekaboo server rejects a stored token. A failed login now also expires the `__peekaboo_token` cookie, not just
  the session copy, and `/peekaboo/auth` no longer runs authentication, so a fresh login code always reaches it.
  Keep `/peekaboo/auth` as `PUBLIC_ACCESS` in `access_control`, as the install instructions show.
- `PeekabooAuthenticator`'s constructor gained an optional `TokenStorage` (autowired). This only matters if you
  construct or decorate the authenticator yourself.

## Upgrading to 1.10.0

- Requires a Peekaboo server that has `/api/token-by-code`. Deploy the server first: against an older server
  login fails with "wrong signature".
- After login the app now receives a one-time `__peekaboo_code` and exchanges it server-to-server, so the JWT
  no longer travels in the URL.
- `TokenStorage`'s constructor gained `Client` (and an optional logger). This only matters if you construct or
  decorate it yourself.

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyze
```

## License

GPL-3.0-only
