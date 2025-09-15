FROM php:8.4
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME="/opt/composer"
ENV PATH="$PATH:/opt/composer/vendor/bin"
RUN apt-get update &&\
    apt-get install -y zip npm yt-dlp ffmpeg &&\
    docker-php-ext-install pdo_mysql

COPY ./app /var/www/html
WORKDIR /var/www/html
