# Deploying Earlybird to Render (free tier) + Neon

This repo has a `render.yaml` Blueprint that deploys the Symfony API, the
React/Vite frontend, and the Vite/React dashboard to Render. The Postgres
database itself lives on Neon (neon.tech), not Render -- Render's own free
managed Postgres expires after 30 days, and Neon's free tier doesn't. It
intentionally leaves out the `reverse-proxy` service from
`docker-compose.yml` -- on Render each web service already gets its own
public HTTPS URL, so the nginx reverse-proxy (which routed by hostname, e.g.
`earlybird-api`) isn't needed and isn't part of this Blueprint.

## 0. Your Neon database

A free Neon project called `earlybird` was already created (Postgres 16,
matching what `docker-compose.yml` uses locally). Get the connection string
at [console.neon.tech](https://console.neon.tech) -- open the `earlybird`
project, Connect, copy the connection string for the `earlybird` database.
You'll paste it into Render as `DATABASE_URL` in step 1 below.

Don't put that connection string in a file or a commit -- `DATABASE_URL` is
`sync: false` in `render.yaml` specifically so Render prompts for it
interactively instead. Same goes for any other real secret in this project;
see section 5 for how those are handled.

## 1. Deploy the Blueprint

1. Push this branch (with `render.yaml`) to GitHub.
2. On [render.com](https://render.com), click **New > Blueprint**.
3. Connect the `dxlxnhxxe/Earlybird` repo and select the **develop** branch (or
   whichever branch you push this to).
4. Render will show the 3 services it's about to create (`earlybird-back`,
   `earlybird-front`, `earlybird-dashboard`). Confirm and deploy.
5. You'll be prompted for the env vars marked `sync: false` in the blueprint
   -- paste in:
   - `DATABASE_URL`: the Neon connection string from step 0 above.
   - `JWT_PASSPHRASE`, `JWT_PRIVATE_KEY_PEM_B64`, `JWT_PUBLIC_KEY_PEM_B64`: see
     section 5 below.
   (There's no Clerk key to provide -- the dashboard's `/clerk` route is
   unused demo code left over from the admin-dashboard template it's built
   on; the app's real login doesn't need it, so it's not in the blueprint.)

## 2. Confirm the real service URLs

`render.yaml` assumes your services will be reachable at:

- `https://earlybird-back.onrender.com`
- `https://earlybird-front.onrender.com`
- `https://earlybird-dashboard.onrender.com`

That's what Render gives you when those exact names are available. After the
first deploy, check the actual URLs on each service's page in the Render
dashboard. If any differ from the above:

- Update `CORS_ALLOW_ORIGIN` on `earlybird-back` to match the real frontend/
  dashboard URLs.
- Update `REACT_APP_API_BASE_URL` on `earlybird-front` and `VITE_API_BASE_URL`
  on `earlybird-dashboard` to match the real backend URL.
- These are all baked in at build time, so after changing any of them, trigger
  **Manual Deploy > Clear build cache & deploy** on that service.

## 3. Load the initial database data

Neon doesn't run `earlybird-back/docker/init/dump.sql` automatically the way
`docker-compose` does. Load it once yourself, any time after the Neon project
was created (you don't need to wait for the Render deploy to do this part):

`dump.sql` already contains full `CREATE TABLE` statements plus seed data, so
running it is all you need -- no separate Doctrine migrations step required.

1. From a machine with `psql` installed (fetch the connection string from
   console.neon.tech first, per section 0 -- don't hardcode it anywhere):
   ```bash
   psql "$NEON_CONNECTION_STRING" -f earlybird-back/docker/init/dump.sql
   ```
2. The very first block in that file tries to `CREATE DATABASE` via the
   `dblink` extension -- that's for the local docker-compose setup, where the
   database doesn't exist yet. On Neon the database already exists, so that
   block will likely error out (dblink isn't enabled) and `psql` will just
   move on to the `CREATE TABLE` statements. That's expected, not a sign
   something's broken.

## 4. Things worth knowing about the free tier

- **Render's free web services sleep after 15 minutes idle** and take about a
  minute to wake back up on the next request -- so the first click on your CV
  link may feel slow. This is normal.
- **Neon's free compute scales to zero after 5 minutes idle too**, and wakes
  on the next query (typically under a second, much faster than Render's web
  service wake-up). Unlike Render's free Postgres, Neon's free tier has no
  time limit and your data doesn't expire or get deleted for inactivity.
- Each Dockerfile binds to a fixed port (8000 for the backend, 3000 for the
  frontend, 5173 for the dashboard) rather than reading Render's `$PORT`.
  Render is usually able to auto-detect this, but if a service's first deploy
  fails with a "no open ports detected" error, that's the fix needed.

## 5. How secrets are handled

Nothing real ever gets committed. The pattern:

- `earlybird-back/.env` and `.env.dev` are tracked in git, but only ever hold
  empty placeholders for anything sensitive (`JWT_PASSPHRASE=`,
  `APP_SECRET=`, `DATABASE_URL=`) -- that's Symfony's own convention for
  these files.
- The real values live in `earlybird-back/.env.local` and `.env.dev.local`.
  Symfony loads these automatically and they override the placeholders above.
  Both are covered by `.gitignore` (`/.env.local`, `/.env.*.local`) and never
  get committed.
- The JWT key pair lives at `earlybird-back/config/jwt/{private,public}.pem`,
  generated locally and also git-ignored (`/config/jwt/*.pem`).
- There's also a pre-commit hook (`.githooks/pre-commit`, wired in via
  `git config core.hooksPath .githooks`) that blocks a commit if the staged
  diff looks like it contains a private key, a non-placeholder
  password/secret/token value, or a connection string with credentials baked
  in. Worth keeping active if you clone this fresh -- run the same
  `git config` line to turn it on.

Render never sees `config/jwt/*.pem` either, since its build just clones the
repo -- `earlybird-back/docker/entrypoint.sh` (wired in via the Dockerfile's
`ENTRYPOINT`) writes the key pair at container start from env vars instead,
if the files aren't already present in the image. Locally with
docker-compose nothing changes -- the real files are mounted from disk.

**Set these three env vars manually on Render** (all `sync: false` in
`render.yaml`, so Render prompts for them and never stores them in a file):

- `JWT_PASSPHRASE`: the exact passphrase your local `private.pem` was
  encrypted with.
- `JWT_PRIVATE_KEY_PEM_B64` / `JWT_PUBLIC_KEY_PEM_B64`: base64 of the two
  `.pem` files, generated with:
  ```bash
  base64 -i earlybird-back/config/jwt/private.pem | tr -d '\n'
  base64 -i earlybird-back/config/jwt/public.pem  | tr -d '\n'
  ```
  Base64, not raw PEM, because pasting a raw multi-line PEM into Render's env
  var field is prone to losing its line breaks, which silently corrupts the
  key. Paste each result as a single value with no extra quotes or blank
  lines -- Render's `base64 -d` at container start will fail loudly
  ("invalid input") if anything got mangled in the copy-paste.

If you ever rotate these (new key pair, new passphrase), update all three env
vars on the `earlybird-back` service and redeploy. Everyone's current login
session gets invalidated when you do -- JWTs signed with the old key stop
verifying -- so just expect to sign in again afterward.
