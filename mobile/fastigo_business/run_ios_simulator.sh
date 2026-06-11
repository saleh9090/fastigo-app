#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RUN_DIR="/private/tmp/fastigo_business_run"
DEVICE="${1:-iPhone 17 Pro Max}"

echo "Preparing clean simulator copy at ${RUN_DIR}"
rm -rf "${RUN_DIR}"
mkdir -p "${RUN_DIR}"
cp -R "${APP_DIR}/." "${RUN_DIR}/"

rm -rf \
  "${RUN_DIR}/build" \
  "${RUN_DIR}/.dart_tool" \
  "${RUN_DIR}/ios/Flutter/ephemeral" \
  "${RUN_DIR}/macos/Flutter/ephemeral"

xattr -cr "${RUN_DIR}" 2>/dev/null || true

cd "${RUN_DIR}"
flutter pub get
flutter run -d "${DEVICE}"
