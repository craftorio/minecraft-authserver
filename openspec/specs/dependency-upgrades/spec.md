# Dependency Upgrades

Composer platform and third-party package versions compatible with PHP 8.4.

## Requirements

### Requirement: PHP platform constraint

`composer.json` SHALL require `"php": "^8.4"` as a platform dependency.

#### Scenario: Composer platform check

- **WHEN** `composer install` runs on PHP 8.4
- **THEN** all packages install without platform requirement errors

### Requirement: Flight framework compatibility

The Flight framework version SHALL be compatible with PHP 8.4 strict `ArrayAccess` return types.

#### Scenario: Flight HTTP bootstrap

- **WHEN** a request is made to `GET /` with a normal User-Agent
- **THEN** the server returns HTTP 200 with JSON body `null` and does not fatal

### Requirement: Major dependency upgrades

The following packages SHALL be upgraded to PHP 8.4-compatible major versions:

- `php-di/php-di` ^7.0
- `symfony/console` ^7.2
- `hassankhan/config` ^3.0
- `nelexa/zip` ^4.0
- `mikecao/flight` ^3.0

#### Scenario: Composer update succeeds

- **WHEN** `composer update` runs inside the PHP 8.4 container
- **THEN** the lock file resolves without conflicts and `composer install` succeeds

#### Scenario: CLI commands work

- **WHEN** `php bin/console list` is executed
- **THEN** all account, session, and certificate commands are listed without fatal errors

#### Scenario: Certificate generation works

- **WHEN** `php bin/console certificates:generate` is executed
- **THEN** PEM and JAR files are written to `var/certificates/`

### Requirement: Vendor deprecation isolation

Third-party library deprecations (e.g. `rych/phpass`) SHALL NOT cause HTTP 500 responses.

#### Scenario: Public keys endpoint with phpass loaded

- **WHEN** `GET /publickeys` is requested
- **THEN** the server returns HTTP 200 with a valid JSON public key payload
