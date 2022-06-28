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
```

Env:
```php
IDENTITY_SERVER_URL_EXTERNAL=http://127.0.0.1:8000
IDENTITY_SERVER_AUTH_PATH=/identity/auth
IDENTITY_SERVER_URL_INTERNAL=http://peekaboo_app
JWT_TOKEN_NAME=__peekaboo_token
```
