# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build and Test Commands

```bash
# Install dependencies
composer install

# Run all tests
vendor/bin/phpunit

# Run a single test file
vendor/bin/phpunit tests/Client/ClientTest.php

# Run a specific test method
vendor/bin/phpunit --filter testGetUserByApiKey

# Run static analysis
vendor/bin/phpstan analyse -c phpstan.dist.neon
```

## Architecture Overview

This is a Symfony bundle (`peekabooauth/peekaboo-bundle`) that provides authentication integration with the Peekaboo identity server. It requires PHP 8.4+ and Symfony 7.4+/8.0+.

### Authentication Flow

The bundle supports multiple authentication methods via a chain of **UserLoaders** (priority order: JWT token > API key > Basic Auth > Token Storage):

1. **API Authentication**: JWT Bearer tokens (`JwtTokenUserLoader`) and API keys via `x-api-key` header (`ApiKeyUserLoader`)
2. **Session Authentication**: Token stored in session (`TokenStorageUserLoader`) after OAuth-style redirect flow

### Key Components

- **`PeekabooAuthenticator`** (`src/Security/`): Symfony authenticator implementing `AbstractAuthenticator`. Delegates to `UserLoaderRegistry` to determine auth method and load users.

- **`UserLoaderRegistry`** (`src/UserLoader/`): Orchestrates multiple `UserLoaderInterface` implementations. Each loader checks `isAuth()` to determine if it can handle the current request.

- **`Client`** (`src/Client/`): HTTP client for communicating with the identity server. Methods: `getUserByJwt()`, `getUserByApiKey()`. Uses `DevHelper` to bypass real server in development (`IDENTITY_SERVER_URL_INTERNAL=https://peekabooauth.dev`).

- **`UserDTO`** (`src/DTO/`): User entity implementing Symfony's `UserInterface`. Contains email, name, and roles.

- **`AuthRedirectBuilder`** (`src/Services/`): Builds redirect URLs for OAuth login flow with the identity server.

### Configuration

Environment variables (see `src/Resources/config/services.yaml`):
- `IDENTITY_SERVER_URL_EXTERNAL` / `IDENTITY_SERVER_URL_INTERNAL`
- `PEEKABOO_APPLICATION_NAME`, `PEEKABOO_APPLICATION_SECRET`
- `PEEKABOO_AUTOLOGIN` (optional: `google`, `facebook`, `google_js`, `facebook_js`)

### Testing

Uses PHPUnit with Prophecy for mocking. Dev mode (`https://peekabooauth.dev`) returns a hardcoded admin user without hitting the real identity server.
