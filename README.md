# Minecraft Authserver

Minecraft-compatible authentication and session server (Yggdrasil-style API). Used by custom launchers and game servers for offline or self-hosted auth.

## Features

- Account authentication (`/authenticate`, `/refresh`)
- Minecraft session API (`/session/minecraft/*`)
- Profiles and textures (`/texture`, bulk lookup)
- RSA keys for session verification (`/publickeys`)
- Account storage: MySQL or SleekDB (file-based)
- Password hashing: phpass (default) or MD5
- CLI for account and certificate management

## Stack

- PHP 7.3, Flight micro-framework
- Symfony Console (CLI)
- PHP-DI, nginx + php-fpm (Docker)
- MailHog for dev email capture

## Quick start (Docker)

```bash
docker compose up -d
```

HTTP is available on port **8187** by default; MailHog UI on **8175**.

Health check:

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8187/
# 200
```

## Session keys (first-time setup)

RSA session keys are **required** before the server can sign player textures or serve `/publickeys`. Generate them once on a fresh install.

**Docker:**

```bash
docker compose up -d
docker compose exec cli php bin/console certificates:generate
```

**Without Docker:**

```bash
cd authserver
composer install
php bin/console certificates:generate
```

The command writes three files to `authserver/var/certificates/` (override via `certificatesDir` in `config.php`):

| File | Purpose |
|------|---------|
| `yggdrasil_session_private.pem` | Signs texture properties (`Authenticator`) |
| `yggdrasil_session_public.pem` | Served by `GET /publickeys` |
| `yggdrasil_session_pubkey.jar` | Trust anchor for Java/Minecraft clients (`yggdrasil_session_pubkey.der` inside) |

The command **refuses to overwrite** an existing private key. Delete the files manually only if you intend to rotate keys (existing clients must be updated with the new public key).

Verify the keys are loaded:

```bash
curl -s http://localhost:8187/publickeys | python3 -m json.tool
```

Expected: JSON with `profilePropertyKeys` and `playerCertificateKeys`, each containing a `publicKey` field.

## Configuration

Copy the example and adjust for your environment:

```bash
cp authserver/config.example.mysql.php authserver/config.php
```

Key options in `config.php`:

| Key | Values | Description |
|-----|--------|-------------|
| `account.storage` | `mysql`, `sleekdb` | Account storage backend |
| `account.hash_algorithm` | `default`, `md5` | Password hash algorithm |
| `account.mysql.*` | — | DSN, table, column mapping |
| `account.sleekdb.*` | — | Data directory and cache TTL |
| `certificatesDir` | `var/certificates` | RSA key output directory |
| `skinDir` | `var/skins` | Local skin files root |

## HTTP API

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/` | Health check |
| POST | `/authenticate` | Login with username/password |
| POST | `/refresh` | Refresh access token |
| GET | `/publickeys` | Public RSA keys |
| GET | `/texture/@hash` | Texture by hash |
| POST | `/session/minecraft/join` | Register server session |
| GET | `/session/minecraft/hasJoined` | Server session verification |
| GET | `/session/minecraft/profile/@profile` | Player profile |
| POST | `/minecraft/profile/lookup/bulk/byname` | Bulk profile lookup |

Document root: `authserver/public/index.php`.

## CLI

Run commands inside the `cli` container:

```bash
docker compose exec cli php bin/console list
```

| Command | Description |
|---------|-------------|
| `account:create` | Create account |
| `account:find` | Find account |
| `account:delete` | Delete account |
| `account:authenticate` | Verify credentials |
| `certificates:generate` | Generate RSA certificates |
| `session:server-join` | Simulate server join |
| `session:server-has-joined` | Simulate hasJoined |

Example:

```bash
docker compose exec cli php bin/console account:create Steve steve@example.com secret
```

## Project layout

```
authserver/          # PHP application
  bin/console        # CLI entrypoint
  public/            # Web entrypoint
  src/               # Source code
  var/               # Data, skins, certificates
docker/              # nginx, php-fpm config
docker-compose.yml
```

## Development without Docker

```bash
cd authserver
composer install
cp config.example.mysql.php config.php
# edit config.php
php -S localhost:8080 -t public
```

## Docker environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `DOCKER_PORT_HTTP` | `8187` | nginx HTTP port |
| `DOCKER_PORT_MAIL_HTTP` | `8175` | MailHog web UI |

## License

MIT — see `authserver/composer.json`.

## For AI agents

Agent instructions (Cursor, Claude, etc.): [AGENTS.md](AGENTS.md).
