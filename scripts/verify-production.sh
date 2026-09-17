#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Post-deployment verification for the Saudi-Ready Check platform.
#
#   ./scripts/verify-production.sh https://investment.dareljamila.com
#
# Checks that every public surface answers 200, that the built assets the
# deployed HTML references actually exist, and that no project file is
# downloadable. Exits non-zero if anything fails, so it can gate a deploy.
# ---------------------------------------------------------------------------
set -uo pipefail

BASE="${1:-https://investment.dareljamila.com}"
BASE="${BASE%/}"
FAILED=0
# No -L: a redirect is an answer we want to assert on, not follow.
CURL=(curl -s -o /dev/null -w '%{http_code}' --max-time 25)

check() { # path, expected codes (space separated), label
    local path="$1" expected="$2" label="${3:-$1}"
    local code
    code="$(${CURL[@]} "$BASE$path")"
    if [[ " $expected " == *" $code "* ]]; then
        printf '  \033[32m✓\033[0m %-34s %s\n' "$label" "$code"
    else
        printf '  \033[31m✗\033[0m %-34s %s (expected: %s)\n' "$label" "$code" "$expected"
        FAILED=$((FAILED + 1))
    fi
}

echo ""
echo "Verifying $BASE"
echo ""
echo "Public pages"
check "/"                  "200" "Arabic home"
check "/quiz"              "200" "Arabic quiz"
check "/en"                "200" "English home"
check "/en/quiz"           "200" "English quiz"
check "/qr/booth_qr"       "302" "QR entry → landing"
check "/sitemap.xml"       "200" "sitemap.xml"
check "/robots.txt"        "200" "robots.txt"
check "/this-page-is-not-real" "404" "404 page"

echo ""
echo "Admin"
check "/admin/login"       "200" "login page"
check "/admin"             "302" "dashboard (guest → login)"
check "/admin/leads"       "302" "leads (guest → login)"

echo ""
echo "Assets referenced by the deployed HTML"
HTML="$(curl -s --max-time 25 -L "$BASE/")"
ASSETS="$(printf '%s' "$HTML" | grep -oE '/build/assets/[A-Za-z0-9._-]+\.(css|js)' | sort -u)"
if [[ -z "$ASSETS" ]]; then
    printf '  \033[31m✗\033[0m %-34s no /build/assets/... found in the HTML\n' "asset references"
    FAILED=$((FAILED + 1))
else
    while read -r asset; do
        [[ -n "$asset" ]] && check "$asset" "200" "$(basename "$asset")"
    done <<< "$ASSETS"
fi
check "/build/manifest.json" "200 403 404" "vite manifest"

echo ""
echo "Language rendering"
for pair in "/:ar:rtl" "/quiz:ar:rtl" "/en:en:ltr" "/en/quiz:en:ltr"; do
    path="${pair%%:*}"; rest="${pair#*:}"; lang="${rest%%:*}"; dir="${rest##*:}"
    body="$(curl -s --max-time 25 "$BASE$path")"
    if printf '%s' "$body" | grep -q "<html lang=\"$lang\" dir=\"$dir\""; then
        printf '  \033[32m✓\033[0m %-34s lang=%s dir=%s\n' "$path" "$lang" "$dir"
    else
        printf '  \033[31m✗\033[0m %-34s expected lang=%s dir=%s\n' "$path" "$lang" "$dir"
        FAILED=$((FAILED + 1))
    fi
done

echo ""
echo "Production hardening"
ERRBODY="$(curl -s --max-time 25 "$BASE/this-page-is-not-real")"
if printf '%s' "$ERRBODY" | grep -qiE "whoops|stack trace|vendor/laravel|APP_KEY|SQLSTATE"; then
    printf '  \033[31m✗\033[0m %-34s debug output is visible — set APP_DEBUG=false\n' "APP_DEBUG"
    FAILED=$((FAILED + 1))
else
    printf '  \033[32m✓\033[0m %-34s no debug output on errors\n' "APP_DEBUG"
fi

echo ""
echo "Files that must NOT be downloadable"
for path in /.env /.env.example /composer.json /composer.lock /artisan /package.json \
            /app/Models/Lead.php /config/mail.php /database/database.sqlite \
            /storage/logs/laravel.log /vendor/autoload.php /.git/config /phpunit.xml; do
    code="$(${CURL[@]} "$BASE$path")"
    if [[ "$code" == "200" ]]; then
        printf '  \033[31m✗\033[0m %-34s %s  ← EXPOSED, fix the root .htaccess\n' "$path" "$code"
        FAILED=$((FAILED + 1))
    else
        printf '  \033[32m✓\033[0m %-34s %s\n' "$path" "$code"
    fi
done

echo ""
if [[ "$FAILED" -eq 0 ]]; then
    echo -e "\033[32mAll checks passed.\033[0m"
    exit 0
fi

echo -e "\033[31m$FAILED check(s) failed.\033[0m"
echo "A 403 on every page usually means the root .htaccess is missing —"
echo "see DEPLOYMENT.md § 'The 403 problem'."
exit 1
