FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy all files
COPY . /var/www/html/

# Set correct permissions (THIS IS THE FIX)
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Configure Apache to serve files from /var/www/html
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80