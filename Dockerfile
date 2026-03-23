FROM php:8.2-apache

# Route all requests through index.php
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

RUN apt-get update && apt-get install -y libpq-dev libzip-dev curl zip unzip git && rm -rf /var/lib/apt/lists/*

# Trust corporate CA certificates (if needed)
# RUN apt-get update && apt-get install -y ca-certificates && rm -rf /var/lib/apt/lists/*
# COPY windows-ca-bundle.pem /usr/local/share/ca-certificates/windows-ca-bundle.crt
# RUN update-ca-certificates

RUN docker-php-ext-install pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/html/
WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --no-progress

RUN chown -R www-data:www-data /var/www/html
