# Pre-event audit — Saudi-Ready Check

_Date: 2026-10-01 · Branch `main` · HEAD `d7d70fa` · Production `d7d70fa`_

> **Update — blockers fixed.** C2, C3, C4, H1, M2 and M3 below have been
> addressed in the working tree (still uncommitted). Each carries a
> **FIXED** note. C1 remains: the work is still uncommitted.
> One finding, L2, was **wrong** and is corrected below.

---

## Summary

1. **Nothing is committed.** All 39 files of the registration feature exist only in the working tree; `HEAD == origin/main == production`, so a deploy today ships nothing. This is the first thing to fix.
2. **Two silent-failure paths can lose a visitor's photo without anyone noticing** — a failed disk write is swallowed, and a photo written before a failed row insert is orphaned with no cleanup.
3. **The landing page is missing the `[x-cloak]` rule**, so on every page load the 93-row country dropdown and the photo-preview block render expanded before Alpine boots — and stay that way permanently with JS disabled, where they cover the email field.
4. **The per-IP rate limit is the biggest event-day risk**: a venue shares one NAT address, so 20 submits/minute is a whole-queue budget, not a per-person one.
5. Photo storage, access control, CSRF, mass assignment, SQL injection, XSS, indexes and query counts are **solid**. Capacity is a non-issue: 2,000 registrations export in 95 ms and cost ~400 MB of disk.

---

## Critical — must be fixed before the event

### C1. The entire feature is uncommitted
**`git status` — 39 files**

`HEAD` is `d7d70fa`, identical to `origin/main`, and production serves the assets built from that commit (`app-C7OtHZFf.css`). Production returns **404 for `POST /register`** and **404 for `/admin/registrations`**, and its landing page still renders the quiz hero (`ابدأ الرحله`).

**Impact:** a `git pull` on the server right now deploys nothing. Every step below depends on this being committed and pushed first.

### C2. A failed photo write is silently swallowed
**`app/Services/PhotoStorage.php:62`** · **`config/filesystems.php` (`local` disk, `'throw' => false`)**

```php
Storage::disk(self::DISK)->put($path, $binary);   // return value ignored
```

`put()` returns `false` on failure and the `local` disk is configured with `'throw' => false`, so a full disk, a permissions problem or a read-only mount produces **no exception and no log line**. `store()` then returns the path as if it had worked, and the row is created with a `photo_path` pointing at a file that does not exist.

**Impact:** the registration succeeds, the admin list shows a broken thumbnail, and the photo route 404s. At an event this is silent and unrecoverable — the person has already left.

> **FIXED** — `PhotoStorage.php:66` now throws on a `false` return, with the
> disk, path, byte count and remaining free space in the message. The controller
> catches it, **saves the registration anyway with `photo_path` null**, logs
> `event_registration.photo_failed`, and the confirmation tells the visitor the
> photo did not save. A registration is never lost to a photo problem, and a row
> never claims a photo that is not on disk. The same unchecked-write shape in
> `EventController.php:75` (admin event logo) was fixed too.

### C3. Orphaned photo when the row insert fails
**`app/Http/Controllers/Public/RegistrationController.php:36` then `:44`**

The photo is written to disk *before* `EventRegistration::create()`. There is no transaction and no `try/finally` cleanup. If the insert fails — deadlock, connection drop, constraint — the file stays on disk with no row pointing at it.

**Impact:** personal data (a photograph of a real person) persists on disk with no record, so it cannot be found or deleted on request. This is the more serious half of the pair: C2 loses a photo, C3 **retains one it should not**. Evidence that this was not theoretical: **45 orphaned files were sitting in `storage/app/private/registrations` against 0 database rows**, left by test runs (see M3).

> **FIXED** — the insert is wrapped; a failure deletes the just-written file
> before rethrowing. The 45 local orphans were verified unreferenced (0 rows had
> a `photo_path`) and removed.

