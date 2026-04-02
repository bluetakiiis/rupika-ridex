# Use the official PHP 8.2 image with Apache
FROM php:8.2-apache

# 1. Install necessary system dependencies and PHP extensions for MySQL
# This matches the MariaDB/MySQL requirements in your vehicles.sql file
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    && docker-php-ext-install pdo pdo_mysql

# 2. Enable Apache mod_rewrite
# Critical for the routing logic in your public/.htaccess and index.php
RUN a2enmod rewrite

# 3. Reconfigure Apache to use /public as the Document Root
# This fixes the "Cannot serve directory /var/www/html/" error from your logs
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Copy your project files into the container
COPY . /var/www/html/

# 5. Set proper ownership for the web server
RUN chown -R www-data:www-data /var/www/html

# Expose the default Apache port
EXPOSE 80
