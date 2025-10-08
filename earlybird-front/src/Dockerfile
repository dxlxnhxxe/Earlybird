# Étape 1 : Image PHP officielle
FROM php:8.3-cli

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip libpq-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le dossier de travail
WORKDIR /var/www/html

# Copier les fichiers du projet dans le conteneur
COPY . .

# Installer les dépendances Symfony
#RUN composer install --no-interaction --prefer-dist --no-scripts

# Exposer le port 8000
EXPOSE 8000

# Démarrer le serveur Symfony intégré
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
