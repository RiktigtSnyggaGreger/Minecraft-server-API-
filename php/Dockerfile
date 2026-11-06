FROM php:8.4-fpm-alpine
RUN docker-php-ext-install mysqli
# Låt PHP läsa miljövariabler (DB_HOST osv) från docker-compose
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf