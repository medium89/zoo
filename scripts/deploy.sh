#!/usr/bin/env bash
set -euo pipefail

# Usage:
#   ./scripts/deploy.sh
#   ./scripts/deploy.sh another-branch
#
# The hosting's default `php` binary is PHP 8.0, while this Laravel project
# requires PHP 8.1+. Keep the binary explicit so every Artisan command uses
# the same version as the deployed application.

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BRANCH="${1:-${DEPLOY_BRANCH:-codex/fix-boarding-datepicker-edge}}"
PHP_BIN="${PHP_BIN:-/opt/php81/bin/php}"

if [[ ! -x "$PHP_BIN" ]]; then
    echo "PHP 8.1 binary was not found or is not executable: $PHP_BIN"
    echo "Set PHP_BIN explicitly, for example: PHP_BIN=/path/to/php81 $0 $BRANCH"
    exit 1
fi

cd "$PROJECT_DIR"

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Deployment stopped: the server working tree has local changes."
    git status --short
    exit 1
fi

old_lock_hash="$(git rev-parse HEAD:composer.lock 2>/dev/null || true)"

echo "Fetching $BRANCH..."
git fetch origin "$BRANCH"
git switch "$BRANCH"
git pull --ff-only origin "$BRANCH"

new_lock_hash="$(git rev-parse HEAD:composer.lock 2>/dev/null || true)"
if [[ "$old_lock_hash" != "$new_lock_hash" ]]; then
    composer_bin="$(command -v composer || true)"
    if [[ -z "$composer_bin" ]]; then
        echo "composer.lock changed, but Composer is not available in PATH."
        exit 1
    fi

    echo "Installing updated Composer dependencies..."
    "$PHP_BIN" "$composer_bin" install --no-dev --prefer-dist --optimize-autoloader
fi

echo "Clearing Laravel caches with $PHP_BIN..."
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan view:cache

echo "Deployment completed: $(git rev-parse --short HEAD)"
