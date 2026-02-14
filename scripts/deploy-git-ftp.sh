#!/usr/bin/env bash
set -euo pipefail

ENV_FILE="${1:-.env.deploy}"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Config file not found: $ENV_FILE"
    echo "Create it from .env.deploy.example"
    exit 1
fi

# shellcheck disable=SC1090
set -a
source "$ENV_FILE"
set +a

require_var() {
    local name="$1"
    if [[ -z "${!name:-}" ]]; then
        echo "Missing required variable: $name"
        exit 1
    fi
}

require_var "GIT_FTP_URL"
require_var "GIT_FTP_USER"
require_var "GIT_FTP_PASSWORD"

if ! command -v git-ftp >/dev/null 2>&1; then
    echo "git-ftp is not installed."
    echo "Install and retry."
    exit 1
fi

REMOTE_ROOT="${GIT_FTP_REMOTE_ROOT:-/}"
SYNCROOT="${GIT_FTP_SYNCROOT:-.}"
SKIP_BUILD="${GIT_FTP_SKIP_BUILD:-0}"
BUILD_CMD="${GIT_FTP_BUILD_CMD:-npm ci && npm run build}"
RUN_INIT="${GIT_FTP_INIT:-0}"

if [[ "$SKIP_BUILD" != "1" ]]; then
    echo "Running build: $BUILD_CMD"
    bash -lc "$BUILD_CMD"
fi

ARGS=(
    --syncroot "$SYNCROOT"
    --remote-root "$REMOTE_ROOT"
    --user "$GIT_FTP_USER"
    --passwd "$GIT_FTP_PASSWORD"
)

if [[ "${GIT_FTP_INSECURE:-0}" == "1" ]]; then
    ARGS+=(--insecure)
fi

if [[ "$RUN_INIT" == "1" ]]; then
    echo "Running: git ftp init"
    git ftp init "${ARGS[@]}" "$GIT_FTP_URL"
else
    echo "Running: git ftp push --auto-init"
    git ftp push --auto-init "${ARGS[@]}" "$GIT_FTP_URL"
fi