### C4. Missing `[x-cloak]` rule on the landing page
**`resources/views/public/landing.blade.php`** — 4 `x-cloak` elements, no rule defining it

`[x-cloak]{display:none!important}` is declared in `quiz.blade.php` and `admin.blade.php` but **not** in `landing.blade.php` and **not** in `app.css` (`grep x-cloak` on the built CSS returns 0). It was lost when the form moved out of the standalone welcome page.

Measured with JS disabled, scrolling the entire page: `name`, `phone`, `photo` and `submit` are reachable, **`email` never is** — the expanded country list covers it.

**Impact:** on every single load there is a visible flash of a 93-row country list and a stray photo-preview block before Alpine initialises; with JS off it is permanent. This is the first thing every visitor sees.

> **FIXED** — `resources/css/app.css:529`. Declared in the stylesheet rather than
> a per-page `<style>` block, so no future page can lose it by being moved.
> Re-measured with JS disabled: `name`, `phone`, `email`, `photo` and `submit`
> are now all reachable. Regression test asserts `[x-cloak]` is present in the
> built CSS.

---

## High

### H1. Rate limit is per-IP, and a venue is one IP
**`app/Providers/AppServiceProvider.php:50`** — `Limit::perMinute(20)->by('registration-submit|'.$request->ip())`

Everyone on the venue Wi-Fi shares one NAT address. Twenty submissions per minute is the budget for *the whole stand*, not per visitor. A queue of people registering together will start getting 429s, and the 429 page is not styled or translated.

**Impact:** registrations silently refused at the busiest moment.

> **FIXED** — `AppServiceProvider.php:50` now applies two limits: **120/min per
> IP** (~2 per second, above any human queue and well under a scripted flood)
> and **6/min per session**, which is the real abuse control since one browser
> is one person regardless of how many share the address. Verified: 12 submits
> from one IP with separate sessions all succeed; a 7th submit in one session
> returns 429.

### H2. No way to delete a person's data
**No route, command or UI exists**

`destroy()` and `destroyPhoto()` exist in `app/Http/Controllers/Admin/RegistrationController.php` and `destroy()` correctly deletes the file before the row — but there is **no retention policy, no bulk delete, no expiry, and no "delete everything for this phone number"**. Nothing purges data after the event.

**Impact:** you are holding names, phone numbers, emails and photographs of real people indefinitely with no documented basis and no deletion path beyond clicking one row at a time.

### H3. No audit trail for photo access
**`app/Http/Controllers/Admin/RegistrationController.php:51-61`**

The photo route logs nothing. Any active staff account can view any photograph and there is no record that it happened. By contrast, lead creation *is* logged (`lead.created`), and so is registration creation (`event_registration.created`) — photo *viewing* is the gap.

**Impact:** if a photo is misused you cannot tell who looked at it.

---

## Medium

### M1. Any staff account can enumerate every photo
**`routes/web.php:105`** · **`app/Models/EventRegistration.php`** (no `getRouteKeyName`)

The route binds on the auto-increment `id`, so `/admin/registrations/1/photo`, `/2/photo` … walks the whole table. There is no per-user scoping — `admin`, `manager` and `sales` all see everything.

This matches how leads already behave, so it may be intended. It is listed because you asked specifically about IDOR. **No traversal risk**: the parameter is resolved by route-model binding and never used as a path; `photo_path` comes from the database, not from input.

### M2. Deleting a registration by any other path orphans its photo
**`app/Models/EventRegistration.php`** — no `deleting` model hook

File cleanup lived in the controller. Any other deletion route — a future bulk action, a tinker session, a cascade — left the file behind.

> **FIXED** — moved to a `static::deleting()` hook on `EventRegistration`, so
> cleanup holds however the row is removed. The admin controller no longer
> deletes the file itself. Test deletes a model directly (not via the
> controller) and asserts the file is gone.

### M3. Tests write to the real private disk
**`tests/Feature/EventRegistrationTest.php`** — no `Storage::fake()`

