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

cd "$REPO_DIR"
git pull origin main

mkdir -p "$LIVE_DIR/app/Controllers" "$LIVE_DIR/app/Views/products"

cp "$REPO_DIR/app/Controllers/Products.php" "$LIVE_DIR/app/Controllers/Products.php"
cp "$REPO_DIR/app/Views/products/bulk_edit.php" "$LIVE_DIR/app/Views/products/bulk_edit.php"
cp "$REPO_DIR/app/Views/products/bulk_preview.php" "$LIVE_DIR/app/Views/products/bulk_preview.php"

echo "Deployment complete."