# 1. Imagen base oficial: PHP 8.3 sobre servidor Apache
FROM php:8.3-apache

# 2. Habilita el módulo mod_rewrite de Apache (esencial para el enrutamiento de Laravel)
RUN a2enmod rewrite

# 3. Instala librerías nativas del sistema Linux y compila extensiones PHP requeridas
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 3.5 Configura el DocumentRoot de Apache hacia la carpeta public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/smartlibrary-api/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# 4. Inyecta el ejecutable binario de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/smartlibrary-api