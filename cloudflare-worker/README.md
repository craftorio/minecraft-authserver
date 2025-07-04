# Cloudflare Worker Port

This directory contains a partial JavaScript rewrite of the PHP authentication server.
The worker is designed for [Cloudflare Workers](https://developers.cloudflare.com/workers/).
Supabase is used as the backing database instead of MySQL or SleekDB.

## Environment Variables
- `SUPABASE_URL` – URL of your Supabase project
- `SUPABASE_KEY` – Service or anon key used to access Supabase

## Routes
The worker currently implements several routes that map to the original PHP API:

- `POST /authenticate` – validates credentials and creates a session
- `POST /refresh` – refreshes an existing session
- `GET /texture/<hash>` – retrieves the PNG skin with the given hash from Supabase storage

Other routes from the PHP version would need to be ported in a similar fashion.

## Running locally
You can run the worker with [Wrangler](https://developers.cloudflare.com/workers/wrangler/):

```bash
wrangler dev src/worker.js
```

Make sure to provide the required environment variables in your `wrangler.toml` or via your shell.

This implementation is minimal and does not cover all features from the PHP code, but it demonstrates how the logic can be translated to a serverless environment.
