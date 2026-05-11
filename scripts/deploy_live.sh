#!/bin/bash
set -euo pipefail

REPO_DIR="${REPO_DIR:-/home/psboffice/repositories/joms-ci4}"
LIVE_DIR="${LIVE_DIR:-/home/psboffice/ci4.psboffice.com}"

echo "Repo: $REPO_DIR"
echo "Live: $LIVE_DIR"

if [ ! -d "$REPO_DIR/.git" ]; then
  echo "Git repository not found at $REPO_DIR" >&2
  exit 1
fi

if [ ! -d "$LIVE_DIR" ]; then
  echo "Live directory not found at $LIVE_DIR" >&2
  exit 1
fi

require_path() {
  local path="$1"
  if [ ! -e "$path" ]; then
    echo "Required path missing: $path" >&2
    exit 1
  fi
}

copy_file() {
  local src="$1"
  local dest="$2"
  require_path "$src"
  mkdir -p "$(dirname "$dest")"
  cp "$src" "$dest"
  echo "Copied file: $dest"
}

sync_dir() {
  local src="$1"
  local dest="$2"
  require_path "$src"
  mkdir -p "$dest"
  rsync -av --delete "$src/" "$dest/"
  echo "Synced dir: $dest"
}

cd "$REPO_DIR"
git pull origin main

copy_file "$REPO_DIR/composer.json" "$LIVE_DIR/composer.json"
copy_file "$REPO_DIR/composer.lock" "$LIVE_DIR/composer.lock"
copy_file "$REPO_DIR/spark" "$LIVE_DIR/spark"
copy_file "$REPO_DIR/preload.php" "$LIVE_DIR/preload.php"

sync_dir "$REPO_DIR/app" "$LIVE_DIR/app"
sync_dir "$REPO_DIR/public" "$LIVE_DIR/public"
sync_dir "$REPO_DIR/system" "$LIVE_DIR/system"
sync_dir "$REPO_DIR/writable" "$LIVE_DIR/writable"

echo "Deployment complete."