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

This returns a hardcoded admin user with the following properties:

- Email: `admin@localhost.net`
- Name: `dev`
- Roles: `ROLE_ADMIN`, `ROLE_USER`, `ROLE_API`, `ROLE_DEV`

## Autologin

Set `PEEKABOO_AUTOLOGIN` to skip the login form and authenticate automatically:

| Value         | Behavior                                    |
|---------------|---------------------------------------------|
| `google`      | Redirect directly to Google login           |
| `facebook`    | Redirect directly to Facebook login         |
| `google_js`   | Show login page, auto-click Google button   |
| `facebook_js` | Show login page, auto-click Facebook button |
| (empty)       | Show login form without autologin           |

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
