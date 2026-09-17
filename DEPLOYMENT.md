# Deployment — GitHub → Hostinger

> **بالعربي باختصار:** الدومين بيُخدَم من جذر المشروع (`public_html/`) مش من `public/`.
> ملف `.htaccess` الجذري هو اللي بيوجّه كل الطلبات لـ `public/` — وبقى الآن **داخل الريبو**
> عشان ما يضيعش مع أي `git pull` أو clone جديد. الأصول المبنية (`public/build`) ورابط
> `public/storage` كمان بقوا داخل الريبو. بعد كل نشر شغّل `scripts/verify-production.sh`.

---

## 1. How the domain is served

```
/home/u109745148/domains/investment.dareljamila.com/public_html/   ← DocumentRoot
├── .htaccess          ← rewrites everything into public/   (tracked in git)
├── app/ bootstrap/ config/ database/ lang/ resources/ routes/ vendor/
├── storage/           ← real storage (never served directly)
└── public/            ← Laravel's web root
    ├── index.php      ← the front controller
    ├── build/         ← Vite production assets (tracked in git)
    ├── storage        ← symlink → ../storage/app/public (tracked in git)
    └── .htaccess      ← Laravel's own pretty-URL rules
```

The hosting account serves the domain from `public_html/`, which is the **Laravel project
root**, not `public/`. That single fact explains the 403 and everything below.

## 2. The 403 problem

**What happened.** A request for `/` maps to `public_html/index.php`. That file does not
exist — Laravel's front controller lives in `public/index.php`. With no index file and
directory listing disabled (the sane default on shared hosting), Apache/LiteSpeed answers
**403 Forbidden**. Not 404, because the directory exists; the server simply refuses to show
its contents.

**Why `php artisan serve` worked.** That command starts PHP's built-in server with its
document root set to `public/`, so every request already lands on `public/index.php`. It
never exercises the hosting layout, which is why local testing looked fine.

**Why the root `.htaccess` fixes it.** It rewrites any request that is not already under
`/public/` to `public/<the same path>`. `/` becomes `public/` → `DirectoryIndex` serves
`public/index.php`; `/build/app.css` becomes `public/build/app.css`. Laravel is reached
exactly as it would be with a correct DocumentRoot.

**The security half of the story.** Because the project root is the web root, without that
rewrite the server happily serves project files. Reproduced locally on Apache: with no root
`.htaccess`, `GET /.env` returned **200 with the file contents**. The version in this repo
therefore also denies dotfiles and application directories explicitly, so a missing
rewrite module can never turn into a credential leak.

## 3. Files that must exist in production

| Path | Source | If missing |
| --- | --- | --- |
| `.htaccess` (root) | git | **403 on every page** + project files exposed |
| `public/index.php` | git | 403/500 |
| `public/.htaccess` | git | Pretty URLs break (404 on `/quiz`) |
| `public/build/` | git (committed assets) | Pages load unstyled, JS 404 |
| `public/storage` | git (relative symlink) | Uploaded media 404 |
| `vendor/` | `composer install` on the server, or uploaded | 500 |
| `.env` | created on the server, never in git | 500 |
| `storage/` writable (775) | server | 500 on sessions/logs/cache |

## 4. One-time migration (next deploy only)

Three things that used to live on the server as **untracked** files are now tracked in git:
the root `.htaccess`, `public/build/` and the `public/storage` symlink. Git refuses to
overwrite untracked files, so the first pull fails unless they are removed once. Take
backups first — all of it is recoverable from the repo afterwards.

```bash
cd ~/domains/investment.dareljamila.com/public_html

# 1. Back up outside the web root.
mkdir -p ~/backups/$(date +%F)
cp .htaccess ~/backups/$(date +%F)/htaccess.txt
cp .env      ~/backups/$(date +%F)/env.txt

# 2. Remove the root .htaccess — the repo version (same rewrite + hardening) replaces it.
rm .htaccess

# 3. Remove the old built assets — the repo now carries them.
rm -rf public/build

# 4. public/storage: ONLY remove it if it is a symlink. Never delete a real
#    directory here; the uploaded files themselves live in storage/app/public.
ls -la public/storage
[ -L public/storage ] && rm public/storage || echo "NOT a symlink — leave it, ask before touching"

# 5. Pull.
git pull origin main

# 6. Confirm the three came back.
head -20 .htaccess
ls public/build/assets | head
ls -la public/storage          # → ../storage/app/public
```

