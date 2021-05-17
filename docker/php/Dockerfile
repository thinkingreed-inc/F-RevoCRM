FROM php:7.4-apache
RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data
RUN set -xe; \
    apt-get update -yqq && \
    apt-get install -yqq --no-install-recommends \
      apt-utils vim gettext git \
      default-mysql-client \
      # for gd
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libwebp-dev \
      libxpm-dev \
      # for imap
      libc-client-dev libkrb5-dev \
      # for ImageMagick
      libmagickwand-dev \
      # for oniguruma
      libonig-dev \
      # for zip
      libzip-dev zip unzip && \
      # Install the zip extension
      docker-php-ext-install zip \
    && docker-php-ext-install bcmath gettext mbstring mysqli pdo pdo_mysql zip \
    && docker-php-ext-configure mbstring --disable-mbregex \
    # gd exif
    && docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd exif \
    # for imap
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install -j$(nproc) imap
RUN pecl install xdebug \
  && docker-php-ext-enable xdebug
