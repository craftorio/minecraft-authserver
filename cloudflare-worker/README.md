# Cloudflare Worker Port

This directory contains a TypeScript rewrite of the PHP authentication server.
It runs on [Cloudflare Workers](https://developers.cloudflare.com/workers/) and
uses the [Hono](https://hono.dev) router. Supabase provides the backing
database.

## Environment Variables
- `SUPABASE_URL` – URL of your Supabase project
- `SUPABASE_KEY` – Service or anon key used to access Supabase
- `TEXTURE_PRIVATE_KEY` – PEM private key used to sign skin properties

## Routes
Each file under `routes/` exports a small Hono app. The main worker loads all of
these modules automatically with `import.meta.glob` and mounts them at the root.
Handlers share the logic in `services/authenticator.ts`.

- `GET /` – simple health check
- `POST /authenticate` – validates credentials and creates a session
- `POST /refresh` – refreshes an existing session
- `POST /session/minecraft/join` – records that a player joined a server
- `GET /session/minecraft/hasJoined` – verifies a player has joined a server
- `GET /session/minecraft/profile/<id>` – retrieves profile information
- `GET /texture/<hash>` – retrieves the PNG skin with the given hash from
  Supabase storage

## Running locally
Use [Wrangler](https://developers.cloudflare.com/workers/wrangler/) to run the
worker:

```bash
wrangler dev dist/cloudflare-worker/worker.js
```

Ensure the required environment variables are provided via `wrangler.toml` or
your shell.
