# PLAN — Aftercare Leaflet Generator (php-margus)

Status: draft v1 (2026-11-24). Owner: lead engineer. Implementation: Codex, one task per PR (see [TASKS.md](TASKS.md)).

## 1. Goal and scope

A patient picks the procedure(s) done at the clinic and downloads a colourful, personalised
**A5 PDF aftercare leaflet** in Estonian (default) or English. The PDF is the product, so
most of the effort goes there. Patient accounts and the AI chat are kept simple.

Priority order: (1) catalogue → (2) patient wizard → (3) **PDF** → (4) accounts → (5) AI chat → (6) admin.
Build order differs slightly: the admin panel (P4) comes after the PDF because seeds cover the content until then.

Out of scope for v1: online payments, appointment booking, other languages, native apps.

## 2. Stack and versions

| Concern        | Choice | Notes |
|----------------|--------|-------|
| Runtime        | **PHP 8.3+** | Laravel 13 requires `php ^8.3`. The brief said 8.2+, so the host must offer 8.3 or newer (open question Q1). |
| Framework      | Laravel 13 | Blade, localisation, scheduler, rate limiter |
| Auth           | laravel/breeze 2.x (Blade stack) | localised views |
| DB             | SQLite via PDO, **WAL**, `foreign_keys=ON`, `busy_timeout=5000` | file lives outside the web root |
| PDF            | mpdf/mpdf 8.x | HTML/CSS → PDF, embedded TTF, SVG/PNG |
| QR             | endroid/qr-code (version compatible with PHP 8.3) | PNG output embedded in the PDF |
| Translatable fields | spatie/laravel-translatable | **Dependency justification:** stores et/en text as JSON columns, which halves the schema and the admin forms. |
| Frontend       | Tailwind + Alpine.js (from Breeze), built with Vite **in CI only** | no Node on the server |
| Tests          | PHPUnit (Laravel default) + Laravel Pint | |
| LLM            | provider-agnostic HTTP client (`App\Services\Chat\LlmClient`) | provider chosen in P6 (Q6) |

Dependency rule: every new Composer/npm package gets a one-line justification in §13 of this file.

## 3. Architecture

```
Browser (mobile-first Blade + Alpine)
   │  /{locale}/...
   ▼
Laravel (cPanel, PHP-FPM/LSAPI)
   ├─ SetLocale middleware (et|en from URL prefix)
   ├─ Wizard controllers ── LeafletRequest (validated DTO)
   ├─ LeafletBuilder ── merges procedures → LeafletDocument (view model)
   ├─ PdfRenderer (mPDF) ── Blade views resources/views/pdf/a5/* → PDF bytes (streamed, not stored)
   ├─ Chat: RedFlagDetector → PiiScrubber → ContextBuilder → LlmClient → SafetyFilter
   ├─ Admin (Gate: is_admin) — catalogue CRUD, settings, PDF preview
   └─ Scheduler (cPanel cron every minute): retention purge, nightly DB backup
SQLite file: ~/data/<env>/database.sqlite (outside web root)
```

Key design choices:

1. **PDFs are not stored.** Anonymous PDFs are generated in memory and streamed. Account
   "my leaflets" stores only the *parameters* (procedure ids, locale, first name, date) and
   regenerates the PDF on download behind auth. `storage/leaflets/` is git-ignored and should stay
   empty. If a cache is ever needed, it goes to `storage/app/private/leaflets` with a TTL, and
   downloads go through auth or a signed URL.
2. **QR code points to a non-personal URL**: `/{locale}/l/{slug1+slug2}`, the online version of
   the leaflet content plus the chat. It holds no name and no date, so it needs no expiry.
3. **Theme colours are derived**: admins choose one base colour per category or procedure.
   `ThemeColor` derives `dark` (text-safe on white, ≥4.5:1), `tint` (≈10% for backgrounds) and
   `on` (white or ink, whichever has the higher contrast on the base colour).
