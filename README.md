# Pwesto Backend

LavaLust PHP backend for Pwesto. This folder is the backend deployment root.

## Requirements

- PHP 8.x with `curl`, `PDO_SQLite`, `PDO_MySQL`, and OpenSSL enabled
- Composer 2
- Node.js is only needed if rebuilding the separate React frontend

## Local setup

1. Copy `.env.example` to `.env` and set the backend configuration. Keep `.env`
   private and out of source control.
2. From this folder, install PHP dependencies with `composer install`.
3. Generate an application key with `php lava key:generate` if `APP_KEY` is
   blank. Keep the same key between deployments.
4. Start the development server with `php lava serve`.

The backend runs at `http://127.0.0.1:3000/`. The root page is the LavaLust
Framework landing page, the Pwesto application is at `/pwesto`, and APIs are
served under `/api/`.

Pwesto uses SQLite by default. To use local Laragon MySQL, create a database
and set `PWESTO_DB_DRIVER=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`,
`DB_NAME`, `DB_USER`, and `DB_PASSWORD` in `.env`. Loopback MySQL connections
do not require a TLS CA file; remote MySQL connections require verified TLS.

Build the React application from the separate `Pwesto Frontend` project before
deploying so its output is available in `public/assets/`.

### Render SQLite storage

For a Render deployment using SQLite, attach a persistent disk to the web
service at `/var/data`, then set these environment variables:

```text
PWESTO_DB_DRIVER=sqlite
PWESTO_DB_PATH=/var/data/pwesto.sqlite
```

The Docker entrypoint prepares the mounted directory for Apache's `www-data`
user. Without a persistent disk, SQLite data on Render can be lost when the
service restarts or redeploys. Do not set `DB_HOST=localhost` on Render;
localhost there refers to the Render container, not your computer.

To restore the six approved local sample stall listings, temporarily set
`PWESTO_RESTORE_APPROVED_STALLS=1` on the Render service and deploy this
version. The restore is idempotent: it inserts missing sample stalls and
approves the matching existing sample rows without changing their other fields.
It does not copy user accounts. Unowned listings cannot accept orders until a
real vendor account claims or replaces them. Remove the variable after
confirming the listings appear.

### Google sign-in on Render

In Google Cloud Console, open the OAuth 2.0 client used by this app (type
**Web application**) and add the origins where the sign-in page is opened to
**Authorized JavaScript origins**. For this deployment, add:

```text
https://pwesto-backend.onrender.com
https://pwesto-frontend.onrender.com
http://127.0.0.1:3000
http://localhost:3000
http://127.0.0.1:5173
```

Set `GOOGLE_CLIENT_ID` on the Render backend to that same Web application
client ID, save the OAuth client, and redeploy the backend if its environment
variable changed. The `origin_mismatch` error is enforced by Google and cannot
be fixed by changing the sign-in code alone.

## Docker

Build this image from this folder after building the frontend:

```powershell
docker build -t pwesto-backend .
docker run --env-file .env -p 8080:80 `
  -v pwesto-runtime:/var/www/html/runtime `
  -v pwesto-uploads:/var/www/html/public/uploads pwesto-backend
```

Keep the runtime and upload volumes persistent. Configure production credentials
as environment variables; never put secrets in frontend `VITE_` variables.
