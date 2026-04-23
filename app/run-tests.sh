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

# Wait for the mfaapi service to be fully ready to serve HTTP responses.
# The service uses "go run ./server/" which compiles before starting, so it
# can take several seconds before it is able to accept real HTTP requests.
# curl exits 0 on any HTTP response (including 4xx) and non-zero for network
# errors (empty reply, connection refused, timeout), so we poll until it
# responds rather than just until the container is started.
echo "Waiting for mfaapi to be ready..."
for attempt in $(seq 1 30); do
    if curl --max-time 3 --silent --output /dev/null http://mfaapi:8080/webauthn/register; then
        echo "mfaapi is ready (attempt $attempt)."
        break
    fi
    echo "mfaapi not ready yet (attempt $attempt/30), retrying in 2 seconds..."
    sleep 2
done

# Run the feature tests
./vendor/bin/behat --strict --stop-on-failure