Every run wrote real `.webp` files into `storage/app/private/registrations` and never cleaned up: **45 files, 0 rows**.

> **FIXED** — `Storage::fake(PhotoStorage::DISK)` in `setUp()`. Verified: a full
> suite run now leaves 0 files behind. Running the suite on the server is still
> inadvisable, but it no longer litters.

### M4. Dead code from the quiz-as-landing era
Safe to remove — nothing references them:
- `lang/{ar,en}/home.php`: 5 `hero_*` keys plus `cta_label` (removed from the CMS editor and the view; values still sit in the database by your instruction)
- `landing_pages.content`: `hero_kicker`, `hero_lead`, `hero_title`, `hero_description`, `hero_meta`, `cta_label` — dead data, invisible, left in place deliberately

**Still in use — do not remove:** all 26 `/quiz` and `/result` routes, `quiz.blade.php`, `result.blade.php`, `ScoringService`, `LeadService`, the `leads` tables, and the CMS keys `quiz_intro`, `lead_headline`, `lead_text`, `lead_cta`, `consent_text`. The quiz still exists at `/quiz`; it is simply no longer the landing journey.

### M5. Landing page weight
```
html                 39.9 KB
app CSS              72.9 KB   (15.3 KB gzipped)
app JS                6.7 KB   ( 2.8 KB gzipped)
portal image        170.9 KB   WebP, fetchpriority=high
                    ────────
                    ~290 KB
```
Fine on venue Wi-Fi, noticeable on a weak mobile connection. The portal image is the single largest asset and blocks the first meaningful paint by design.

---

## Low

- **L1.** `AUDIT.md` findings H1–H3 from the previous audit (phone normalisation, IP-keyed rate limits, test-guard ordering in `tests/TestCase.php`) remain unfixed.
- **L2. ~~429/419 are unstyled English defaults.~~ This finding was WRONG.**
  `resources/views/errors/{403,404,419,429,500,503}.blade.php` all exist and
  render through `x-errors.layout`, and `lang/{ar,en}/errors.php` carry
  translated titles and messages for every code. Verified by triggering a real
  429: the response is a styled page, `lang="ar" dir="rtl"`, titled
  «محاولات كتير». I did not check before reporting it — corrected here.
- **L3.** `storage/app/private/registrations` is created implicitly by the first upload. If the parent is not writable the first registration of the event is the one that fails.
- **L4.** No `alt` text strategy for stored photos in the admin list (`alt=""`), which is correct for decoration but means screen-reader users get no indication a photo exists beyond the badge.

---

## What is solid

Stated plainly, because you asked:

- **Photo privacy.** Verified from a logged-out session: `/admin/registrations/N/photo` → 302 to `/admin/login` with zero image bytes; `/storage/registrations/<uuid>.webp`, `/registrations/<uuid>.webp`, `/images/…` and `/storage/app/private/…` all 404. Files live in `storage/app/private`, which has no URL and is not behind the `public/storage` symlink.
- **The image pipeline.** EXIF orientation is applied *before* metadata is stripped, so portrait photos stay upright (verified: a 4032×3024 source with `Orientation=6` came out 3024×4032, then capped to 1200×1600). A byte scan of the output for `Apple`, `iPhone`, `GPS` and `Exif` returns zero. Output is always WebP.
- **Validation.** `image` + `mimetypes:` sniffs real content, not the extension — a text file renamed `.jpg` is rejected (tested). 8 MB cap enforced.
- **Mass assignment.** The controller builds an explicit array; `photo_path` is never taken from request input.
- **SQL injection.** No raw SQL anywhere in the feature; the search scope uses bound parameters.
- **XSS.** All Blade output uses `{{ }}` escaping. No `{!! !!}` in the registration path.
- **CSRF.** `@csrf` present; the route is in the `web` group.
- **Admin middleware.** All registration routes sit inside `['auth', 'admin.active']`.
- **No secrets in git.** `.env` was never committed; no API keys, passwords or hardcoded recipients found in tracked files.
- **Queries.** A 20-row admin page costs **3 queries** including both relations — no N+1. Indexes exist on `created_at`, `(event_id, created_at)` and `uuid`.
- **Capacity.** Measured, not estimated:

