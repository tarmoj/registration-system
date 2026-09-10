#!/usr/bin/env bash
set -euo pipefail

REMOTE="v2404@uuu.ee"
DEST="/home/v2404/www/tarmojohannes/registration-system"

SCP="scp -q"

echo "Deploying to ${REMOTE}:${DEST} ..."

# Top-level PHP files (config.php is excluded — manage it on the server directly)
$SCP attendance.php db.php register.php unsubscribe.php admin.php\
    "${REMOTE}:${DEST}/"

# Subdirectories (recursive)
$SCP -r cron helpers lang "${REMOTE}:${DEST}/"

echo "Done."
