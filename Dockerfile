# Image de démo pour Render (offre gratuite) : PHP 8.3 + Apache (Debian 12 « bookworm »), DocumentRoot sur /public.
# Les assets Vite compilés (public/build) sont versionnés dans git : aucune étape Node n'est nécessaire.
# Aucun secret dans l'image : toute la configuration arrive par variables d'environnement au démarrage.
#
# Multi-stage (F1) : Composer, git et unzip ne servent qu'à la construction et ne sont PAS dans l'image finale.
# Base épinglée sur Debian 12 (bookworm) : les noms de paquets ne changent pas d'un jour à l'autre (sur trixie,
# l'ancien nom du paquet freetype a disparu au profit de libfreetype-dev). Les extensions PHP sont installées par
# install-php-extensions (mlocati), qui choisit lui-même les bons paquets Debian et ne garde que les bibliothèques
# d'exécution (aucun paquet -dev ni compilateur dans l'image finale).

# ---------- Étape 1 : dépendances PHP (sans les dépendances de dev : Telescope, Debugbar, Pail, PHPUnit…) ----------
FROM composer:2 AS vendor
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1
WORKDIR /app

# Couche mise en cache tant que composer.json / composer.lock ne changent pas.
COPY composer.json composer.lock ./
# Les exigences de plateforme (version de PHP, ext-gd, ext-zip) sont celles de l'image FINALE (PHP 8.3 + extensions),
# pas de l'image Composer (sa version de PHP peut dépasser les bornes de certains paquets) : on les ignore ici ;
# l'étape finale vérifie que les extensions sont bien chargées.
RUN composer install --no-dev --no-scripts --no-autoloader --no-progress --ignore-platform-reqs

COPY . .
RUN rm -f bootstrap/cache/*.php \
    && composer dump-autoload --no-dev --optimize --no-scripts --no-interaction --ignore-platform-reqs

# ---------- Étape 2 : image d'exécution ----------
FROM php:8.3-apache-bookworm

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    PORT=10000

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

# gd (jpeg/webp/freetype : QR codes et dompdf), pdo_mysql, bcmath, zip (Excel), intl, exif, opcache.
# mbstring, xml, fileinfo, tokenizer, ctype sont déjà dans l'image officielle.
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions gd pdo_mysql bcmath zip intl exif opcache \
    && php -r 'foreach (["gd","pdo_mysql","bcmath","zip","intl","exif","mbstring","xml","fileinfo","tokenizer","ctype","Zend OPcache"] as $e) { if (!extension_loaded($e)) { fwrite(STDERR, "Extension PHP manquante : $e\n"); exit(1); } }'

# Apache : mod_rewrite + headers, DocumentRoot /public, port lu dans $PORT (Render l'injecte, 10000 par défaut).
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

# Code puis dépendances construites à l'étape 1 (vendor/ est exclu du contexte par .dockerignore).
COPY . .
COPY --from=vendor /app/vendor ./vendor

# Les fichiers bootstrap/cache/*.php versionnés ont été générés AVEC les paquets de dev : on les supprime puis on
# régénère la découverte des paquets (package:discover) à partir du vendor/ sans dépendances de dev.
RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
