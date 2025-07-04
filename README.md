# Minecraft Authserver Worker

This repository contains a JavaScript implementation of a Minecraft authentication server designed for the Cloudflare Workers platform. The project uses the [Hono](https://hono.dev) router and stores data in Supabase.

> **Prerequisite**: Wrangler 3 requires Node.js 20 or later. Ensure your local environment or Docker image provides a recent Node version.

## Environment Variables
- `SUPABASE_URL` – URL of your Supabase project
- `SUPABASE_KEY` – service or anon key used to access Supabase
- `TEXTURE_PRIVATE_KEY` – PEM private key used to sign skin properties

A `.env.example` file is included with defaults from Supabase's official
self-hosting stack. Copy it to `.env` when running `docker compose` to
provide all required environment variables.

## Routes
Each file under `cloudflare-worker/routes/` exports a small Hono app. The main worker loads these modules automatically with `import.meta.glob` and mounts them at the root.

- `GET /` – health check
- `POST /authenticate` – validates credentials and creates a session
- `POST /refresh` – refreshes an existing session
- `POST /session/minecraft/join` – records that a player joined a server
- `GET /session/minecraft/hasJoined` – verifies a player has joined a server
- `GET /session/minecraft/profile/<id>` – retrieves profile information
- `GET /texture/<hash>` – retrieves the PNG skin from Supabase storage

## Running locally
Install dependencies and start the worker with [Wrangler](https://developers.cloudflare.com/workers/wrangler/):

```bash
npm install
wrangler dev dist/worker.js
```

Ensure the required environment variables are provided via `wrangler.toml` or your shell.

## Supabase setup
Run `supabase init` to create a local project and apply the migration from `supabase/migrations/0001_initial.sql` to create the tables and storage bucket. The example configuration in `supabase/config.toml` sets local ports and a placeholder JWT secret.

## Docker
A `Dockerfile` and `docker-compose.yml` are provided for convenience. The compose file mirrors the [official Supabase self-hosting stack](https://supabase.com/docs/guides/self-hosting/docker). The worker communicates with Supabase through the `supabase-kong` gateway at `http://supabase-kong:8000`.

```bash
docker compose up --build
```

## Production build
Compile the worker and publish it to Cloudflare:

```bash
npm run build
wrangler publish dist/worker.js
```

The compiled files are placed in the `dist/` directory and are ignored by git.
