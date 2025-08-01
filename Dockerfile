FROM php:8.2-apache

# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers deflate

# Installer les dépendances système
RUN apt-get update -y && apt-get install -y \
    supervisor \
    build-essential \
    git \
    curl \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    libonig-dev \
    libxml2-dev \
    sudo \
    zip \
    unzip \
    npm \
    nodejs \
    libpng-dev \
    libzip-dev \
    libicu-dev \
    exiftool \
    libpq-dev \
    libmagickwand-dev --no-install-recommends && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer et activer Imagick
RUN pecl install imagick && docker-php-ext-enable imagick

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install sockets pdo pdo_mysql gd zip exif intl && \
    docker-php-ext-configure gd --with-jpeg && \
    docker-php-ext-enable exif

# Configurer Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    apt-get update && apt-get install -y nano

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de l'application avec le bon propriétaire
COPY --chown=www-data:www-data . ./
COPY tailwind.config.js postcss.config.js /var/www/html/

# Installer les dépendances PHP
RUN composer update && composer dump-autoload --optimize

# Définir les permissions des répertoires de stockage et de bootstrap
# S'assurer que les dossiers existent avant de changer les permissions
RUN mkdir -p storage/framework/cache/data storage/app/public && \
    chown -R www-data:www-data storage bootstrap && \
    chmod -R 775 storage bootstrap && \
    chown -R www-data:www-data storage/framework/cache/data storage/app/public && \
    chmod -R 775 storage/framework/cache/data storage/app/public


# Configurer les permissions pour le répertoire config
RUN chown www-data:www-data config  && \
    chmod 775 config 

# Exécuter les commandes Artisan
RUN php artisan config:clear && \
    php artisan cache:clear && \
    php artisan key:generate && \
    php artisan optimize:clear && \
    php artisan storage:link

# Configurer Apache avec le fichier de configuration personnalisé
RUN rm /etc/apache2/sites-available/000-default.conf && \
    rm /etc/apache2/sites-enabled/000-default.conf && \
    cp vhost.docker.conf /etc/apache2/sites-available/vhost.docker.conf && \
    a2ensite vhost.docker.conf

# Exposer le port 80
EXPOSE 80

