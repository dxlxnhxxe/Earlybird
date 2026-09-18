# Deploying Earlybird to Render (free tier) + Neon

This repo has a `render.yaml` Blueprint that deploys the Symfony API, the
React/Vite frontend, and the Vite/React dashboard to Render. The Postgres
database itself lives on Neon (neon.tech), not Render -- Render's own free
managed Postgres expires after 30 days, and Neon's free tier doesn't. It
intentionally leaves out the `reverse-proxy` service from
`docker-compose.yml` -- on Render each web service already gets its own
public HTTPS URL, so the nginx reverse-proxy (which routed by hostname, e.g.
`earlybird-api`) isn't needed and isn't part of this Blueprint.

## 0. Your Neon database (already created)

A free Neon project called `earlybird` was already created for you (Postgres
16, matching what `docker-compose.yml` uses locally). Get its connection
string yourself at [console.neon.tech](https://console.neon.tech) -- open
the `earlybird` project, Connect, copy the connection string for the
`earlybird` database. You'll paste it into Render as `DATABASE_URL` in step 1
below.

**Do not paste the connection string into this file, a commit, or anywhere
else that ends up in git.** An earlier version of this doc did exactly that
(committed the real string in plaintext) and GitHub's/Neon's secret scanning
flagged it within hours of the push -- the password has since been rotated,
but treat that as a close call, not a non-issue. `DATABASE_URL` is
`sync: false` in `render.yaml` specifically so Render prompts you for it
interactively instead of it living in a file; keep it that way.

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
   - `JWT_PRIVATE_KEY_PEM` / `JWT_PUBLIC_KEY_PEM`: see section 5 below.
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

## 5. JWT keys (rotated, no longer committed)

The old JWT key pair and passphrase were committed to the repo despite
`.gitignore` trying to exclude them -- if this repo is public, anyone could
have forged valid JWTs for your API. This has been fixed:

- A brand new key pair and passphrase were generated locally
  (`earlybird-back/config/jwt/{private,public}.pem`, and the new passphrase is
  in `earlybird-back/.env`). The old ones are no longer valid anywhere once
  you deploy with the new files.
- Those two `.pem` files are no longer tracked by git (removed with
  `git rm --cached`) -- they stay on your machine and inside any container
  built from your machine, but won't be pushed to GitHub going forward.
- Because the backend's Dockerfile does `COPY . .` from the git checkout,
  Render's build won't have these files either. `earlybird-back/docker/entrypoint.sh`
  (wired in via the Dockerfile's new `ENTRYPOINT`) writes them from the
  `JWT_PRIVATE_KEY_PEM` / `JWT_PUBLIC_KEY_PEM` env vars instead, if the files
  aren't already present in the image. Locally, via docker-compose, nothing
  changes -- the real files are still mounted from disk and those env vars
  are simply unset.
- When creating the Blueprint on Render (step 1 above), paste the contents of
  your local `earlybird-back/config/jwt/private.pem` into `JWT_PRIVATE_KEY_PEM`,
  and `public.pem` into `JWT_PUBLIC_KEY_PEM` (paste the whole file, including
  the `-----BEGIN/END-----` lines).

**One thing this doesn't do:** the *old* key and passphrase are still visible
in this repo's git history (past commits), even though they're no longer used
anywhere. Anyone who found them there couldn't do anything with them once
you've deployed with the new key -- but if you want them gone from history
entirely, that requires rewriting git history (`git filter-repo` + a force
push), which would change every commit hash on every branch and could break
other clones or open PRs. Given this repo has several other branches
(`main`, `develop-dylan(team)`, `nerimene-tdtpj`, etc.), this was not done --
say the word if you want it done and understand the trade-off.
