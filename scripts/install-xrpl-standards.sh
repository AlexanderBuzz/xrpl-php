#!/usr/bin/env bash
# Installs the xrpl-standards agent skill (every XLS specification as a
# reference file, with an index and fetch scripts) into .claude/skills/, where
# Claude Code loads it by name. The skill is maintained by Peersyst and
# vendored in XRPLF/xrpl-go; its registry repository is not public, and the
# skills CLI does not follow the symlinks xrpl-go uses, so this takes it from
# the xrpl-go repository with a sparse clone. The directory is gitignored;
# run this once per checkout, and again to refresh.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TARGET="$REPO_ROOT/.claude/skills/xrpl-standards"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

git clone -q --depth 1 --filter=blob:none --sparse https://github.com/XRPLF/xrpl-go.git "$TMP_DIR/xrpl-go"
git -C "$TMP_DIR/xrpl-go" sparse-checkout set -q .agents/skills/xrpl-standards

rm -rf "$TARGET"
mkdir -p "$(dirname "$TARGET")"
cp -R "$TMP_DIR/xrpl-go/.agents/skills/xrpl-standards" "$TARGET"

echo "xrpl-standards installed to $TARGET (xrpl-go @ $(git -C "$TMP_DIR/xrpl-go" rev-parse --short HEAD), $(find "$TARGET/references" -name '*.md' | wc -l | tr -d ' ') reference files)"
