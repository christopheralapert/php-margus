# DEMO — where everything is, and the free Render demo

This file lives on the branch **`feature/demo-render`** only. The full product stays on `develop` and `main`,
unchanged by the demo setup.

## 1. Branches: where each version lives

| Branch | What it contains | Status |
|--------|------------------|--------|
| `main` | Production. Only the initial repo files until release PR #1 is merged. | Waiting for the owner to merge PR #1 |
| `develop` | **The full version, P0–P6**: wizard, A5 PDF, admin, accounts, GDPR, AI chat. 465 tests, CI green. | Source of release PR #1 (`develop` → `main`) |
| `feature/demo-render` | `develop` **plus** the free Render demo setup (Dockerfile, `render.yaml`, demo banner, this file) | Deploys the public demo; not merged anywhere |
| `feature/T7.1-a4-trifold` | P7: A4 landscape tri-fold PDF | In review (design fix round) |
| `feature/T7.2-polish-a11y` | P7: accessibility pass, et/en error pages, faster PDF | Committed, waiting until PR #1 is merged |
| `feature/T7.3-security-review` | P7: security headers (CSP), encrypted sessions, review fixes | Committed, waiting until PR #1 is merged |
| `feature/T0.x…T6.x`, `fix/…` | Finished task branches, all merged into `develop` | Done (kept for history) |

The P7 branches are deliberately **not** merged into `develop` yet. Everything pushed to `develop` is added to the open
release PR automatically. They will be merged after PR #1, as a second release.

## 2. Where things are in the project

| What | Where |
|------|-------|
| Plan, tasks, PDF design, deployment | `docs/PLAN.md`, `docs/TASKS.md`, `docs/PDF_DESIGN.md`, `docs/DEPLOYMENT.md` |
| Rules for the coding agents | `AGENTS.md` |
| Routes (all under `/et/...` and `/en/...`) | `routes/web.php`, `routes/auth.php`, `routes/admin-settings.php`, `routes/console.php` (schedule) |
| Patient wizard | `app/Http/Controllers/WizardController.php`, `resources/views/wizard/` |
| Online leaflet + chat page (QR target) | `app/Http/Controllers/OnlineLeafletController.php`, `resources/views/online/`, `resources/js/chat.js` |
| Leaflet logic (merge, builder) | `app/Services/Leaflet/` (`LeafletBuilder`, `LeafletDocument`, `SharedAdvice`, `PdfAdvice`) |
| PDF rendering (mPDF) | `app/Services/Leaflet/PdfRenderer.php`, templates `resources/views/pdf/a5/`, CSS `resources/pdf/css/a5.css`, fonts `resources/pdf/fonts/`, icons `resources/pdf/icons/` |
| PDF visual QA | `php artisan leaflet:render-samples`, `scripts/render-pdfs.sh` (`--gray`, `--compare`), reference PNGs `tests/visual/reference/`, guide `tests/visual/README.md` |
| AI chat (safety pipeline) | `app/Services/Chat/` (`RedFlagDetector`, `PiiScrubber`, `ContextBuilder`, `SafetyFilter`, `AnthropicLlmClient`), `config/chat.php` |
| Admin panel | `app/Http/Controllers/Admin/`, `app/Services/Admin/` (incl. `SvgSanitizer`), `resources/views/admin/` |
| Accounts, delete my data, retention, backup | `AccountLeafletController`, `app/Services/{DeleteUserData,PurgeExpiredData,BackupDatabase}.php` |
| Clinic settings (phone, colours, disclaimer…) | DB table `settings`, defaults in `config/clinic.php`, accessor `app/Support/Settings.php` |
| Procedure content (15 DRAFT procedures, et + en) | `database/seeders/content/*.php` (shared advice in `_general.php`) |
| Translations | `lang/et/*.php`, `lang/en/*.php`, `lang/et.json`, `lang/en.json` |
| Logo, category icons, illustrations | `public/brand/`, `public/icons/categories/`, `public/illustrations/` |
| CI and deploy workflows | `.github/workflows/` (`ci.yml`, `_deploy.yml`, `deploy-staging.yml`, `deploy-production.yml`), `deploy/` |
| **Demo (this branch only)** | `Dockerfile`, `.dockerignore`, `docker/`, `render.yaml`, `docs/DEMO.md` |