4. **Merging by `merge_key`**: advice items carry an optional `merge_key`
   (e.g. `general.no_smoking_48h`). When two or more selected procedures share a key, the item
   is printed once in a "General advice" section.

## 4. Folder structure (target)

```
app/
  Http/Controllers/{WizardController,LeafletController,OnlineLeafletController,ChatController,AccountLeafletController}.php
  Http/Controllers/Admin/{CategoryController,ProcedureController,SettingsController,PreviewController}.php
  Http/Middleware/SetLocale.php
  Models/{Category,Procedure,TimelineStep,AdviceItem,Setting,SavedLeaflet,Consent,User}.php
  Services/Leaflet/{LeafletBuilder,LeafletDocument,PdfRenderer,ThemeColor,QrCodeFactory}.php
  Services/Chat/{LlmClient,ContextBuilder,RedFlagDetector,PiiScrubber,SafetyFilter}.php
  Console/Commands/{RenderSampleLeaflets,PurgeExpiredData,BackupDatabase}.php
config/{leaflet.php,clinic.php,chat.php}
database/migrations, database/seeders/{CategorySeeder,ProcedureSeeder,SettingsSeeder}.php
database/seeders/content/{dental,dermatology,...}.php   # seed content arrays (et+en)
lang/{et,en}/{wizard,leaflet,pdf,admin,account,chat,validation,auth,...}.php
resources/views/{wizard,online,account,admin,layouts}/...
resources/views/pdf/a5/{document,cover,general,procedure,timeline,cards,topics,redflag,back}.blade.php
resources/views/pdf/trifold/...                          # P7
resources/pdf/fonts/{Nunito-*.ttf,SourceSans3-*.ttf,OFL.txt}
resources/pdf/css/a5.css                                  # mPDF-safe CSS only
public/brand/logo.svg
public/illustrations/{category}/{slug}.svg                # flat, original, swappable
scripts/render-pdfs.sh                                    # PDF → PNG via pdftoppm
tests/visual/reference/*.png                              # committed reference renders
tests/visual/output/                                      # git-ignored
.github/workflows/{ci.yml,deploy-staging.yml,deploy-production.yml}
deploy/{post-deploy.sh,rsync-exclude.txt}
```

## 5. SQLite schema

Translatable columns (marked `json·tr`) store `{"et": "...", "en": "..."}` through spatie/laravel-translatable.
Every table has `created_at`/`updated_at` unless noted.

