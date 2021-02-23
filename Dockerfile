FROM php:8-cli-buster

ARG proxy_server_version=0.5

WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock phpunit.run.xml /app/
COPY bin /app/bin
COPY src /app/src

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev zip \
    && docker-php-ext-install pcntl sockets zip > /dev/null \
    && rm -rf /var/lib/apt/lists/* \
    && composer check-platform-reqs --ansi \
    && composer install --prefer-dist --no-dev \
    && composer clear-cache \
    && curl https://raw.githubusercontent.com/webignition/docker-tcp-cli-proxy/${proxy_server_version}/composer.json --output composer.json \
    && curl https://raw.githubusercontent.com/webignition/docker-tcp-cli-proxy/${proxy_server_version}/composer.lock --output composer.lock \
    && composer check-platform-reqs --ansi \
    && rm composer.json \
    && rm composer.lock \
    && rm /usr/bin/composer \
    && curl -L https://github.com/webignition/docker-tcp-cli-proxy/releases/download/${proxy_server_version}/server.phar --output ./server \
    && chmod +x ./server

CMD ./server
