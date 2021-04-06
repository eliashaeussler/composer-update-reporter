#!/usr/bin/env bash
set -e

# Resolve variables
ROOT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." >/dev/null 2>&1 && pwd)"
APP_PATH="${ROOT_PATH}/tests/Build/test-application"
TEMP_DIR="/tmp"

# Check if temp directory is writeable
if [ ! -w "${TEMP_DIR}" ]; then
  TEMP_DIR="$(dirname "${ROOT_PATH}")"
fi
TEMP_PATH="${TEMP_DIR}/update-reporter-test"

# Define cleanup function for several signals
function cleanup() {
  local exitCode=$?
  rm -rf "${TEMP_PATH}"
  exit $exitCode
}
trap cleanup INT ERR EXIT

# Prepare temporary application
cp -r "${APP_PATH}" "${TEMP_PATH}"
rm -rf "${TEMP_PATH}/vendor"
composer config --working-dir "${TEMP_PATH}" repositories.local path "${ROOT_PATH}"
composer require --working-dir "${TEMP_PATH}" --quiet --dev "eliashaeussler/composer-update-reporter:*@dev"

# Run update check
composer update-check --working-dir "${TEMP_PATH}" --ansi "$@"
