# Use an official PHP image with Apache
FROM php:8.1-apache

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Install libcurl and other necessary dependencies
RUN apt-get update && \
    apt-get install -y libcurl4-openssl-dev pkg-config && \
    docker-php-ext-install curl

# Copy the PHP script to the container
COPY index.php /var/www/html/index.php
COPY check-json.php /var/www/html/check-json.php

# Set the working directory
WORKDIR /var/www/html

# Expose port 80
EXPOSE 80
