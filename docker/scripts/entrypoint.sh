#!/bin/sh
set -e

# Ensure Laravel storage directories exist
mkdir -p /www/storage/framework/cache/data
mkdir -p /www/storage/framework/sessions
mkdir -p /www/storage/framework/views
mkdir -p /www/storage/framework/testing
mkdir -p /www/storage/logs
mkdir -p /www/storage/app/public
mkdir -p /www/bootstrap/cache

# Set proper permissions
chmod -R 775 /www/storage
chmod -R 775 /www/bootstrap/cache

# Execute the main command
exec "$@"
