## Why

The authserver currently targets PHP 7.3 on a custom Linuxbrew-based Docker image. PHP 7.3 is end-of-life, blocks security updates, and is incompatible with modern library versions. Upgrading to PHP 8.4 on the official `php` Docker image reduces maintenance burden, improves performance, and aligns the stack with supported runtimes. A new automated test suite is required to lock in Yggdrasil API behavior during the upgrade and prevent regressions in authentication, session, and profile flows.

## What Changes

- Replace `digitalspacestudio/php:7.3` with a project-built image based on official `php:8.4-fpm`
- Update `composer.json` platform requirement to `^8.4` and bump incompatible dependencies
- Upgrade Flight from v1.x to v3.x for PHP 8.4 `ArrayAccess` compatibility
- Update Docker volume mounts and PHP-FPM config paths for the official image layout
- Add PHPUnit as a dev dependency with a test harness runnable inside the `cli` container
- Add integration tests for core Yggdrasil HTTP routes and unit tests for critical domain logic (hashing, profile ID, session signing)
- Update README and AGENTS.md to document PHP 8.4 and test commands
- **BREAKING**: Minimum PHP version becomes 8.4; deployments on PHP 7.3 are no longer supported

## Capabilities

### New Capabilities

- `php-runtime`: Official PHP 8.4 Docker image, required extensions, and container configuration for cli/fpm services
- `dependency-upgrades`: Composer platform and third-party package versions compatible with PHP 8.4
- `yggdrasil-api-contract`: Preserved HTTP API behavior for authentication, session, profile, and public key endpoints
- `automated-test-suite`: PHPUnit-based unit and integration tests executed in Docker

### Modified Capabilities

<!-- No existing specs in openspec/specs/ — all capabilities are new -->

## Impact

- `docker/php/Dockerfile` (new), `docker-compose.yml`, `docker/php/*.ini|conf|sh`
- `authserver/composer.json`, `authserver/composer.lock`
- `authserver/tests/` (new test tree)
- `README.md`, `AGENTS.md`
- No changes to external Yggdrasil API contract (clients and launchers remain compatible)
- Vendor deprecations (e.g. `rych/phpass` `${var}` syntax) must be suppressed or isolated so they do not break HTTP responses
