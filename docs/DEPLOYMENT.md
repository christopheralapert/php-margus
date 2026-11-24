# DEPLOYMENT — cPanel + GitHub Actions

The production domain and staging subdomain are **not decided yet**. Nothing in the code hard-codes a
domain: the app uses `APP_URL` from `.env`, and the deploy targets live in GitHub Environments.
The deploy workflows are **ready but inactive** until the variables below are set.

## 1. Server requirements

- cPanel shared hosting with **PHP 8.3 or newer** (Laravel 13 requires it). Select it in
  *MultiPHP Manager* (or *Select PHP Version*) for both domains.
- PHP extensions: `pdo_sqlite`, `sqlite3`, `mbstring`, `gd`, `zip`, `intl`, `fileinfo`, `openssl`, `curl`, `ctype`, `dom`, `xml`, `bcmath` (optional).
- `memory_limit` ≥ 256M (mPDF), `max_execution_time` ≥ 60.
- SSH access ("Terminal" / "SSH Access" in cPanel) for rsync deploys. Without SSH, use the FTP fallback (§6).
- No Node, Chrome or daemons are needed on the server.

## 2. Directory layout on the server

```
/home/<cpaneluser>/
├── apps/
│   ├── php-margus-staging/        ← Laravel app (deploy target, STAGING_PATH)
│   │   ├── public/                ← the staging subdomain's document root
│   │   ├── storage/               ← writable, never overwritten by deploys
│   │   └── .env                   ← created by hand, never overwritten
│   └── php-margus-production/     ← same for production
├── data/
│   ├── staging/database.sqlite    ← outside the web root, chmod 600
│   ├── production/database.sqlite
│   └── backups/{staging,production}/
└── public_html/                   ← untouched (or a symlink, see §3)
```

## 3. Domains and document root

1. *Domains* → *Create a New Domain* (or *Subdomains*) for staging, e.g. `staging.<domain>`.
2. Set its **Document Root** to `apps/php-margus-staging/public`. Do the same for production with
   `apps/php-margus-production/public`.
3. **If the document root cannot be changed** (e.g. the primary domain is fixed to `public_html`):
   - Preferred: replace `public_html` with a symlink (via SSH):
     `mv ~/public_html ~/public_html.bak && ln -s ~/apps/php-margus-production/public ~/public_html`
   - Fallback (no symlinks allowed): upload the app to `~/apps/php-margus-production`, copy only `public/*` into
     `public_html`, and edit `public_html/index.php` so its paths point to `../apps/php-margus-production/`.
     (The deploy workflow supports this with `vars.PUBLIC_DIR_OVERRIDE`; document it in the PR when it is used.)
     Never put the whole app inside `public_html` with a rewrite `.htaccess`: `.env` and `storage` would be one misconfiguration away from public.
4. Enable *SSL/TLS Status* → AutoSSL for both domains. Force HTTPS (`APP_URL=https://...`; the app sets HSTS in production).

## 4. First-time setup (per environment)

```bash
# over SSH
mkdir -p ~/apps/php-margus-staging ~/data/staging ~/data/backups/staging
touch ~/data/staging/database.sqlite && chmod 600 ~/data/staging/database.sqlite
# after the first deploy has uploaded the code:
cd ~/apps/php-margus-staging
cp .env.example .env            # then edit it, see below
php artisan key:generate
php artisan migrate --force --seed
php artisan app:make-admin you@example.com   # after P4
chmod -R ug+rwX storage bootstrap/cache
```

`.env` essentials:
```
APP_ENV=staging            # production on prod
APP_DEBUG=false
APP_URL=https://<staging-domain>
APP_LOCALE=et
APP_FALLBACK_LOCALE=en
DB_CONNECTION=sqlite
DB_DATABASE=/home/<cpaneluser>/data/staging/database.sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=smtp           # cPanel mail account
LLM_PROVIDER= / LLM_API_KEY= / LLM_MODEL= / CHAT_ENABLED=false   # see P6
```

## 5. Cron jobs (cPanel → Cron Jobs)

Use the full PHP path that cPanel shows for the selected version (e.g. `/usr/local/bin/ea-php83` or `/opt/cpanel/ea-php83/root/usr/bin/php`).

| Schedule | Command | Purpose |
|----------|---------|---------|
| `* * * * *` | `cd ~/apps/php-margus-production && /usr/local/bin/ea-php83 artisan schedule:run >> /dev/null 2>&1` | Laravel scheduler (retention purge `leaflets:purge` daily, session cleanup) |
| `15 3 * * *` | `cd ~/apps/php-margus-production && /usr/local/bin/ea-php83 artisan db:backup --to=$HOME/data/backups/production --keep=14 >> $HOME/data/backups/backup.log 2>&1` | nightly SQLite backup (`VACUUM INTO`, gzip, chmod 600) |

