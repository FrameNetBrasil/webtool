#!/bin/sh
set -e

# Run composer install if vendor doesn't exist or composer.lock changed
if [ ! -d "vendor" ] || [ "composer.lock" -nt "vendor" ]; then
    echo "Running composer install..."
    composer install --no-interaction --prefer-dist
fi

# Execute the main command
exec "$@"
