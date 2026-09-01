# =============================================================================
# LCDS — image PHP 8.4 + Apache pour le développement.
#
# Images publiques uniquement (pas de registre privé) : le dépôt est sur GitHub
# et doit pouvoir être construit par n'importe qui.
#
# Le code n'est PAS copié dans l'image : docker-compose bind-monte le projet.
# Une image de production (code figé, opcache.validate_timestamps=0) sera à
# ajouter le jour où une cible de déploiement existera — voir readme/ci-cd.md.
# =============================================================================
FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libicu-dev \
        zlib1g-dev \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libfreetype-dev \
        default-mysql-client \
        git \
        curl \
        unzip \
        less \
    && rm -rf /var/lib/apt/lists/*

# gd est compilé avec webp : le mu-plugin lcds-webp en dépend pour encoder les
# sous-tailles d'images. Retirer --with-webp casserait la conversion en silence,
# WordPress se rabattant sur le format source — voir readme/images.md.
RUN docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mysqli \
        zip \
        intl \
        gd \
        exif \
        opcache

RUN { \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 300'; \
        echo 'max_input_vars = 3000'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# revalidate_freq=0 : le code bind-monté est relu à chaque requête, sinon une
# modification sur l'hôte ne serait pas visible avant expiration du cache.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN curl -fsSL -o /usr/local/bin/wp \
        https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x /usr/local/bin/wp

# Filet pour un serveur MySQL présentant un certificat auto-signé : le client
# mariadb vérifie la chaîne par défaut et refuse la connexion. Sans effet sur le
# stack local (TLS y est désactivé côté serveur, voir docker-compose.yml), utile
# dès qu'on pointe le conteneur sur une base distante en TLS.
# Écrit dans ~/.my.cnf : dernier fichier lu par le client, donc celui qui prime.
RUN printf '[client]\nssl-verify-server-cert=0\n' > /root/.my.cnf \
    && chmod 600 /root/.my.cnf

# rewrite : permaliens et règles WebP. headers/expires/deflate : le bloc de
# sécurité de website/.htaccess les exige (voir readme/securite.md).
RUN a2enmod rewrite headers expires deflate
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
