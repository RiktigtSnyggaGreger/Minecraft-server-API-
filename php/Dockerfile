FROM php:8.4-fpm-alpine
RUN docker-php-ext-install mysqli
# Låt PHP läsa miljövariabler (DB_HOST osv) från docker-compose
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-docker.conf
# Visa inte fel och PHP-version publikt. Fel hamnar i: docker compose logs php
RUN printf "display_errors = Off\nlog_errors = On\nexpose_php = Off\n" > /usr/local/etc/php/conf.d/zz-security.ini