If git still complains about another untracked file, back that file up and remove it the
same way — never use `git checkout -f` or `git clean -fd` blindly on production.

## 5. Every deployment after that

### With SSH (preferred)

```bash
cd ~/domains/investment.dareljamila.com/public_html

git pull origin main

# Only when composer.lock changed:
php artisan down --render="errors::503" || true
composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force                 # additive migrations only
php artisan db:seed --class=EnglishContentSeeder --force   # idempotent

# Clear EVERYTHING first, then rebuild. A config cache left over from an older
# release is what caused the 500 (TypeError in Locale::meta) after a deploy:
# the cached file had no `creativemark.locales` key, so the new code read
# nothing back. optimize:clear drops config, routes, views, events and cache.
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan about                           # production / debug OFF / caches on

php artisan email:diagnose                  # confirms SMTP from the server itself
php artisan up || true
```

Then, from any machine:

```bash
./scripts/verify-production.sh https://investment.dareljamila.com
```

### Without SSH (hPanel only)

1. **Git** → *Deploy* (hPanel pulls the repository into `public_html`).
2. **File Manager** → delete `bootstrap/cache/config.php` and `bootstrap/cache/routes-v7.php`
   if they exist (this is the safe equivalent of `config:clear`).
3. **File Manager** → also delete `bootstrap/cache/events.php` and everything in
   `storage/framework/views/` (the no-SSH equivalent of `optimize:clear`).
4. **Cron jobs** → add a one-off job, run it once, then delete it:
   `/usr/bin/php ~/domains/investment.dareljamila.com/public_html/artisan migrate --force`
5. Repeat step 4 with `db:seed --class=EnglishContentSeeder --force` when content changes.
6. Run `scripts/verify-production.sh` from your laptop.

`composer install` is only needed when `composer.lock` changes; `npm run build` is **never**
run on the server — assets are built locally and committed.

## 6. Before you push (local)

```bash
npm run build          # refreshes public/build — commit the result
php artisan test       # must be green
git add -A && git commit -m "…" && git push origin main
```

Committing `public/build` is deliberate: the server has no Node toolchain, and code and
assets must move together — otherwise the new HTML references asset hashes that do not
exist yet, and the site loads unstyled.

## 7. Never change these (they bring the 403 back)

* Do **not** delete or empty the root `.htaccess`.
* Do **not** move `public/index.php` to the project root, and do not duplicate `public/`.
* Do **not** point the domain at `public_html/public` **and** keep the rewrite — pick one.
  (If the DocumentRoot is ever corrected to `.../public_html/public`, the root `.htaccess`
  must be removed in the same change, otherwise requests resolve to `public/public/…`.)
* Do **not** add `RewriteBase /public` or change `RewriteCond %{REQUEST_URI} !^/public/`.
* Do **not** `chmod 777` anything. Directories `755`, files `644`, `storage/` and
  `bootstrap/cache/` `775` are enough.
* Do **not** run `migrate:fresh`, `db:wipe`, or delete `storage/` on production.
* Do **not** commit `.env`, and do not let `.env` live anywhere under `public/`.

## 8. Troubleshooting

| Symptom | Cause | Fix |
| --- | --- | --- |
| 403 on every URL | Root `.htaccess` missing or emptied | Restore it from the repo (`git checkout -- .htaccess`) |
| Site loads unstyled, `/build/...` 404 | `public/build` not deployed or stale | Build locally, commit, pull again |
| `/quiz` 404 but `/` works | `public/.htaccess` missing or `AllowOverride None` | Restore the file / ask support to allow overrides |
| 500 after a deploy | **Stale `bootstrap/cache/config.php` from the previous release**, or `vendor/` out of date | `php artisan optimize:clear` then re-cache; `composer install` if `composer.lock` changed |
| `TypeError … must be of type array, null returned` | Old config cache missing new config keys | `php artisan optimize:clear` (the code now falls back on its own, but always clear) |
| Uploaded images 404 | `public/storage` is a plain file, not a symlink | `rm public/storage && php artisan storage:link` (or recreate with `ln -s ../storage/app/public public/storage`) |
| Emails silently not sent | Wrong mailer, cached config, or blocked SMTP port | `php artisan email:diagnose`, then Admin → Email logs |
| Pull refuses to run | Untracked file conflicts with a tracked one | Back it up, delete it, pull again (see § 4) |
| `public/storage` is a real directory with files | Someone copied instead of symlinking | Move its contents into `storage/app/public`, then recreate the symlink |
