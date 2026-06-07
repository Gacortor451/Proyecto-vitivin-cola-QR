FROM php:8.2-apache

# Instalar dependencias necesarias para GD + PostgreSQL + ZIP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    unzip \
    zip \
    libzip-dev \
    openssl

# Extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_pgsql zip

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Activar SSL en Apache
RUN a2enmod ssl

# Copiar tu configuración SSL personalizada
COPY www-ssl.conf /etc/apache2/sites-available/www-ssl.conf

# Activar tu VirtualHost SSL
RUN a2ensite www-ssl.conf
