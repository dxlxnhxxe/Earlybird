#!/bin/bash

# EarlyBird Hosts File Setup (For Linux/Mac)
# Run this script with sudo

set -e

echo "EarlyBird - Adding hosts file entries..."

HOSTS_FILE="/etc/hosts"
HOSTS_ENTRIES=(
    "127.0.0.1 earlybird-front"
    "127.0.0.1 earlybird-dashboard"
    "127.0.0.1 earlybird-api"
)

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "ERROR: This script must be run as root (use sudo)"
    echo "Usage: sudo ./setup-hosts.sh"
    exit 1
fi

# Backup hosts file
BACKUP_FILE="$HOSTS_FILE.backup-$(date +%Y%m%d-%H%M%S)"
cp "$HOSTS_FILE" "$BACKUP_FILE"
echo "Hosts file backed up to: $BACKUP_FILE"

# Add entries
echo ""
echo "Adding entries to hosts file..."
for entry in "${HOSTS_ENTRIES[@]}"; do
    if grep -qF "$entry" "$HOSTS_FILE"; then
        echo "  Skipped (exists): $entry"
    else
        echo "$entry" >> "$HOSTS_FILE"
        echo "  Added: $entry"
    fi
done

echo ""
echo "Success! EarlyBird hosts entries have been added."
echo ""
echo "You can now access:"
echo "  - Frontend: http://earlybird-front"
echo "  - Dashboard: http://earlybird-dashboard"
echo "  - API: http://earlybird-api"