| rows | export | CSV size | admin page query | peak memory |
|---|---|---|---|---|
| 500 | 60 ms | 23 KB | 6 ms | 32 MB |
| 2,000 | 95 ms | 96 KB | 4 ms | 38 MB |

  At ~206 KB per photo: **500 registrations ≈ 100 MB**, **2,000 ≈ 400 MB**. Neither is a problem.
- **The portal with a broken image.** If the artwork 404s the stage keeps its `--color-ink-950` background and the transition still runs — no white screen. Under `prefers-reduced-motion` the overlap is undone (`margin-bottom: 0`, stage opacity forced to 1) and the form is reachable normally.
- **Tests.** 159 passing, 726 assertions, including guest-blocked photo access, admin-allowed access, the three-column export contract, and badge degradation.

---

## Gaps — never built

- **Retention.** No expiry, no purge command, no policy. Data persists forever.
- **Subject deletion.** No "delete this person and their photo" action beyond deleting one row in the UI.
- **Photo-access logging.** No record of who viewed which photograph.
- **Consent.** The quiz lead form has an explicit consent checkbox (`consent`, `consent_at`). **The registration form has neither.** The privacy line states the purpose but nothing is recorded as agreement, and no timestamp is stored.
- **Admin notification.** A new lead triggers `NotificationService`. A new registration triggers nothing — no email, no dashboard badge. Staff must refresh the list to notice.
- **Offline / flaky Wi-Fi.** A failed submit loses the typed input except for `old()` repopulation; there is no client-side retry or queue.
- **Duplicate detection.** The same phone number can register unlimited times; nothing warns the person or the staff.
- **Untested failure paths:** disk full, write failure mid-stream, corrupt/truncated upload, concurrent submits, export with thousands of rows, a photo file deleted from disk behind the application's back.

---

## Deploy checklist

Ordered. Steps 1–3 are local and must happen before anything touches the server.

1. **Fix C4** — add `<style>[x-cloak]{display:none!important}</style>` to `landing.blade.php` (or the rule to `app.css`). One line, and it is visible to every visitor.
2. **Fix C2 and C3** — check the `put()` return value and throw; wrap the insert so a failure deletes the just-written file.
3. **Decide on H1** — raise or re-key the rate limiter before the venue shares one IP.
4. `npm run build` → `rm -f public/hot` → `php artisan test` (expect 159 passing).
5. `git add -A` → commit → `git push origin main`. **`public/build` is committed deliberately** (`.gitignore:26`), and a rebuild reproduces identical hashes, so the server needs no Node.
6. On the server: `git fetch origin && git reset --hard origin/main`.
7. `composer install --no-dev --optimize-autoloader --no-interaction`.
8. `php artisan migrate --force` — creates **`event_registrations` only**; `Schema::create` plus `dropIfExists` in `down()`, no `Schema::table`, no `->change()`, no `dropColumn`. **No existing table is touched.**
9. `mkdir -p storage/app/private/registrations && chmod 750 storage/app/private/registrations` — owned by the PHP user. **Never `php artisan storage:link`**: `symlink()` is blocked on Hostinger and `public/storage` already exists and works.
10. `php artisan config:clear && php artisan route:clear && php artisan view:clear`, then `config:cache && route:cache && view:cache`.
11. `php artisan app:refresh-registration-copy --dry-run`, read the diff, then run it without the flag. Idempotent; a second run reports "Already up to date".
12. Smoke test: load `/` and `/en`, submit one registration with a photo, confirm it appears in `/admin/registrations`, confirm the photo 302s to login when signed out, then delete the test row.

