#!/usr/bin/env bash

# print script lines as they are executed
set -x

# exit if any line in the script fails
set -e

# Try to install composer dev dependencies
cd /app
composer install --no-interaction --no-scripts --no-progress

if [[ -n "$SSL_CA_BASE64" ]]; then
    # Decode the base64 and write to the file
    caFile="/app/console/runtime/ca.pem"
    echo "$SSL_CA_BASE64" | base64 -d > "$caFile"
    if [[ $? -ne 0 || ! -s "$caFile" ]]; then
        echo "Failed to write database SSL certificate file: $caFile" >&2
        exit 1
    fi
fi

# Try to run database migrations
./yii migrate --interactive=0

make-ssl-cert generate-default-snakeoil

# start apache
apachectl start

# Run the feature tests
./vendor/bin/behat --strict --stop-on-failure --tags '~@integration'
