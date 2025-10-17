FROM framenetbrasil/php-fpm:8.3

ARG WWWGROUP=1001
ARG WWWUSER=1000
RUN addgroup -g $WWWGROUP www \
    && adduser -s /usr/bin/fish -D -G www -u $WWWUSER sail \
    && mkdir /var/log/laravel \
    && touch /var/log/laravel/laravel.log \
    && chown -R sail:www /var/log/laravel

#COPY . /www
#RUN chown -R sail:www /www

USER sail
WORKDIR /www

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN if [[ -n "$PROD" ]] ; then composer install; fi
