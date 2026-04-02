FROM php:8.2-apache

# Install system dependencies for MySQL and PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite for your routes
RUN a2enmod rewrite

# Copy your project files to the container
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html/

# Expose port 80 (Render will map this automatically)
EXPOSE 80
