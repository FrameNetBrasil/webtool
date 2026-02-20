FROM framenetbrasil/php-fpm:8.3

ARG WWWGROUP=1001
ARG WWWUSER=1000
RUN addgroup -g $WWWGROUP www \
    && adduser -s /usr/bin/fish -D -G www -u $WWWUSER sail \
    && mkdir /var/log/laravel \
    && touch /var/log/laravel/laravel.log \
    && chown -R sail:www /var/log/laravel \
    && apk add --no-cache graphviz ttf-freefont font-noto

#COPY . /www
#RUN chown -R sail:www /www

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy and set up entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER sail
WORKDIR /www

ENTRYPOINT ["docker-entrypoint.sh"]
