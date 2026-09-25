#!/usr/bin/env bash
set -u

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

printf '%s\n' 'Engineering Harness preflight'
printf 'root: %s\n' "$ROOT_DIR"
printf 'branch: '
git branch --show-current 2>/dev/null || printf 'unknown\n'

printf '%s\n' '--- git status ---'
git status --short

printf '%s\n' '--- versions ---'
if command -v php >/dev/null 2>&1; then php -r 'printf("php: %s\n", PHP_VERSION);'; else printf '%s\n' 'php: unavailable'; fi
if command -v composer >/dev/null 2>&1; then composer --version --no-ansi; else printf '%s\n' 'composer: unavailable'; fi
if command -v node >/dev/null 2>&1; then node --version; else printf '%s\n' 'node: unavailable'; fi
if command -v npm >/dev/null 2>&1; then npm --version; else printf '%s\n' 'npm: unavailable'; fi

printf '%s\n' '--- relevant manifests ---'
for file in composer.json package.json vite.config.js config/auth.php config/lsxmedical.php; do
    if [ -f "$file" ]; then printf '%s\n' "$file"; else printf 'missing: %s\n' "$file"; fi
done

printf '%s\n' '--- changed files ---'
git diff --name-only
git diff --cached --name-only
git ls-files --others --exclude-standard
