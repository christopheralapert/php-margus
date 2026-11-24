# TASKS

One task = one branch `feature/<id>-<slug>` from `develop` = one PR into `develop`.
Codex implements; the lead engineer reviews, tests and merges. Tick `[x]` when merged.
**Net** = Codex needs network access (dependency installs or font downloads); otherwise it runs with no network.

Standard checks for every task: `php artisan test` is green, `vendor/bin/pint --test` is clean, and CI is green.
PDF tasks also need: `php artisan leaflet:render-samples && scripts/render-pdfs.sh`, PNGs reviewed against
[PDF_DESIGN.md §11](PDF_DESIGN.md#11-acceptance-checklist-for-every-pdf-pr).

---

## P0 — Skeleton, CI, deploy

### [x] T0.1 Laravel skeleton + Breeze + SQLite config — Net
- **Goal**: a working Laravel 13 app in the repo root with Breeze (Blade), SQLite with WAL, a minimal locale-prefixed home page.
- **Files**: whole Laravel skeleton, `composer.json` (`"php": "^8.3"`), `config/database.php`, `app/Providers/AppServiceProvider.php`
  (SQLite pragmas), `.env.example`, `routes/web.php`, `lang/{et,en}/` (published Laravel lang files + `app.php`), `tests/Feature/SmokeTest.php`.
- **Acceptance**:
  - `composer install && php artisan migrate && php artisan test` pass on a fresh clone.
  - `/` redirects to `/et`; `/et` and `/en` render a placeholder home with a translated title.
  - The SQLite connection runs `journal_mode=WAL`, `foreign_keys=ON`, `busy_timeout=5000` (a test asserts WAL).
  - `.env.example` documents `DB_DATABASE` as an absolute path outside the web root, `APP_URL`, `APP_LOCALE=et`, `APP_FALLBACK_LOCALE=en`.
  - Breeze is installed (Blade stack), and `npm run build` produces `public/build`.
- **Test**: `php artisan test`; `php artisan serve` and open `/`, `/et`, `/en`.

### [x] T0.2 CI workflow
- **Goal**: a GitHub Actions CI workflow on PRs and pushes to `develop`/`main`.
- **Files**: `.github/workflows/ci.yml`, `phpunit.xml` (sqlite `:memory:`), `pint.json`.
- **Acceptance**: the jobs run PHP 8.3 setup → composer install → pint --test → `php artisan test` → `npm ci && npm run build`;
  dependencies are cached; the workflow passes on the PR.
- **Test**: the PR shows a green CI check.

### [x] T0.3 Deploy workflows (staging working, production ready) — Net
- **Goal**: deploy `develop` → staging and `main` → production via rsync over SSH, with FTP as the fallback; inactive until configured.
- **Files**: `.github/workflows/deploy-staging.yml`, `.github/workflows/deploy-production.yml` (or one reusable `_deploy.yml` +
  two callers), `deploy/rsync-exclude.txt`, `deploy/post-deploy.sh`, `docs/DEPLOYMENT.md` (lead only).
- **Acceptance**:
  - Job-level guard `if: vars.STAGING_ENABLED == 'true'` (or `PRODUCTION_ENABLED`); otherwise the job is skipped, not failed.
  - Uses GitHub Environments `staging` / `production` with the secrets listed in DEPLOYMENT.md.
  - Build: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, then removes `node_modules` before upload.
  - rsync excludes `.env`, `*.sqlite*`, `storage/`, `.git`, `node_modules`, `tests`; `--delete` never touches excluded paths.
  - `post-deploy.sh` runs over SSH: create storage dirs if missing, `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`, `storage` perms.
  - The FTP path (`vars.DEPLOY_METHOD == 'ftp'`) uploads the same build with the same excludes and skips post-deploy (documented manual steps).
  - `concurrency` prevents two deploys of the same environment at once.
- **Test**: `actionlint` (if available) on the YAML; a push to `develop` shows the deploy job as *skipped*.

---

## P1 — Schema, seeds, i18n

### [x] T1.1 Schema + models — Net (spatie/laravel-translatable)
- **Goal**: migrations and Eloquent models from PLAN §5.
- **Files**: `database/migrations/*`, `app/Models/*`, `database/factories/*`, `tests/Unit/Models/*`.
- **Acceptance**: all tables/columns from PLAN §5; translatable attributes via spatie; casts (`encrypted` for saved leaflet
  personal fields, enums as PHP backed enums in `app/Enums`); relations (Category→Procedures→TimelineSteps/AdviceItems);
  `Procedure::themeColor()` falls back to the category colour; factories for every model.
- **Test**: `php artisan migrate:fresh` + model unit tests (relations, casts, fallbacks).

### [x] T1.2 i18n + locale routing
- **Goal**: `/{locale}` prefix on all routes, a `SetLocale` middleware, a language switcher, all strings in `lang/et` and `lang/en`.
- **Files**: `app/Http/Middleware/SetLocale.php`, `routes/web.php`, `lang/{et,en}/*.php`, `resources/views/components/lang-switcher.blade.php`,
  published Breeze views localised, `tests/Feature/LocaleTest.php`, `tests/Unit/LangParityTest.php`.
- **Acceptance**: unsupported locale → 404; the switcher keeps the current path; `<html lang>` is correct; Breeze auth pages work under
  `/et/login` etc.; `LangParityTest` fails if a key exists in one locale but not the other.
- **Test**: feature tests for `/et/...`, `/en/...`, `/xx/...`.

### [x] T1.3 Settings, categories, brand assets
- **Goal**: the settings store + seeders for clinic settings and 9 categories; logo and category icons.
- **Files**: `app/Models/Setting.php`, `app/Support/Settings.php` (cached accessor), `database/seeders/{SettingsSeeder,CategorySeeder}.php`,
  `public/brand/logo.svg`, `public/icons/categories/*.svg`, `app/Services/Leaflet/ThemeColor.php`, tests.
- **Acceptance**: all clinic data from the brief is seeded (et+en); the logo is a simple original SVG (a rounded snowflake/drop mark +
  "Lumi Kliinik" wordmark, text converted to paths or drawn shapes so it renders without the font); 9 category icons in the same flat
  style; `ThemeColor` derives dark/tint/on with a unit-tested WCAG contrast; category colours from PDF_DESIGN §4.
- **Test**: unit tests for ThemeColor and Settings; `php artisan db:seed`.

### [x] T1.4 Procedure seed content (et+en, DRAFT)
- **Goal**: 12–15 example procedures (1–2 per category) with full content blocks.
- **Files**: `database/seeders/ProcedureSeeder.php`, `database/seeders/content/*.php`, placeholder illustrations `public/illustrations/{category}/*.svg`.
- **Acceptance**: each procedure has a summary, 4 timeline steps, ≥4 do's, ≥4 don'ts, every topic (pain, eating, hygiene, activity, sleep),
  ≥3 red-flag items, follow-up text, search keywords, in **both** et and en; status `draft`; general items share `merge_key`s
  (e.g. `general.no_smoking_48h`, `general.no_alcohol_24h`, `general.no_sauna_week1`, `general.keep_wound_clean`); medication text never
  states doses ("as prescribed by your doctor"); natural Estonian with correct õ ä ö ü š ž.
  Suggested set: dental (tooth extraction, dental implant), dermatology (mole removal, cryotherapy), aesthetic (botulinum injection,
  dermal filler), eye (cataract surgery), ENT (tonsillectomy, nosebleed cauterisation), surgery (wound suturing), ortho/physio
  (knee arthroscopy), gynaecology (IUD insertion), vaccination (vaccination, generic).
- **Test**: a seeder test asserting counts and that every translatable field has non-empty et and en values.

---

## P2 — Patient wizard

### [x] T2.1 Landing + procedure picker
- **Goal**: a mobile-first landing (language choice + start) and a searchable multi-select procedure picker grouped by category, with icons.
- **Files**: `app/Http/Controllers/WizardController.php`, `resources/views/wizard/{home,procedures}.blade.php`, `resources/js/wizard.js`
  (Alpine filter), `lang/*/wizard.php`, tests.
- **Acceptance**: works at 360 px width without horizontal scroll; the search filters by name + keywords client-side, without diacritics
  sensitivity (`o` matches `õ/ö`); selection persists in the session; no JS still allows selecting via checkboxes; tap targets ≥ 44 px;
  inactive procedures are hidden; accessible labels.
- **Test**: feature tests (list, select, validation: at least one procedure); manual check in browser dev-tools mobile view.

### [ ] T2.2 Details, consent, preview, download
- **Goal**: optional first name + date → consent → HTML preview → PDF download (renders via `PdfRenderer`; until P3 it is a minimal PDF).
- **Files**: `WizardController`, `LeafletController`, `app/Http/Requests/LeafletRequest.php`, `app/Services/Leaflet/{LeafletBuilder,LeafletDocument,PdfRenderer}.php`
  (skeleton), `app/Models/Consent.php` usage, views, tests.
- **Acceptance**: the first name is max 40 chars, letters/spaces/hyphen only; the date is not in the future by more than 1 day; when a name or date
  is entered, an unticked consent checkbox is required and a `consents` row (purpose `personalised_leaflet`, no user, no IP) is created;
  the preview shows the merged content; the download streams `application/pdf` with a filename; nothing is written to disk; the session wizard
  data is cleared after download.
- **Test**: feature tests including "no file created in storage" and consent behaviour.

---

## P3 — A5 PDF leaflet (main effort)

Every P3 task: run `php artisan leaflet:render-samples && scripts/render-pdfs.sh` and attach the PNGs to the PR.

### [ ] T3.1 Fonts, base layout, render tooling — Net (fonts)
- **Goal**: mPDF configured with embedded Nunito + Source Sans 3, A5 pages, footer, page numbers, metadata, the base CSS, and the render scripts.
- **Files**: `config/leaflet.php`, `resources/pdf/fonts/*` (+ `OFL.txt`), `resources/pdf/css/a5.css`, `resources/views/pdf/a5/{document,footer}.blade.php`,
  `app/Services/Leaflet/PdfRenderer.php`, `app/Console/Commands/RenderSampleLeaflets.php`, `scripts/render-pdfs.sh`, tests.
- **Acceptance**: PDF_DESIGN §2, §5, §7.1; `pdffonts` shows only the embedded subset fonts; the glyph fixture string renders in all weights;
  title + language metadata set; `render-pdfs.sh` writes `tests/visual/output/*.png` (110 dpi) and supports `--gray`; works on Windows Git Bash and Ubuntu.
- **Test**: a unit test asserting PDF bytes start with `%PDF`, page size A5 and metadata; visual review of the PNGs.

### [ ] T3.2 Cover + illustrations
- **Goal**: the cover page per PDF_DESIGN §8 (Cover) and a consistent placeholder illustration set.
- **Files**: `resources/views/pdf/a5/cover.blade.php`, CSS, `public/illustrations/**`, `resources/pdf/icons/*.svg`.
- **Acceptance**: single and multi-procedure covers; name/date only if given; the draft notice; no page number; illustrations follow §6 SVG rules.
- **Test**: renders (a), (c), (e).

### [ ] T3.3 Procedure header band + timeline
- **Files**: `procedure.blade.php`, `timeline.blade.php`, CSS, icon helpers.
- **Acceptance**: §7.2–7.4; the theme colour is used everywhere it should be; steps never split across pages.

### [ ] T3.4 Do/Don't cards + topic tiles + follow-up
- **Files**: `cards.blade.php`, `topics.blade.php`, `followup.blade.php`, CSS.
- **Acceptance**: §7.5, §7.6, §7.8; the fixed medication line is always present; grayscale-safe.

### [ ] T3.5 Red-flag box
- **Files**: `redflag.blade.php`, CSS.
- **Acceptance**: §7.7; never splits; the phone and 112 are the largest text on the page; clinic and after-hours numbers come from settings.

### [ ] T3.6 Multi-procedure merge
- **Files**: `LeafletBuilder.php`, `general.blade.php`, tests.
- **Acceptance**: items with a `merge_key` shared by ≥2 selected procedures appear once in "General advice" and are removed from the procedure
  sections; red flags are deduplicated the same way; each procedure keeps its own coloured section; order follows the user's selection.
- **Test**: unit tests on `LeafletBuilder` with 1, 2 and 3 procedures; render (c).

### [ ] T3.7 Back page + QR
- **Files**: `back.blade.php`, `app/Services/Leaflet/QrCodeFactory.php`, route stub `online.show` returning content (full page in P6).
- **Acceptance**: §7.10 and the back page wireframe; the QR encodes `APP_URL/{locale}/l/{combo}` with no personal data and scans correctly
  (decode test with a QR reader library is optional; manual scan otherwise); the generation date is in locale format (et `24.11.2026`, en `24 Nov 2026`).

### [ ] T3.8 Visual QA, print mode, reference renders
- **Files**: `scripts/render-pdfs.sh`, `tests/visual/reference/*.png`, `tests/visual/README.md`, `PdfRenderer` print mode.
- **Acceptance**: the full sample matrix (PLAN §7) incl. print mode with bleed + crop marks and the notes-page padding to a multiple of 4;
  reference PNGs committed; `scripts/render-pdfs.sh --compare` reports changed pages (pixel diff via ImageMagick `compare` if installed, otherwise
  checksum) — informational, not a CI gate.

---

## P4 — Admin panel

### [ ] T4.1 Admin shell + categories CRUD
- **Acceptance**: `is_admin` gate; `php artisan app:make-admin {email}` command; a simple Tailwind layout; categories CRUD with et+en fields,
  colour picker (hex validation), icon SVG upload (sanitised: no scripts or external refs), sort, active toggle.
### [ ] T4.2 Procedures CRUD
- **Acceptance**: et/en tabs for every text field; timeline steps and advice items editable with add/remove/reorder; `merge_key` field;
  illustration upload (SVG sanitised or PNG ≥ 200 dpi check); status workflow (draft → reviewed requires the reviewer name; published);
  validation errors in the right locale.
### [ ] T4.3 Clinic settings
- **Acceptance**: edit all `settings` keys (contacts, hours et/en, brand colours, disclaimer); logo upload replaces `public/brand/logo.svg`
  (the previous one is kept as a backup); the settings cache is cleared on save.
### [ ] T4.4 PDF preview of any procedure
- **Acceptance**: "Preview PDF (et/en)" buttons on procedure edit; inline PDF; draft watermark shown.

## P5 — Patient accounts

### [ ] T5.1 Accounts + my leaflets
- **Acceptance**: localised register/login/verify/reset (Breeze); the account consent checkbox records a `consents` row; "Save to my account"
  on the preview; a "My leaflets" list (procedures, date created); re-download regenerates the PDF (auth + policy); a user can delete one leaflet.
### [ ] T5.2 Delete my data + retention + privacy page
- **Acceptance**: "Delete my data" (password confirm) removes the user, leaflets and consents; `leaflets:purge` runs daily and applies PLAN §9 retention;
  `db:backup` command (`VACUUM INTO`, gzip, 14-day rotation, chmod 600); a privacy page (et+en placeholder text marked for clinic review).

## P6 — AI chat

### [ ] T6.1 Chat backend
- **Acceptance**: PLAN §8 fully: `LlmClient` interface + one HTTP driver (provider via `.env`: `LLM_PROVIDER`, `LLM_API_KEY`, `LLM_MODEL`,
  `LLM_BASE_URL`, `CHAT_ENABLED`, `CHAT_DAILY_CAP`), a fake driver for tests, RedFlagDetector before any call, PiiScrubber, output SafetyFilter,
  rate limits, no message logging. Tests cover: a red-flag message never reaches the LLM; emails/phones/ID codes are scrubbed; out-of-scope answers fall back.
### [ ] T6.2 Online version page + chat UI
- **Acceptance**: `/{locale}/l/{combo}` shows the leaflet content in HTML (same merge logic) + the chat box with disclaimer, the "Call clinic"
  button and the 112 reminder; works without JS (chat hidden); mobile-first.

## P7 — After release

### [ ] T7.1 A4 landscape tri-fold PDF (PDF_DESIGN §2, §8)
### [ ] T7.2 Polish: accessibility audit (WCAG AA), empty/error states, 404/500 pages in et+en, performance (render < 3 s for 3 procedures)
### [ ] T7.3 Security review: headers/CSP, upload sanitising, authz tests, dependency audit (`composer audit`, `npm audit`), rate limits
### [ ] T7.4 Release v1.1

## Release

### [ ] R1 Release PR `develop` → `main` after P0–P6 (the owner merges)
