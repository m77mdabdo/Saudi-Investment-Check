#!/usr/bin/env bash
# Installs the repository hooks. Run once per clone.
set -euo pipefail
ROOT="$(git rev-parse --show-toplevel)"
install -m 0755 "$ROOT/scripts/hooks/post-commit" "$ROOT/.git/hooks/post-commit"
echo "post-commit hook installed"
