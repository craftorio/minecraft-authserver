# PHP Runtime

Official PHP 8.4 Docker runtime and container configuration for the authserver.

## Requirements

### Requirement: PHP 8.4 FPM runtime

The application SHALL run on PHP 8.4 or later using the official `php:8.4-fpm` Docker image as the base.

#### Scenario: PHP version check

- **WHEN** `php -v` is executed inside the `cli` container
- **THEN** the output reports PHP 8.4.x

#### Scenario: Required extensions loaded

- **WHEN** `php -m` is executed inside the `cli` container
- **THEN** the output includes `pdo_mysql`, `zip`, `mbstring`, and `openssl`

### Requirement: Official image configuration paths

Docker volume mounts SHALL use official PHP image paths instead of Linuxbrew paths.

#### Scenario: PHP ini mount

- **WHEN** the `fpm` service starts
- **THEN** custom settings are loaded from `/usr/local/etc/php/conf.d/zz-authserver.ini`

#### Scenario: PHP-FPM pool config

- **WHEN** the `fpm` service starts
- **THEN** FPM listens on port 9000 and accepts connections from nginx

### Requirement: Composer available in container

The PHP image SHALL include Composer 2 for dependency management.

#### Scenario: Composer install on startup

- **WHEN** the `fpm` entrypoint runs
- **THEN** `composer install -o` completes without errors
