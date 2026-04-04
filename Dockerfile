# Use the official PHP 8.2 image with Apache
FROM php:8.2-apache

# Install DB clients/server and PHP MySQL extensions.
# MariaDB server is used only when USE_EMBEDDED_DB=1 (free single-service mode).
RUN apt-get update && apt-get install -y --no-install-recommends \
    libmariadb-dev \
    mariadb-server \
    mariadb-client \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for pretty routes.
RUN a2enmod rewrite \
    && a2dismod mpm_event \
    && a2enmod mpm_prefork

# Reconfigure Apache to use /public as the Document Root.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Defaults for free demo mode.
ENV USE_EMBEDDED_DB=1
ENV DB_HOST=127.0.0.1
ENV DB_PORT=3306
ENV DB_NAME=ridex_db
ENV DB_USER=ridex_app
ENV DB_PASS=ridex_app_pass

# Copy project and startup script.
COPY . /var/www/html/
COPY docker/entrypoint.sh /usr/local/bin/ridex-entrypoint
RUN chmod +x /usr/local/bin/ridex-entrypoint \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["ridex-entrypoint"]
