FROM php:8-alpine3.14

LABEL author="Fernando Piovezan"

ENV APP_ROOT=/home/app QUEUE_DRIVER=database NUM_PROCS=4 OPTIONS=--queue=some_queue --sleep=3 --tries=3

ADD supervisord.conf /etc/

RUN docker-php-ext-install pdo_mysql \
    && docker-php-ext-install bcmath \
    && apk add --update supervisor \
    \

RUN adduser -G root -u 1000 -h /home/baseuser baseuser -D

CMD ["supervisord", "-c", "/etc/supervisord.conf"]