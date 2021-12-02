FROM php:8-alpine3.14

LABEL author="Fernando Piovezan" \
        version.image="v0.1" \
        version.php=$PHP_VERSION \
        description="Configuração para trabalhar com os Workers do laravel no docker"

ENV APP_ROOT=/home/app QUEUE_DRIVER=database NUM_PROCS=4 OPTIONS=""

ADD supervisord.conf /etc/

RUN docker-php-ext-install pdo_mysql \
    && docker-php-ext-install bcmath \
    && apk add --update supervisor \
    \

RUN adduser -G root -u 1000 -h /home/baseuser baseuser -D

CMD ["supervisord", "-c", "/etc/supervisord.conf"]