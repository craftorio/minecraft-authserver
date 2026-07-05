# Automated Test Suite

PHPUnit-based unit and integration tests executed in Docker.

## Requirements

### Requirement: PHPUnit as dev dependency

The project SHALL include PHPUnit 11 as a dev dependency in `composer.json`.

#### Scenario: PHPUnit installed

- **WHEN** `composer install` runs inside the PHP 8.4 container
- **THEN** `vendor/bin/phpunit` exists and is executable

### Requirement: Test directory structure

Tests SHALL be organized under `authserver/tests/` with `Unit/` and `Integration/` subdirectories.

#### Scenario: Autoload for test classes

- **WHEN** PHPUnit configuration is loaded
- **THEN** test classes under `tests/` are autoloaded via PSR-4 or PHPUnit bootstrap

### Requirement: Unit tests for hashing

Unit tests SHALL verify password hash algorithms produce consistent results.

#### Scenario: Phpass hash verification

- **WHEN** a known password is hashed with Phpass and verified
- **THEN** verification returns true for the correct password and false for incorrect

#### Scenario: MD5 hash verification

- **WHEN** a known password is hashed with MD5 and verified
- **THEN** verification returns true for the correct password and false for incorrect

### Requirement: Unit tests for profile ID derivation

Unit tests SHALL verify profile UUID derivation is deterministic for a given username.

#### Scenario: Profile ID stability

- **WHEN** `ProfileId::offlineUsername()` is called twice with the same username
- **THEN** both calls return the same UUID string

### Requirement: Integration tests for HTTP routes

Integration tests SHALL exercise core Yggdrasil routes using SleekDB storage in a temporary directory.

#### Scenario: Home route integration

- **WHEN** the Home route handler is invoked
- **THEN** the JSON response is `null`

#### Scenario: Authenticate flow integration

- **WHEN** an account is created in test storage and `/authenticate` is called with valid credentials
- **THEN** the response contains `accessToken` and `selectedProfile`

#### Scenario: Public keys integration

- **WHEN** test certificates are generated and `/publickeys` is called
- **THEN** the response contains `profilePropertyKeys` with a non-empty `publicKey`

### Requirement: Test execution command

The README SHALL document how to run tests inside Docker.

#### Scenario: Docker test command

- **WHEN** `docker compose exec cli vendor/bin/phpunit` is executed
- **THEN** all tests pass with exit code 0

### Requirement: PHPUnit configuration

A `phpunit.xml.dist` file SHALL configure test suites, bootstrap, and coverage exclusions for `vendor/`.

#### Scenario: PHPUnit discovers tests

- **WHEN** `vendor/bin/phpunit --list-tests` is executed
- **THEN** at least one unit test and one integration test are listed
