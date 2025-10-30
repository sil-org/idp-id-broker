#!/usr/bin/env bash

# print script lines as they are executed
set -x

# exit if any line in the script fails
set -e

# Try to install composer dev dependencies
cd /app
composer install --no-interaction --no-scripts --no-progress

# avoid having issues locally due to the random sleep on the appfortests container
testServer=${TEST_SERVER_HOSTNAME}
localServer='appfortests'

if [[ "$testServer" == "$localServer" ]]; then
  whenavail $localServer 80 10 true
fi

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
whenavail testdb 3306 100 ./yii migrate --interactive=0

make-ssl-cert generate-default-snakeoil

# start apache
apachectl start

# Run the feature tests
./vendor/bin/behat --strict --stop-on-failure
