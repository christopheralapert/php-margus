# php-margus — Aftercare Leaflet Generator

A bilingual (Estonian default, English) web app. A patient picks the
procedure(s) done at the clinic and downloads a colourful, personalised
A5 PDF aftercare leaflet ("voldik").

- Stack: Laravel 13, PHP 8.3+, SQLite, Blade, Breeze, mPDF, endroid/qr-code
- Hosting: cPanel shared hosting; assets are built in CI and only built files are deployed
- Demo clinic: "Lumi Kliinik" (fictional, editable in admin settings)

## Branches

| Branch      | Purpose                                     |
|-------------|---------------------------------------------|
| `main`      | production; changed only by release PRs     |
| `develop`   | integration; auto-deploys to staging        |
| `feature/*` | one task each, from `develop`, PR back into `develop` |

## Docs

- [docs/PLAN.md](docs/PLAN.md): architecture, schema, routes, GDPR
- [docs/TASKS.md](docs/TASKS.md): phased task list
- [docs/PDF_DESIGN.md](docs/PDF_DESIGN.md): the leaflet design system
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md): cPanel and GitHub Actions setup
- [AGENTS.md](AGENTS.md): rules for coding agents

## Local development

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm ci && npm run build
php artisan serve
```

> Medical content in the seed data is DRAFT and must be reviewed by clinic
> medical staff before use.
