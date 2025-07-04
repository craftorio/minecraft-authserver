# Minecraft Authserver Worker

This repository contains a JavaScript implementation of a Minecraft authentication server designed for the Cloudflare Workers platform. The project uses the [Hono](https://hono.dev) router and stores data in Supabase.

> **Prerequisite**
> - Node.js 20 or later for building the worker
> - [Supabase CLI](https://supabase.com/docs/guides/cli) for managing the local database
> - `wrangler` and `workerd` installed globally via `npm i -g wrangler workerd`

## Environment Variables
- `SUPABASE_URL` – URL of your Supabase project
- `SUPABASE_KEY` – service or anon key used to access Supabase
- `TEXTURE_PRIVATE_KEY` – PEM private key used to sign skin properties

A `.env.example` file is included with defaults from Supabase's official
self-hosting stack. Copy it to `.env` before starting Docker so that
`docker compose` has all required variables.

## Routes
Each file under `cloudflare-worker/routes/` exports a small Hono app. The main worker loads these modules automatically with `import.meta.glob` and mounts them at the root.

- `GET /` – health check
- `POST /authenticate` – validates credentials and creates a session
- `POST /refresh` – refreshes an existing session
- `POST /session/minecraft/join` – records that a player joined a server
- `GET /session/minecraft/hasJoined` – verifies a player has joined a server
- `GET /session/minecraft/profile/<id>` – retrieves profile information
- `GET /texture/<hash>` – retrieves the PNG skin from Supabase storage


## Local development with Docker
1. Install the Supabase CLI.
2. Run `supabase init` to create the local configuration (the provided `supabase/config.toml` will be used).
3. Copy `.env.example` to `.env`.
4. Ensure the `volumes/` directory from this repo is present.
5. Start the Supabase stack and worker:

```bash
docker compose up --build
```

6. The Supabase CLI reads `supabase/config.toml` and connects to the stack on
   `localhost` using the ports defined there (API on **8000**, Postgres on
   **5432**, Studio on **3000**). Because `docker compose` exposes these same
   ports, commands like `supabase db reset` work without extra configuration.

7. Apply the initial database schema after the containers are running:

```bash
supabase db reset

With `wrangler` installed, you can also run the worker directly:

```bash
wrangler dev --local
```

Or test the standalone runtime:

```bash
workerd serve dist/worker.js --experimental
```

## Production build
Compile the worker and publish it to Cloudflare. Configure `SUPABASE_URL` and
`SUPABASE_KEY` with your Supabase Cloud project credentials:

```bash
npm run build
wrangler publish dist/worker.js
```

The compiled files are placed in the `dist/` directory and are ignored by git.
