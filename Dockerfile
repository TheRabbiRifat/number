# Use an official PHP image with Apache
FROM php:8.1-apache

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Copy the PHP script to the container
COPY certificate_verify.php /var/www/html/index.php

# Set the working directory
WORKDIR /var/www/html

# Install necessary extensions (if needed, e.g., curl)
RUN docker-php-ext-install curl

# Expose port 80
EXPOSE 80
