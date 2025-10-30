FROM silintl/php8:8.3

ARG GITHUB_REF_NAME
ENV GITHUB_REF_NAME=$GITHUB_REF_NAME

RUN apt-get update && apt-get install -y --no-install-recommends \
  cron \
  make \
  ssl-cert \
  && rm -rf /var/lib/apt/lists/* \
  && a2enmod ssl

ENV APP_HOME=/app
WORKDIR $APP_HOME

# Install/cleanup composer dependencies
COPY app/composer.json $APP_HOME
COPY app/composer.lock $APP_HOME
RUN composer install --prefer-dist --no-interaction --no-dev --optimize-autoloader --no-progress

COPY app/ $APP_HOME

# Fix folder permissions
RUN chown -R www-data:www-data \
    console/runtime/

COPY dockerbuild/vhost.conf /etc/apache2/sites-enabled/

# ErrorLog inside a VirtualHost block is ineffective for unknown reasons
RUN sed -i -E 's@ErrorLog .*@ErrorLog /proc/self/fd/2@i' /etc/apache2/apache2.conf

# Add links to the run scripts to maintain backward compatibility
RUN mkdir /data
RUN ln -s /app/run.sh /data/run.sh
RUN ln -s /app/run-cron.sh /data/run-cron.sh

EXPOSE 80
CMD ["/app/run.sh"]
