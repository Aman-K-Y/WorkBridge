# ============================================
# WorkBridge - PHP Frontend Dockerfile
# Runs the PHP frontend on Apache, connecting
# to the Flask backend API via internal Docker network.
# ============================================

FROM php:8.2-apache

# Enable Apache modules needed
RUN a2enmod rewrite headers

# Install PHP extensions needed for cURL API calls and sessions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set Apache DocumentRoot to /var/www/html (default)
# Copy frontend files into the web root
COPY . /var/www/html/

# Create upload directories with proper permissions
RUN mkdir -p /var/www/html/uploads/avatars /var/www/html/uploads/resumes \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# Set proper ownership for the entire web directory
RUN chown -R www-data:www-data /var/www/html

# Apache config: allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

EXPOSE 80
