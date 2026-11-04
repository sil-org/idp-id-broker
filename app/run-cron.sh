#!/usr/bin/env bash

# exit if any line in the script fails
set -e

echo "email-cron starting idp-id-broker version $GITHUB_REF_NAME"

if [[ $PARAMETER_STORE_PATH ]]; then
  config="config-shim -v --path $PARAMETER_STORE_PATH"
elif [[ $APP_ID ]]; then
  config="config-shim --app $APP_ID --config $CONFIG_ID --env $ENV_ID"
else
  config=""
fi

# Make environment variables available to cron jobs
$config env >> /etc/environment

if [[ -n "$SSL_CA_BASE64" ]]; then
    # Decode the base64 and write to the file
    caFile="$APP_HOME/console/runtime/ca.pem"
    echo "$SSL_CA_BASE64" | base64 -d > "$caFile"
    if [[ $? -ne 0 || ! -s "$caFile" ]]; then
        echo "Failed to write database SSL certificate file: $caFile" >&2
        exit 1
    fi
fi

echo '* * * * * root '"$APP_HOME"'/yii send/send-queued-email > /proc/1/fd/1 2>&1' > /etc/crontab
chmod 0644 /etc/crontab

# Stop cron on shutdown. `cron` doesn't handle SIGTERM itself, so as PID 1 it would ignore the stop signal.
term_handler() {
    # `|| true` because `set -e` would otherwise exit here on an already-gone child
    kill -TERM "$child" 2>/dev/null || true
    wait "$child" 2>/dev/null || true
    exit 0
}
trap term_handler TERM

# run cron in foreground so output is logged, as a background job so the trap can run during `wait`
cron -f &
child=$!
wait "$child"
