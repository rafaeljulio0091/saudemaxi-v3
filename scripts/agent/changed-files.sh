#!/usr/bin/env bash
set -u

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

printf '%s\n' 'Unstaged changes:'
git diff --name-status
printf '%s\n' 'Staged changes:'
git diff --cached --name-status
printf '%s\n' 'Untracked files:'
git ls-files --others --exclude-standard
