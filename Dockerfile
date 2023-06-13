
ARG PHP_VERSION="8.0.9"

###############################################################################
# Builder Images
###############################################################################
FROM composer/composer:2-bin AS composer

FROM mlocati/php-extension-installer:latest AS php_extension_installer

###############################################################################
# Assets
###############################################################################
FROM node:16 as runalyze_assets

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        gettext \
    && rm -rf /var/cache/apt/archives /var/lib/apt/lists

###############################################################################
# Web Server
###############################################################################
FROM php:${PHP_VERSION}-apache AS runalyze_php

ENV SYMFONY_ENV=dev SYMFONY_DEBUG=1 XDEBUG_MODE=develop

WORKDIR /var/www/runalyze

COPY --from=php_extension_installer --link /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
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
        zip
    # && rm -rf /var/lib/apt/lists/*

RUN echo "Europe/Berlin" > /etc/timezone && \
    dpkg-reconfigure --frontend=noninteractive tzdata && \
    sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
    echo 'LANG="en_US.UTF-8"'>/etc/default/locale && \
    dpkg-reconfigure --frontend=noninteractive locales && \
    update-locale LANG=en_US.UTF-8
ENV LC_ALL=en_US.UTF-8
ENV LANG=en_US.UTF-8
ENV LANGUAGE=en_US

# install-php-extensions clears the apt cache at the end
RUN set -eux; \
    IPE_GD_WITHOUTAVIF=1 install-php-extensions \
        # apcu \
        ast \
        bcmath \
        gettext \
        intl \
        # opcache \
        pdo_mysql \
        xdebug \
        zip \
    ;

RUN a2enmod rewrite

RUN mkdir /var/www/sqlite3_ext && \
    cp /usr/lib/x86_64-linux-gnu/mod_spatialite.so /var/www/sqlite3_ext/mod_spatialite.so

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --link docker/php-overrides.ini "$PHP_INI_DIR/conf.d/"

ENV APACHE_DOCUMENT_ROOT /var/www/runalyze/web

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer --link /composer /usr/bin/composer

# prevent the reinstallation of vendors after every change in the source code
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

ARG HOST_UID=1000
ARG HOST_GID=1000
CMD [ "sh", "-c", "rsync -rA --delete vendor /tmp; chown -R ${HOST_UID}:${HOST_GID} /tmp/vendor; apache2-foreground" ]