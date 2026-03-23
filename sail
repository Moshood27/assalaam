#!/usr/bin/env sh
# Wrapper to run Laravel Sail from the backend directory
# Usage: ./sail up -d
set -e
SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
cd "$SCRIPT_DIR/backend"
exec sh ./vendor/bin/sail "$@"