Repeat both for staging (the backup can keep 3 days on staging). Until P5 ships `db:backup`, use:
`sqlite3 ~/data/production/database.sqlite ".backup '$HOME/data/backups/production/db-$(date +\%F).sqlite'"` (if `sqlite3` exists on the host).

## 6. GitHub Actions configuration

Workflows: `.github/workflows/deploy-staging.yml` (push to `develop`) and `deploy-production.yml` (push to `main`).
Each run: `composer install --no-dev --optimize-autoloader` → `npm ci && npm run build` → remove `node_modules` →
rsync over SSH (`--delete`, excluding `.env`, `*.sqlite*`, `storage/`, `.git`, `tests`, `node_modules`) → run `deploy/post-deploy.sh`
on the server (`migrate --force`, `config:cache`, `route:cache`, `view:cache`).

### 6.1 Create the environments
*Repo → Settings → Environments → New environment*: `staging` and `production`.
For `production`, optionally add *Required reviewers* (yourself) and restrict deployment branches to `main`.

### 6.2 Secrets (per environment, same names in both)

| Secret | Example / how to get it |
|--------|--------------------------|
| `SSH_HOST` | server hostname from cPanel (e.g. `server123.hostingprovider.ee`) |
| `SSH_PORT` | usually `22` (some hosts use `2222` etc.) |
| `SSH_USER` | the cPanel username |
| `SSH_PRIVATE_KEY` | create a deploy key locally: `ssh-keygen -t ed25519 -f lumi_deploy -C "github-deploy"`; import `lumi_deploy.pub` in cPanel → *SSH Access* → *Manage SSH Keys* → *Import Key* → **Authorize**; paste the private key here |
| `SSH_KNOWN_HOSTS` | output of `ssh-keyscan -p <port> <host>` |
| `DEPLOY_PATH` | `/home/<cpaneluser>/apps/php-margus-staging` (or `-production`) |
| `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` | only for the FTP fallback; use an FTP account limited to the app directory |

### 6.3 Variables (per environment unless noted)

| Variable | Value |
|----------|-------|
| `STAGING_ENABLED` (repo variable) | `true` to activate staging deploys |
| `PRODUCTION_ENABLED` (repo variable) | `true` to activate production deploys |
| `APP_URL` | `https://<staging-domain>` / `https://<production-domain>` (shown in the workflow summary and used for the smoke check) |
| `DEPLOY_METHOD` | `rsync` (default) or `ftp` |
| `PHP_BIN` | the PHP CLI path on the server, e.g. `/usr/local/bin/ea-php83` |

With `gh` (after the domains are known):
```bash
gh variable set STAGING_ENABLED --body true
gh variable set APP_URL --env staging --body https://staging.example.ee
gh variable set PHP_BIN --env staging --body /usr/local/bin/ea-php83
gh secret set SSH_HOST --env staging --body server123.example.ee
gh secret set SSH_PRIVATE_KEY --env staging < lumi_deploy
gh secret set SSH_KNOWN_HOSTS --env staging --body "$(ssh-keyscan -p 22 server123.example.ee)"
# ...repeat for production
```

## 7. Branch protection (manual steps)

Protecting `main` on a **private** repo needs GitHub Pro/Team (rulesets and branch protection are not available
for private repos on GitHub Free). If the API call during setup failed, set it up by hand once the plan allows it:

*Settings → Rules → Rulesets → New branch ruleset*
- Name: `protect-main`; Enforcement: Active; Target: `main`
- ✔ Restrict deletions
- ✔ Require a pull request before merging (0 or 1 approvals; dismiss stale approvals)
- ✔ Require status checks to pass: `CI / test` (after T0.2)
- ✔ Block force pushes

Optionally apply the same to `develop` (PR required, no force push).
Until then, the team rule is: **never push to `main` directly; only release PRs from `develop`.**

## 8. Release procedure

1. All tasks for the release are merged into `develop`, and staging is verified.
2. Open a PR `develop` → `main` titled `Release vX.Y` with the changelog and any manual steps.
3. The owner merges it. The production deploy runs automatically.
4. Run the manual steps (new `.env` keys, cron changes) and smoke-test `/et`, `/en`, a PDF download and the admin login.
5. Tag: `git tag vX.Y && git push --tags`.

## 9. Rollback

- Code: revert the merge commit on `main` with a PR (`git revert -m 1 <sha>`); the deploy runs again.
- Database: stop the cron jobs, restore the latest `~/data/backups/production/db-*.sqlite.gz` over the live file (gunzip, chmod 600), and run
  `php artisan migrate:status` to check that the schema matches the code.
