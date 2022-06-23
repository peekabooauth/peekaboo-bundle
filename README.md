Peekaboo Bundle
===================

## Install

```bash
composer require gupalo/peekaboo-bundle
```

Add to `config/bundles.php`

```php
Gupalo\PeekabooBundle\PeekabooBundle::class => ['all' => true]
```

Env:
```php
IDENTITY_SERVER_URL=127.0.0.1:8000
IDENTITY_SERVER_AUTH_PATH=/identity/auth
```
