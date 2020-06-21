FROM fireflyiii/tools-base-image:latest-amd64
# To learn more about this base image, visit https://github.com/firefly-iii/tools-base-image

ENV VERSION=dev

ENV HOMEPATH=/var/www/html COMPOSER_ALLOW_SUPERUSER=1
LABEL version="dev"

# Fetch scripts from helper repo :/
RUN curl -SL https://raw.githubusercontent.com/lfuelling/spectre-importer-docker/master/scripts/site.conf -o /etc/apache2/sites-available/000-default.conf && \
    curl -SL https://raw.githubusercontent.com/lfuelling/spectre-importer-docker/master/scripts/entrypoint.sh -o /entrypoint.sh

RUN chmod +x /entrypoint.sh

COPY ./ $HOMEPATH

WORKDIR $HOMEPATH
RUN chown -R www-data:www-data $HOMEPATH && \
    chmod -R 775 $HOMEPATH/storage && \
    composer install --prefer-dist --no-dev --no-scripts --no-suggest

# Expose port 80
EXPOSE 80

# Run entrypoint thing
ENTRYPOINT ["/entrypoint.sh"]
