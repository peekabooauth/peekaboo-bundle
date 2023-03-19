Peekaboo Bundle
===============

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

ENV:
```php
IDENTITY_SERVER_URL_EXTERNAL=https://peekabooauth.com
IDENTITY_SERVER_AUTH_PATH=/identity/auth
IDENTITY_SERVER_LOGOUT_PATH=/identity/logout
ROUTE_AFTER_REDIRECT=homepage
IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.com
PEEKABOO_APPLICATION_NAME=atlas
PEEKABOO_APPLICATION_SECRET=cb76217cd4ebae7b85f93312d8606c7e
```

API auth
```bash
curl -X POST -H "Content-Type: application/json" https://peekabooauth.com/api/login_check -d '{"username":"user@example.com","password":"123456"}'
```

### Local dev

If you want to test app without connection to real server (useful for development offline) then set
env `IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.dev`. Then you'll get valid user immediately without
using identity server.
