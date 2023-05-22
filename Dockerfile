
ARG PHP_VERSION="8.0.9"

###############################################################################
# Builder Images
###############################################################################
FROM composer/composer:2-bin AS composer

FROM mlocati/php-extension-installer:latest AS php_extension_installer

###############################################################################
# Web Server
###############################################################################
FROM php:${PHP_VERSION}-apache AS runalyze_php

ENV SYMFONY_ENV=dev SYMFONY_DEBUG=1 XDEBUG_MODE=off

WORKDIR /var/www/runalyze

COPY --from=php_extension_installer --link /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y \
    gettext \
    git \
    inkscape \
    libsqlite3-mod-spatialite \
    locales \
    python3 \
    python3-pip \
    rsync \
    sqlite3 \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

RUN echo "Europe/Berlin" > /etc/timezone && \
    dpkg-reconfigure --frontend=noninteractive tzdata && \
    sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
    echo 'LANG="en_US.UTF-8"'>/etc/default/locale && \
    dpkg-reconfigure --frontend=noninteractive locales && \
    update-locale LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8
ENV LANG=en_US.UTF-8
ENV LANGUAGE=en_US

RUN set -eux; \
    install-php-extensions \
        # apcu \
        gettext \
        intl \
        # opcache \
        pdo_mysql \
        xdebug \
        zip \
    ;

RUN a2enmod rewrite

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

ENV APACHE_DOCUMENT_ROOT /var/www/runalyze/web

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer --link /composer /usr/bin/composer

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* ./
RUN set -eux; \
    if [ -f composer.json ]; then \
        composer install --ignore-platform-reqs --no-autoloader --no-scripts --no-progress; \
        composer clear-cache; \
    fi

# copy sources
COPY --link . ./

RUN set -eux; \
    mkdir -p var/cache var/log; \
    chown -R www-data:www-data var data; \
    if [ -f composer.json ]; then \
        composer dump-autoload; \
        # composer dump-autoload --classmap-authoritative; \
        # composer dump-env prod; \
        # composer run-script post-install-cmd; \
        chmod +x bin/console; sync; \
    fi

CMD [ "sh", "-c", "rsync -ru --delete vendor /tmp; apache2-foreground" ]