## 3. Why a demo on Render

The intended host (cPanel at ita.voco.ee) has PHP 8.4 **without `mbstring` and `gd`**, which mPDF requires, and the
admin will not install them. Until a suitable cPanel host is found, the demo runs on Render's free plan in a Docker
container that includes every PHP extension.

Free-plan behaviour, and what it means:
- **It sleeps after ~15 minutes without visitors.** The first request after that takes about 30–60 s to wake it up.
- **The disk is temporary.** The SQLite database is created and seeded with the demo content on every start, so
  accounts, saved leaflets and admin edits disappear after a restart or a new deploy. A banner on every page says so.
- **Do not enter real health data.** It's a demo.
- Emails are not sent (`MAIL_MAILER=log`). In demo mode, new accounts are verified automatically.
- The AI chat is **off** (`CHAT_ENABLED=false`).

## 4. Set up the Render demo (one time, about 10 minutes)

The demo needs no server work. Render builds the `Dockerfile` from this branch, and `render.yaml` (a Render
"Blueprint") sets everything else.

1. Push this branch: `git push origin feature/demo-render`. The CI workflow also builds the Docker image on this
   branch and smoke-tests `/up`, `/et` and `/et/leaflet`. Wait until it is green (GitHub → Actions).
2. Create a free account at **render.com** (sign in with GitHub is easiest) and allow Render to access the
   `php-margus` repository (it is private, so Render needs that permission).
3. In the Render dashboard: **New → Blueprint** → choose the `php-margus` repository → branch
   **`feature/demo-render`**. Render reads `render.yaml` and shows one web service, `php-margus-demo` (free plan).
4. Render asks for the two values marked `sync: false`:
   - `DEMO_ADMIN_EMAIL`: the login email for the demo admin (any address, it is not verified).
   - `DEMO_ADMIN_PASSWORD`: a password you choose (Render stores it as a secret; it is never in the repo).
   `APP_KEY` is generated by Render automatically.
5. Click **Apply / Deploy**. The first build takes about 5–10 minutes. When it is "Live", the demo is at
   `https://php-margus-demo.onrender.com` (or similar; Render shows the exact URL).
6. Try: `/et` (wizard and PDF download), `/et/l/tooth-extraction` (online page), `/et/login` with the demo admin,
   then `/et/admin` (categories, procedures, settings, PDF preview).

Updating the demo: every push to `feature/demo-render` redeploys it automatically (`autoDeploy: true`).
To demo newer work (for example P7), merge that branch into `feature/demo-render` and push.

### What the demo setup changes (only on this branch)

| File | Purpose |
|------|---------|
| `Dockerfile`, `.dockerignore` | PHP 8.4 + Apache with every extension (mbstring, gd, intl, zip, …), built Vite assets, `composer install --no-dev` |
| `docker/start.sh` | On every start: derive `APP_KEY`, create and seed `/tmp/demo/database.sqlite`, create the demo admin, cache config/routes/views, start Apache on `$PORT` |
| `docker/apache.conf` | Document root `public/`, no access log (no URLs or IP addresses stored), small worker limits for the free plan |
| `render.yaml` | Render Blueprint: free web service, health check `/up`, environment variables |
| `app/Console/Commands/DemoAdmin.php` | `php artisan app:demo-admin` creates the verified admin from `DEMO_ADMIN_EMAIL` / `DEMO_ADMIN_PASSWORD` |
| `bootstrap/app.php`, `.env.example` | `TRUSTED_PROXIES` (set to `*` on Render, so https links and secure cookies work behind its proxy; unset on cPanel) |
| Demo banner (`components/demo-banner`, `lang/*/demo.php`) and auto-verify on registration | Only when `DEMO_MODE=true` |
| `.github/workflows/ci.yml` | Extra `docker` job: builds and smoke-tests the image; also runs on pushes to this branch |

### If the deploy fails

- Render → the service → **Logs** shows the build and start output. `start.sh` prints a clear message if `APP_KEY`
  is missing.
- The free plan has 512 MB RAM. If the PDF download fails with an out-of-memory error, lower
  `MaxRequestWorkers` in `docker/apache.conf` to 2.
