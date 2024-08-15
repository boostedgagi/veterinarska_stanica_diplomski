## Koristimo PHP 8.1 kao osnovu
#FROM php:8.1-cli
#
## Instaliramo potrebne PHP ekstenzije
#RUN apt-get update && apt-get install -y \
#    && docker-php-ext-install pdo pdo_mysql zip
#
## Instaliramo Composer
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
#
## Postavljamo radni direktorijum u kontejneru
#WORKDIR /var/www
#
## Kopiramo projektne fajlove u kontejner
#COPY . .
#
## Instaliramo PHP zavisnosti
#RUN composer install --no-dev --optimize-autoloader
#
## Postavljamo prava za keširanje i logove
#RUN chown -R www-data:www-data var/cache var/log
#
## Kreiramo podrazumevanu komandu za pokretanje consumer-a
#CMD ["php", "bin/console", "messenger:consume", "async", "--time-limit=3600", "--memory-limit=128M", "--sleep=10"]
