# ==========================
# Étape 1 : Image PHP
# ==========================
FROM php:8.2-cli

# Définir le dossier de travail
WORKDIR /app

# Installer les dépendances système + extension PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# ==========================
# Étape 2 : Installation de Composer
# ==========================
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# ==========================
# Étape 3 : Copier le code du projet
# ==========================
COPY . .

# Installer les dépendances Symfony
RUN composer install --no-interaction --optimize-autoloader

# ==========================
# Étape 4 : Exposer le port et lancer le serveur
# ==========================
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
