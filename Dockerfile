# Utiliser l'image PHP officielle avec extensions
FROM php:8.2-fpm

# Installer des dépendances
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    unzip \
    git \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd mbstring exif pcntl bcmath

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers du projet dans le conteneur
COPY . .

# Configurer Git pour composer
RUN git config --global --add safe.directory /var/www

# Configurer les permissions sur le répertoire de travail
RUN chown -R www-data:www-data /var/www

# Installer les dépendances du projet en deux étapes
RUN composer install --no-scripts --no-autoloader \
    && composer dump-autoload --optimize

# Copier le fichier d'environnement et générer la clé
COPY .env.example .env
RUN php artisan key:generate

# Configurer les permissions sur le stockage et le cache
RUN chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage \
    && chown -R www-data:www-data /var/www/bootstrap/cache

# Exposer le port
EXPOSE 9000

# Commande pour démarrer l'application
CMD php artisan serve --host=0.0.0.0 --port=9000