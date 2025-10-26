FROM php:8.3-bullseye AS base
WORKDIR /app

ENV COMPOSER_MEMORY_LIMIT=-1
######################################################
# Step 1 | Install Dependencies
######################################################
RUN apt-get update \
    && apt-get install -y curl git unzip openssl tar ca-certificates procps \
    && apt-get clean -y

#RUN install-php-extensions gd bcmath pdo_mysql zip intl opcache pcntl redis swoole exif zip bz2 @composer
RUN curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - | sh -s \
      gd bcmath pdo_mysql zip intl opcache pcntl redis swoole exif zip bz2 mbstring fileinfo igbinary imagick @composer
######################################################
# Copy Configuration
######################################################
COPY .github/docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY .github/docker/php/php.ini $PHP_INI_DIR/conf.d/php.ini

######################################################
# Step 6 | Configure Credentials & Hosts for external Git (optional)
######################################################
COPY composer.json composer.lock /app/
RUN composer install --no-dev --no-scripts --no-autoloader
######################################################
# Local Stage
######################################################
FROM base AS local
RUN addgroup -gid 1024 app \
  && adduser -uid 1024 --disabled-password --ingroup app app \
  && adduser www-data app \
  && curl -fsSL https://deb.nodesource.com/setup_16.x | bash - \
  && apt-get update \
  && apt-get install -y nodejs \
  && apt-get install -y mysql-client \
  && apt-get clean -y \
  && install-php-extensions xdebug
USER app
# yarn install as command
CMD sh -c "composer install && php artisan octane:start --watch --host=0.0.0.0 --port=80"
######################################################
# Build Ziggy Package - Vite needs ziggy package available
######################################################
FROM base AS vite-vendor-build
WORKDIR /app
RUN COMPOSER_ALLOW_SUPERUSER=1 | rm composer.lock composer.json && composer require tightenco/ziggy --ignore-platform-reqs
######################################################
# NodeJS Stage
######################################################
FROM node:20-buster AS vite
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm install
COPY ./resources /app/resources
COPY --from=vite-vendor-build /app/vendor/tightenco/ziggy /app/vendor/tightenco/ziggy
RUN npm run build
######################################################
# Production Stage
######################################################
FROM base AS production
COPY --from=vite /app/public/build ./public/build
COPY . /app/

RUN composer install --no-dev --optimize-autoloader
RUN chmod 777 -R bootstrap storage
RUN rm -rf .env bootstrap/cache/*.php
RUN chown -R www-data:www-data /app
RUN rm -rf ~/.composer

CMD ["sh", "-c", "php artisan octane:start --host=0.0.0.0 --port=80"]
