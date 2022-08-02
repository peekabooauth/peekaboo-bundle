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
......
providers:
    peekaboo_user_provider:
        id: Peekabooauth\PeekabooBundle\Security\UserProvider
......
firewalls:
    peekaboo:
        pattern: ^/
        custom_authenticators:
            - Peekabooauth\PeekabooBundle\Security\PeekabooAuthenticator
        provider: peekaboo_user_provider
.......
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
