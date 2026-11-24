# AGENTS.md — rules for coding agents (Codex)

Read this file first, every time. Then read the task text you were given and the referenced sections of
`docs/PLAN.md` and `docs/PDF_DESIGN.md`.

## Rules

1. Work only on the current feature branch. Never commit to `main` or `develop` directly, and never push.
   (The lead engineer commits, pushes and opens PRs. Leave your changes in the working tree, uncommitted.)
2. Do one task only, and don't make unrelated changes (no drive-by refactors, no reformatting of untouched files).
3. PHP only on the server: mPDF for PDFs, SQLite for the database, nothing that needs Node, Chrome or a daemon at runtime.
   Node is allowed only for building CSS/JS (Vite) in development and CI.
4. Every user-facing string goes in `lang/et/*.php` and `lang/en/*.php`. `et` is the default locale. No hard-coded
   UI text in Blade, controllers, PDFs or emails. Keep both locales' keys identical.
5. For PDF work, use only CSS that mPDF supports (`docs/PDF_DESIGN.md` §10), and run the render script after every change:
   `php artisan leaflet:render-samples && bash scripts/render-pdfs.sh` (once T3.1 exists). Look at the PNGs.
6. Write or update tests, and run them before finishing: `php artisan test` and `vendor/bin/pint --test`
   (run `vendor/bin/pint` on files you changed to fix the style).
7. No new dependency without a one-line justification added to the dependency log in `docs/PLAN.md` §13.
   (This is the only change you may make in `docs/`.)
8. Never commit secrets, `.env` or `.sqlite` files. Add every new env variable to `.env.example` with a safe default and a comment.
9. Never hard-code domains or URLs; use `config('app.url')` / `route()` / `url()`.
10. Health data: collect as little as possible; never write patient PDFs to a public folder; never log personal data or chat messages.
11. Medical content: never state medication doses; always "as prescribed by your doctor" / "nii, nagu arst on määranud".
    Seed content is marked DRAFT.
12. Finish with a summary: the files you changed (grouped), the commands you ran with their results, and anything left unclear or not done.

## Environment notes

- Development machine: Windows 11 with Git Bash; CI and the server run Linux. Use forward slashes and LF line endings;
  shell scripts must start with `#!/usr/bin/env bash` and work on both.
- PHP 8.3 CLI with pdo_sqlite, gd, intl, mbstring, zip. Composer is on the PATH (`composer`).
- `pdftoppm` / `pdffonts` (Poppler) are on the PATH for PDF rendering checks.
- Tests use SQLite `:memory:`. Local dev DB: `database/database.sqlite` (git-ignored).
- Network access is off unless the task says **Net**. If you need a package and have no network, stop and say so in the summary.

## Code conventions

- Laravel defaults: PSR-12 via Pint, typed properties and return types, `final` classes for services, constructor injection.
- Controllers stay thin; logic lives in `app/Services/...`. Validation in Form Requests.
- Enums in `app/Enums` (backed enums). Config in `config/leaflet.php`, `config/clinic.php`, `config/chat.php`.
- Blade components for repeated UI; Tailwind utility classes; Alpine.js for small interactions; mobile-first.
- Tests: feature tests for HTTP flows, unit tests for services. Use factories, not hand-built arrays.
