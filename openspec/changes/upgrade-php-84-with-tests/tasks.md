## 1. Docker runtime (php-runtime)

- [ ] 1.1 Create `docker/php/Dockerfile` based on official `php:8.4-fpm` with `pdo_mysql`, `zip`, `mbstring`, Composer 2, and msmtp
- [ ] 1.2 Update `docker-compose.yml`: replace `digitalspacestudio/php:7.3` with `build` + image `craftorio/authserver-php:8.4` for `cli` and `fpm`
- [ ] 1.3 Remap volume mounts to official paths (`/usr/local/etc/php/conf.d/`, `/usr/local/etc/php-fpm.d/`, `/etc/msmtprc`)
- [ ] 1.4 Update `docker/php/entrypoint.sh` to use `php-fpm --nodaemonize` instead of Linuxbrew binary
- [ ] 1.5 Update `docker/php/php-fpm.conf` with `listen = 9000` pool override
- [ ] 1.6 Update `docker/php/php.ini` paths and set `error_reporting = E_ALL & ~E_DEPRECATED`
- [ ] 1.7 Build and verify: `docker compose build && docker compose up -d`, confirm `php -v` reports 8.4.x

## 2. Composer dependencies (dependency-upgrades)

- [ ] 2.1 Add `"php": "^8.4"` to `authserver/composer.json`
- [ ] 2.2 Bump `mikecao/flight` to `^3.0`, `php-di/php-di` to `^7.0`, `symfony/console` to `^7.2`
- [ ] 2.3 Bump `hassankhan/config` to `^3.0`, `nelexa/zip` to `^4.0`, `ramsey/uuid` to `^4.7`, `vlucas/phpdotenv` to `^5.6`
- [ ] 2.4 Run `composer update` inside PHP 8.4 container and commit updated `composer.lock`
- [ ] 2.5 Verify CLI: `php bin/console list` and `php bin/console certificates:generate` work without fatals
- [ ] 2.6 Verify HTTP: `GET /` returns `null`, `GET /publickeys` returns 200 JSON (no 500 from vendor deprecations)

## 3. Test infrastructure (automated-test-suite)

- [ ] 3.1 Add `phpunit/phpunit ^11` to `composer.json` require-dev
- [ ] 3.2 Create `authserver/phpunit.xml.dist` with Unit and Integration test suites
- [ ] 3.3 Create `authserver/tests/bootstrap.php` with autoload and temp storage setup
- [ ] 3.4 Add PSR-4 autoload-dev for `Craftorio\Authserver\Tests\` in `composer.json`

## 4. Unit tests

- [ ] 4.1 `tests/Unit/Hash/PhpassTest.php` — hash and verify known password
- [ ] 4.2 `tests/Unit/Hash/Md5Test.php` — hash and verify known password
- [ ] 4.3 `tests/Unit/ProfileIdTest.php` — deterministic UUID from username

## 5. Integration tests (yggdrasil-api-contract)

- [ ] 5.1 `tests/Integration/Route/HomeTest.php` — GET / returns null
- [ ] 5.2 `tests/Integration/Route/PublicKeysTest.php` — GET /publickeys returns valid key shape
- [ ] 5.3 `tests/Integration/Route/AuthenticateTest.php` — missing fields → 400, valid creds → 200, wrong password → 403
- [ ] 5.4 `tests/Integration/Route/RefreshTest.php` — valid refresh returns new accessToken
- [ ] 5.5 `tests/Integration/Route/SessionMinecraftJoinTest.php` — valid join returns 204
- [ ] 5.6 `tests/Integration/Route/SessionMinecraftHasJoinedTest.php` — hasJoined after join returns profile
- [ ] 5.7 `tests/Integration/Route/ProfileLookupBulkByNameTest.php` — bulk lookup with JSON array body

## 6. Documentation and verification

- [ ] 6.1 Update `README.md`: PHP 8.4, Docker build steps, `vendor/bin/phpunit` command
- [ ] 6.2 Update `AGENTS.md`: PHP 8.4 version reference
- [ ] 6.3 Run full test suite: `docker compose exec cli vendor/bin/phpunit` — all green
- [ ] 6.4 Manual smoke test: create account, authenticate, refresh, join/hasJoined flow

## 7. Merge and cleanup

- [ ] 7.1 Merge `php84-docker` branch work into main (or rebase this change onto it)
- [ ] 7.2 Archive OpenSpec change after implementation is complete
