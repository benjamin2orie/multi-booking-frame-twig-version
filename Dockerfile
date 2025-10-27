
# Use official PHP image with Apache
FROM php:8.2-apache

# Install required PHP extensions and tools
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# ✅ Copy full project into /var/www/app
COPY . /var/www/app/

# Set working directory
WORKDIR /var/www/app/

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install

# ✅ Move public files to Apache's root
RUN cp -r public/* /var/www/html/

# ✅ Set permissions
RUN chown -R www-data:www-data /var/www/html





# # Use official PHP image with Apache
# FROM php:8.2-apache

# # Install required PHP extensions and tools
# RUN apt-get update && apt-get install -y \
#     unzip \
#     git \
#     libzip-dev \
#     zip \
#     && docker-php-ext-install pdo pdo_mysql

# # Enable Apache mod_rewrite
# RUN a2enmod rewrite

# # ✅ Copy everything (not just public/)
# COPY . /var/www/app/

# # Set working directory
# WORKDIR /var/www/app/

# # Install Composer
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# # ✅ Install dependencies from root
# RUN composer install

# # ✅ Move public folder to Apache's root
# RUN cp -r public/* /var/www/html/

# # Set permissions
# RUN chown -R www-data:www-data /var/www/html
