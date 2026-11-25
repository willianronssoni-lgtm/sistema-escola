FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite

RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#<Directory /var/www/html>#<Directory /var/www/html/public>#' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#AllowOverride None#AllowOverride All#' /etc/apache2/apache2.conf \
    && printf '%s\n' 'Alias /assets /var/www/html/front/assets' '<Directory /var/www/html/front/assets>' '    Options -Indexes' '    AllowOverride None' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/front-assets.conf \
    && a2enconf front-assets

COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads/capas \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R u=rwX,g=rX,o=rX /var/www/html

EXPOSE 8080
