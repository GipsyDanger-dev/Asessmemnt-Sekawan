FROM php:8.3-apache

RUN apt-get update && apt-get install -y libicu-dev libzip-dev \
    && docker-php-ext-install intl mysqli zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY . .
RUN chown -R www-data:www-data writable
RUN chmod +x docker-entrypoint.sh
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/conf-available/*.conf
ENTRYPOINT ["./docker-entrypoint.sh"]
