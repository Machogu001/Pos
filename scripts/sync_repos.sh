#!/usr/bin/env bash

set -euo pipefail

APP_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BACKEND_ROOT="$APP_ROOT"
MOBILE_ROOT="$APP_ROOT/.external/Pos_app"

ACTION="${1:-status}"
TARGET="${2:-all}"

if [[ ! -d "$MOBILE_ROOT/.git" ]]; then
    echo "Mobile app checkout not found at $MOBILE_ROOT" >&2
    exit 1
fi

case "$ACTION" in
    status|pull|push)
        ;;
    *)
        echo "Usage: bash scripts/sync_repos.sh [status|pull|push] [backend|mobile|all]" >&2
        exit 1
        ;;
esac

case "$TARGET" in
    backend)
        REPOS=("backend")
        ;;
    mobile)
        REPOS=("mobile")
        ;;
    all)
        REPOS=("backend" "mobile")
        ;;
    *)
        echo "Unknown target: $TARGET" >&2
        echo "Usage: bash scripts/sync_repos.sh [status|pull|push] [backend|mobile|all]" >&2
        exit 1
        ;;
esac

run_repo() {
    local name="$1"
    local path

    if [[ "$name" == "backend" ]]; then
        path="$BACKEND_ROOT"
    else
        path="$MOBILE_ROOT"
    fi

    echo
    echo "==> $name: $path"

    case "$ACTION" in
        status)
            git -C "$path" status --short
            ;;
        pull)
            git -C "$path" pull --ff-only
            ;;
        push)
            git -C "$path" push
            ;;
    esac
}

for repo in "${REPOS[@]}"; do
    run_repo "$repo"
done