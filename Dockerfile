FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

# Garante que somente o MPM prefork do Apache fique habilitado.
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite

# Railway fornece PORT=8080 por padrão para este serviço.
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#<Directory /var/www/html>#<Directory /var/www/html/public>#' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's#AllowOverride None#AllowOverride All#' /etc/apache2/apache2.conf

COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads/capas \
    && chown -R www-data:www-data /var/www/html/uploads \
    && find /var/www/html -type d -exec chmod 755 {} \\; \
    && find /var/www/html -type f -exec chmod 644 {} \\;

EXPOSE 8080
