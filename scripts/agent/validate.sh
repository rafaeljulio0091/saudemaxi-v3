#!/usr/bin/env bash
set -u

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

failures=0
run_check() {
    label="$1"
    shift
    printf '\n[%s]\n' "$label"
    if "$@"; then
        printf '%s\n' 'PASS'
    else
        status=$?
        printf '%s (exit %s)\n' 'FAIL' "$status"
        failures=$((failures + 1))
    fi
}

if command -v php >/dev/null 2>&1 && [ -f artisan ]; then
    run_check 'php artisan test' php artisan test
else
    printf '%s\n' '[php artisan test] SKIP: php ou artisan indisponível'
fi

if [ -x vendor/bin/pint ]; then
    run_check 'pint' vendor/bin/pint --test
else
    printf '%s\n' '[pint] SKIP: vendor/bin/pint indisponível'
fi

if command -v npm >/dev/null 2>&1 && [ -f package.json ]; then
    run_check 'frontend tests' npm run test:frontend
    run_check 'vite build' npm run build
else
    printf '%s\n' '[npm] SKIP: npm ou package.json indisponível'
fi

if command -v git >/dev/null 2>&1; then
    run_check 'git diff --check' git diff --check
else
    printf '%s\n' '[git diff --check] SKIP: git indisponível'
fi

if [ "$failures" -gt 0 ]; then
    printf '\nValidation finished with %s failure(s).\n' "$failures"
    exit 1
fi

printf '\nValidation finished successfully.\n'
