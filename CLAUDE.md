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

## Two Release Lines

Two lines are maintained in parallel; apps that cannot leave Symfony 6 stay on 1.8.

| Line | Branch | PHP | Symfony |
|---|---|---|---|
| 1.10.x | `main` | 8.4+ | 7.4+ / 8.0+ |
| 1.8.x | `1.8` | 8.1+ | 6.2 – 6.4 |

- **Every bug and security fix ships on both lines.** Fix and test on `main` first, then backport: branch `backport/<topic>-1.8` off `origin/1.8` in its own worktree, carry the same change and tests, open a separate PR against `1.8`. A fix is done only when both PRs exist. New features go to `main` only unless asked.
- **Backports must be valid PHP 8.1 on Symfony 6.2**, tests included. Typical rewrites: untyped class constants (`const string` needs 8.3), no `readonly class` (8.2), no `#[\Override]` (8.3), no DNF types (8.2).
- **Installing 1.8 dependencies locally:** `roave/security-advisories` conflicts with the PHP 8.1 platform pin, so mirror CI: `composer remove --dev roave/security-advisories --no-update && composer update --no-scripts`, then `git checkout composer.json && rm composer.lock` so neither change gets committed.
- **Releasing:** on each line, bump `PeekabooBundle::VERSION`, add a `## Upgrading to X.Y.Z` README section (a 1.8 note names its `main` counterpart, e.g. "same change as 1.10.0 on main"), and tag `X.Y.Z` (no `v` prefix) on that line's merge commit.

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
- `PEEKABOO_DEV_ROLES` (optional: comma-separated roles to override dev user defaults)

### Testing

Uses PHPUnit with native mocks. Dev mode (`https://peekabooauth.dev`) returns a hardcoded admin user without hitting the real identity server. Set `PEEKABOO_DEV_ROLES` to override roles (e.g. `PEEKABOO_DEV_ROLES=ROLE_SEO`).
