## Context

The authserver is a PHP Yggdrasil-compatible session server using Flight, PHP-DI, Symfony Console, and dual storage backends (MySQL + SleekDB). Production Docker currently uses `digitalspacestudio/php:7.3` with Linuxbrew paths. A partial upgrade exists on branch `php84-docker` (official `php:8.4-fpm` image, updated Composer deps, Flight v3), but no automated tests exist and the change is not merged.

Constraints:
- PHP 7.3 syntax and patterns (`declare(strict_types=1)`, PSR-4) must remain
- Yggdrasil HTTP contract must not change for launchers and game servers
- Docker Compose layout (nginx, fpm, cli, mailhog) stays the same
- No major framework rewrite — Flight remains the HTTP layer

## Goals / Non-Goals

**Goals:**
- Run the application on PHP 8.4.23+ inside an official `php:8.4-fpm` image
- Update all Composer dependencies to PHP 8.4-compatible versions
- Preserve all Yggdrasil route behavior (status codes, JSON shapes)
- Add PHPUnit tests runnable via `docker compose exec cli`
- Document upgrade and test workflow in README

**Non-Goals:**
- Migrating from Flight to another framework
- Replacing `rych/phpass` (vendor deprecation suppressed via `error_reporting`)
- Adding CI/CD pipeline (can follow later)
- MySQL integration tests against a real database (use SleekDB for integration tests)
- PHP 8.4 language feature adoption (typed properties everywhere, enums, etc.)

## Decisions

### 1. Official `php:8.4-fpm` as base image

**Choice:** Build `docker/php/Dockerfile` from `php:8.4-fpm`.

**Rationale:** Official image is maintained, well-documented, and uses standard paths (`/usr/local/etc/php/`). Eliminates Linuxbrew dependency.

**Alternatives considered:**
- `digitalspacestudio/php:8.4` — same vendor lock-in, unknown maintenance
- Alpine variant — smaller but musl compatibility issues with some extensions

### 2. Single shared image for `cli` and `fpm` services

**Choice:** Both services build from the same Dockerfile, tagged `craftorio/authserver-php:8.4`.

**Rationale:** Identical PHP extensions and Composer; only entry command differs.

### 3. Flight v3 upgrade (not v2, not fork)

**Choice:** `mikecao/flight ^3.0`.

**Rationale:** v1.3 fatals on PHP 8.4 due to `ArrayAccess` return types. v3 is latest, API-compatible with existing `\Flight::route()`, `\Flight::json()`, `\Flight::request()` usage. No code changes needed in routes.

**Alternatives considered:**
- `flightphp/core` — maintained fork but requires namespace/import changes
- Pin PHP 8.0 + Flight v2 — does not meet PHP 8.4 goal

### 4. Dependency version targets

| Package | From | To | Notes |
|---------|------|-----|-------|
| php | (none) | ^8.4 | Platform constraint |
| mikecao/flight | ^1.3 | ^3.0 | PHP 8.4 compat |
| php-di/php-di | ^6.3 | ^7.0 | PSR-11, PHP 8+ |
| symfony/console | ^5.3 | ^7.2 | PHP 8.2+ |
| hassankhan/config | ^2.2 | ^3.0 | Same `AbstractConfig` API |
| nelexa/zip | ^3.3 | ^4.0 | Certificate jar generation |
| ramsey/uuid | ^4.2 | ^4.7 | PHP 8.4 |
| vlucas/phpdotenv | ^5.3 | ^5.6 | Minor bump |

### 5. Suppress vendor deprecations in php.ini

**Choice:** `error_reporting = E_ALL & ~E_DEPRECATED` in `docker/php/php.ini`.

**Rationale:** `rych/phpass` triggers `${var}` deprecation on PHP 8.4. With `display_errors=on`, Flight's error handler converts deprecations to fatal 500 responses. Suppressing E_DEPRECATED preserves HTTP behavior until phpass is replaced.

### 6. PHPUnit 11 with SleekDB-backed integration tests

**Choice:** PHPUnit 11 (PHP 8.2+), tests in `authserver/tests/`.

**Structure:**
```
tests/
  Unit/
    Hash/PhpassTest.php
    Hash/Md5Test.php
    ProfileIdTest.php
  Integration/
    Route/HomeTest.php
    Route/PublicKeysTest.php
    Route/AuthenticateTest.php
    Route/RefreshTest.php
```

**Integration approach:** Bootstrap a minimal Flight app with in-memory SleekDB storage (temp directory), inject test config, call route handlers directly or via HTTP simulation. No external MySQL required for CI simplicity.

**Alternatives considered:**
- Pest — adds dependency, team unfamiliar
- Full HTTP e2e via curl in shell — harder to assert JSON shapes

### 7. Test execution in Docker only

**Choice:** `docker compose exec cli vendor/bin/phpunit` as documented command.

**Rationale:** Matches existing dev workflow; ensures PHP 8.4 runtime.

## Risks / Trade-offs

| Risk | Mitigation |
|------|------------|
| Flight v3 subtle API differences | Integration tests for all 9 routes |
| phpass deprecation noise in CLI | Same `error_reporting` in php.ini; document known CLI warning |
| SleekDB 2.50 behavior change | Test account CRUD in integration suite |
| hassankhan/config v3 breaking change | Config class extends `AbstractConfig` — verify `get()`/`getDefaults()` unchanged |
| nelexa/zip v4 API change | Test `certificates:generate` command output |
| No CI gate | Document manual test command; CI can be follow-up change |

## Migration Plan

1. Merge `php84-docker` branch changes (Docker + Composer) into main via this change
2. Add PHPUnit and test files
3. Run `docker compose build && docker compose up -d`
4. Run `composer install` and `vendor/bin/phpunit` inside cli container
5. Manual smoke test: `curl /`, `/publickeys`, `account:create` + `/authenticate`
6. Rollback: revert to `digitalspacestudio/php:7.3` image and old `composer.lock`

## Open Questions

- Should CI (GitHub Actions) be added in this change or deferred?
- Is MySQL storage integration testing required for production confidence, or is SleekDB coverage sufficient?
