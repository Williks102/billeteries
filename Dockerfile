# Image de démo pour Render (offre gratuite) : PHP 8.3 + Apache, DocumentRoot sur /public.
# Les assets Vite compilés (public/build) sont versionnés dans git : aucune étape Node n'est nécessaire.
# Aucun secret dans l'image : toute la configuration arrive par variables d'environnement au démarrage.
FROM php:8.3-apache

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=10000

# Extensions : gd (jpeg/webp/freetype, requis pour les QR codes et dompdf), pdo_mysql, bcmath, zip (Excel),
# intl, exif, opcache. mbstring, xml, fileinfo, tokenizer, ctype sont déjà dans l'image officielle.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip ca-certificates \
        libfreetype6-dev libjpeg62-turbo-dev libwebp-dev libpng-dev \
        libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql bcmath zip intl exif opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache : mod_rewrite, DocumentRoot /public, port lu dans $PORT (Render l'injecte, 10000 par défaut).
RUN a2enmod rewrite headers \
    && sed -ri 's#/var/www/html#${APACHE_DOCUMENT_ROOT}#g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri 's#Listen 80#Listen ${PORT}#' /etc/apache2/ports.conf \
    && sed -ri 's#<VirtualHost \*:80>#<VirtualHost *:${PORT}>#' /etc/apache2/sites-available/000-default.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername
COPY docker/apache-render.conf /etc/apache2/conf-available/render.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-render.ini
RUN a2enconf render

WORKDIR /var/www/html

# Dépendances d'abord (couche mise en cache) : SANS les dépendances de dev (Telescope, Debugbar, Pail, PHPUnit…).
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress

COPY . .

# Les fichiers bootstrap/cache/*.php versionnés ont été générés AVEC les paquets de dev : on les supprime pour que
# package:discover (lancé par dump-autoload) les régénère sans Telescope/Debugbar.
RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