| Table | Columns |
|-------|---------|
| `users` (Breeze) | id, name (nullable: first name only), email unique, password, email_verified_at, `locale` (et/en), `is_admin` bool, `last_active_at`, remember_token |
| `categories` | id, slug unique, `name` json·tr, `icon_path` (svg), `color` (#hex), `sort`, `is_active` |
| `procedures` | id, category_id FK, slug unique, `name` json·tr, `summary` json·tr, `color` nullable (falls back to category), `illustration_path`, `cover_illustration_path` nullable, `status` enum(draft, reviewed, published), `review_note` json·tr (default "DRAFT — must be reviewed by clinic medical staff"), `reviewed_by` nullable, `reviewed_at` nullable, `search_keywords` json·tr, `follow_up` json·tr, `sort`, `is_active` |
| `timeline_steps` | id, procedure_id FK cascade, `phase` enum(first_24h, days_1_3, week_1, full_recovery), `marker` json·tr (e.g. "0–24 h", "Päev 1–3"), `title` json·tr, `body` json·tr, `sort` |
| `advice_items` | id, procedure_id FK cascade, `section` enum(do, dont, pain, eating, hygiene, activity, sleep, red_flag), `text` json·tr, `merge_key` nullable indexed, `sort` |
| `settings` | id, `key` unique, `value` json. Keys: clinic.name (tr), clinic.phone, clinic.after_hours_phone, clinic.emergency (112), clinic.email, clinic.address (tr), clinic.hours (tr), clinic.website, brand.logo_path, brand.colors {primary,secondary,accent,danger,ink,paper,soft}, leaflet.disclaimer (tr), retention.saved_leaflet_months, retention.unverified_account_days |
| `saved_leaflets` | id, uuid unique, user_id FK cascade, `locale`, `procedure_ids` json, `first_name` **encrypted** nullable, `procedure_date` **encrypted** nullable, `format` enum(a5, trifold), `last_downloaded_at` |
| `consents` | id, user_id nullable FK cascade, `purpose` enum(personalised_leaflet, account, chat), `text_version` (e.g. "2026-11-v1"), `locale`, `given_at`, `withdrawn_at` nullable. No IP, no user agent. |
| `sessions`, `cache`, `jobs`, `password_reset_tokens` | Laravel defaults (database driver) |

Not stored: anonymous wizard input after the PDF is streamed, chat messages, IP addresses (beyond the rate limiter's hashed keys in cache).

Connection setup (`AppServiceProvider::boot` or the sqlite connection config): `PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000; PRAGMA synchronous=NORMAL`.
`.env`: `DB_DATABASE=/home/<user>/data/<env>/database.sqlite` (absolute path, outside `public_html`).

## 6. Routes

All public routes have a locale prefix. `/` redirects to `/et` (or `/en` when `Accept-Language` prefers English and no cookie is set).

| Method | URI | Name | Notes |
|--------|-----|------|-------|
| GET | `/{locale}` | home | landing: language choice + "Start" |
| GET | `/{locale}/leaflet` | wizard.procedures | searchable, multi-select picker grouped by category |
| POST | `/{locale}/leaflet/details` | wizard.details | optional first name + date; explicit consent checkbox when either is filled |
| POST | `/{locale}/leaflet/preview` | wizard.preview | HTML preview of the leaflet content + "Download PDF" |
| POST | `/{locale}/leaflet/pdf` | leaflet.pdf | streams the PDF (`attachment`), never stored |
| GET | `/{locale}/l/{combo}` | online.show | online version (no personal data); QR target; hosts the chat |
| POST | `/{locale}/chat` | chat.send | JSON; `throttle:chat` |
| GET | `/{locale}/privacy` | privacy | privacy notice |
| * | `/{locale}/login, register, ...` | Breeze | localised |
| GET | `/{locale}/account/leaflets` | account.leaflets | auth |
| POST | `/{locale}/account/leaflets` | account.leaflets.store | save from the preview (auth) |
| GET | `/{locale}/account/leaflets/{uuid}/pdf` | account.leaflets.pdf | auth + ownership; regenerated |
| DELETE | `/{locale}/account/leaflets/{uuid}` | account.leaflets.destroy | |
| DELETE | `/{locale}/account` | account.destroy | deletes the user, saved leaflets and consents |
| * | `/{locale}/admin/...` | admin.* | `auth` + `can:admin` |
| GET | `/{locale}/admin/procedures/{procedure}/preview.pdf` | admin.preview | inline PDF preview |

The wizard state lives in the session (`wizard.procedures`, `wizard.first_name`, `wizard.date`, `wizard.consent`).
It is cleared after the download, and the session lifetime for these values is 30 minutes.

## 7. PDF pipeline

```
LeafletRequest{locale, procedureIds[], firstName?, date?, format=a5, printMode=false}
  → LeafletBuilder::build()
      - loads procedures (published or draft; drafts get a "DRAFT" watermark in non-production)
      - resolves the theme colour per procedure (ThemeColor)
      - merges advice items: merge_key shared by ≥2 procedures → general section
      - collects red flags: general first, then per procedure (labelled with a coloured dot)
      - resolves clinic settings, logo path, QR (endroid → PNG data URI) for /{locale}/l/{combo}
  → LeafletDocument (pure view model, unit-testable)
  → PdfRenderer::render(doc): string
      - mPDF config: format A5 (148×210), margins from PDF_DESIGN.md, fontDir + fontdata
        (nunito, sourcesans3), default_font=sourcesans3, tempDir=storage/framework/mpdf,
        SetTitle, SetAuthor(clinic), SetLang/`lang` attribute, SetCreator("Lumi leaflet")
      - print mode: sheet larger by 2×3 mm bleed + crop marks (see PDF_DESIGN.md §9)
      - WriteHTML(css, HEADER_CSS) then WriteHTML(blade html, HTML_BODY)
  → response()->streamDownload(..., "lumi-voldik-{date}.pdf")
```

Guarantees: all text comes from lang files or DB translations in the requested locale; fonts are
embedded and subset; icons are SVG drawings (no emoji or font glyphs for ✓ ✕ ⚠); images are SVG or PNG ≥200 dpi at print size.

Visual QA: `php artisan leaflet:render-samples` writes PDFs for a fixed sample matrix to
`tests/visual/output/*.pdf`, and `scripts/render-pdfs.sh` turns them into PNG (pdftoppm, 110 dpi for
review, `-gray` variant for the grayscale check). Reference PNGs are committed under `tests/visual/reference/`.

Sample matrix: (a) one dental procedure, et, with name and date; (b) the same in en; (c) three
procedures from different categories, et (merge test); (d) a long-content procedure (page-break
test); (e) without name or date; (f) print mode with bleed.

## 8. AI chat safety (P6)

1. **Scope**: the context is only the selected procedures' content in the current locale, plus clinic contacts. The system prompt forbids anything outside it.
2. **Red-flag pre-check (deterministic, before any LLM call)**: `RedFlagDetector` matches the
   message against a curated et+en keyword list (global list + each procedure's red-flag items
   tokenised). On a hit, the app returns the canned red-flag reply (clinic phone, after-hours line,
   112) **without calling the LLM**.
3. **PII scrub**: before sending, remove or mask emails, phone numbers, Estonian personal ID codes
   (11 digits), the user's name if known, and dates of birth. No user id, name or email is ever sent.
4. **System rules**: no diagnosis, no dose changes ("follow the dose your doctor prescribed"), say
   "I don't know, please call the clinic" when the answer is not in the context, answer in the
   leaflet language, keep answers short.
5. **Output filter**: if the reply contains dose patterns (`\d+\s?(mg|ml|tabl|g)`) not present in
   the context, or diagnostic phrasing, replace it with a safe fallback.
6. **Disclaimer** always visible above the chat; a "Call clinic" button is always shown.
7. **Rate limits**: 8 messages / 10 min per session, 30 / day per hashed IP, plus a global daily cap
   (`CHAT_DAILY_CAP`). Max 500 characters per message. History: last 6 turns kept client-side only.
8. **No logging** of message content. Only counters (number of messages, red-flag hits) are logged.
9. Kill switch: `CHAT_ENABLED=false` hides the chat entirely.

## 9. GDPR measures (health data, EU/Estonia)

- **Minimisation**: the wizard works with no personal data. First name and date are optional.
  Accounts need only email + password (the name is optional).
- **Lawful basis**: explicit consent (GDPR Art. 9(2)(a)) when personal data is entered (first
  name/date, account). Consent is recorded in `consents` with a text version, and can be withdrawn via account deletion.
- **No storage of anonymous leaflets.** PDFs are streamed, not written to disk.
- **Encryption at rest** for `saved_leaflets.first_name` and `procedure_date` (Laravel `encrypted` cast).
- **Access control**: saved leaflets are behind auth + ownership policy. No public PDF folder.
- **Retention** (`leaflets:purge`, daily): saved leaflets of users inactive > `retention.saved_leaflet_months` (default 12);
  unverified accounts > 30 days; withdrawn consents' data immediately; expired sessions.
- **Right to erasure**: "Delete my data" deletes the user, leaflets and consents at once (cascade).
- **No third parties with personal data**: no analytics or CDN fonts/scripts (everything self-hosted),
  the LLM gets scrubbed text only, and mail goes through the host's SMTP.
- **Security headers**: CSP (self only), HSTS on production, `X-Frame-Options: DENY`, secure + httpOnly + SameSite=Lax cookies.
- Privacy notice page in et+en (the clinic supplies the final text; Q4).
- Backups are as sensitive as the DB: stored outside the web root, `chmod 600`, 14-day rotation.

## 10. Deployment summary

Push to `develop` → staging; push to `main` → production (GitHub Actions). Each run: composer install `--no-dev`,
Vite build, rsync over SSH (FTP fallback) excluding `.env`, `*.sqlite`, `storage/`, then a
post-deploy script (`migrate --force`, caches). Workflows are inactive until the repository variables
`STAGING_ENABLED` / `PRODUCTION_ENABLED` are `true`. Details: [DEPLOYMENT.md](DEPLOYMENT.md).

## 11. Risks

| Risk | Impact | Mitigation |
|------|--------|-----------|
| mPDF CSS limits (no flex/grid, quirky floats) | layout bugs | tables for layout, PDF_DESIGN.md §10 whitelist, PNG review every PDF PR |
| Host only offers PHP 8.2 | Laravel 13 will not run | confirm Q1 early; fallback is Laravel 12 (needs a re-plan of P0) |
| SVG features unsupported by mPDF | missing art | illustrations use plain shapes, inline attributes, no `<style>`/filters/text; PNG fallback |
| SQLite write locks under load | errors | WAL + busy_timeout; writes are rare (admin, accounts) |
| Medical content errors | patient harm | all seeds DRAFT with a visible watermark until an admin marks them reviewed; clinic review (Q3) |
| LLM hallucination | wrong advice | context-only answers, pre-check red flags, output filter, disclaimer, kill switch |
| Shared hosting forbids SSH/rsync | deploy fails | FTP fallback workflow path |
| Large PDF render time on shared CPU | slow downloads | font subsetting, cached SVG→image, target < 3 s for 3 procedures |
| Windows dev vs Linux host | path or line-ending bugs | `.gitattributes` eol=lf, CI on ubuntu |

## 12. Open questions for the clinic

1. **Q1** Which PHP version can the cPanel host run? (Laravel 13 needs 8.3+.) Is SSH access available (needed for rsync)?
2. **Q2** Final domain and staging subdomain, and who owns DNS?
3. **Q3** Who on the medical staff reviews and signs off each procedure's content (the `reviewed_by` workflow)?
4. **Q4** Final privacy notice text, data controller details and DPO contact.
5. **Q5** Retention periods: are 12 months for saved leaflets and 30 days for unverified accounts acceptable?
6. **Q6** LLM vendor for the chat (EU data residency, DPA). Is the chat wanted at launch at all?
7. **Q7** Real clinic phone numbers, address and opening hours (the current ones are placeholders).
8. **Q8** Should the after-hours line appear on every page footer, or only on the red-flag box and back page?
9. **Q9** Are printed booklets planned (then the bleed and saddle-stitch page multiples of 4 matter)?
10. **Q10** Final illustration style and budget: in-house drawings or a commissioned set?

## 13. Dependency log

| Package | Why |
|---------|-----|
| laravel/framework 13 | required stack |
| laravel/breeze (dev) | Blade auth scaffolding (brief) |
| mpdf/mpdf | HTML/CSS → PDF without Chrome or Node (brief) |
| endroid/qr-code | QR codes for the back page (brief) |
| spatie/laravel-translatable | et/en JSON columns; keeps schema and admin forms small |
| anthropic-ai/sdk | official SDK for the default LLM driver (chat, P6); behind the LlmClient interface |
