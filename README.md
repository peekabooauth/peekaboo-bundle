Peekaboo Bundle
===================

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
    api_peekaboo_user_provider:
        id: Peekabooauth\PeekabooBundle\UserProvider\ApiUserProvider
#......
firewalls:
    api:
        pattern: ^/api
        custom_authenticators:
            - Peekabooauth\PeekabooBundle\Security\ApiPeekabooAuthenticator
        provider: api_peekaboo_user_provider
    peekaboo:
        pattern: ^/
        custom_authenticators:
            - Peekabooauth\PeekabooBundle\Security\PeekabooAuthenticator
        provider: peekaboo_user_provider
#.......
access_control:
    - { path: ^/peekaboo/auth, roles: PUBLIC_ACCESS }
```

Env:
```php
IDENTITY_SERVER_URL_EXTERNAL=https://peekabooauth.com
IDENTITY_SERVER_AUTH_PATH=/identity/auth
IDENTITY_SERVER_LOGOUT_PATH=/identity/logout
ROUTE_AFTER_REDIRECT=homepage
IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.com
PEEKABOO_APPLICATION_NAME=atlas
PEEKABOO_APPLICATION_SECRET=cb76217cd4ebae7b85f93312d8606c7e
```

Api auth
```bash
curl -X POST -H "Content-Type: application/json" https://peekabooauth.com/api/login_check -d '{"username":"andriy@loc.loc","password":"123456"}'
```
