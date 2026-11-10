ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-apache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev libcurl4-openssl-dev \
    && docker-php-ext-install curl pdo_mysql pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite
 
# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install backend dependencies and copy the app
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
COPY . /var/www/html/

# Prepare writable directories before setting ownership
RUN mkdir -p /var/www/html/runtime/cache /var/www/html/runtime/session \
        /var/www/html/runtime/pwesto-documents \
        /var/www/html/public/uploads/pwesto-products \
        /var/www/html/public/uploads/pwesto-profile \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod -R 775 /var/www/html/runtime /var/www/html/public/uploads
# Point Apache document root to public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
 
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot ${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/sites-available/000-default.conf \
&& sed -i 's|<Directory /var/www/html>|<Directory ${APACHE_DOCUMENT_ROOT}>|g' /etc/apache2/apache2.conf
 
EXPOSE 80

VOLUME ["/var/www/html/runtime", "/var/www/html/public/uploads"]