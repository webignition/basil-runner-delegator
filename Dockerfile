FROM php:7.4-cli-buster

ARG proxy_server_version=0.5

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev zip \
    && docker-php-ext-install pcntl sockets zip > /dev/null \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer self-update --2

RUN echo "Checking runner platform requirements"
COPY composer.json /app
COPY composer.lock /app
RUN composer check-platform-reqs --ansi

RUN echo "Installing runner"
COPY bin /app/bin
COPY src /app/src
COPY phpunit.run.xml /app
RUN composer install --prefer-dist --no-dev
RUN composer clear-cache

RUN echo "Checking proxy server platform requirements ${proxy_server_version}"
RUN curl https://raw.githubusercontent.com/webignition/docker-tcp-cli-proxy/${proxy_server_version}/composer.json --output composer.json
RUN curl https://raw.githubusercontent.com/webignition/docker-tcp-cli-proxy/${proxy_server_version}/composer.lock --output composer.lock
RUN composer check-platform-reqs --ansi
RUN rm composer.json
RUN rm composer.lock

RUN rm /usr/local/bin/composer

RUN echo "Fetching proxy server ${proxy_server_version}"
RUN curl -L https://github.com/webignition/docker-tcp-cli-proxy/releases/download/${proxy_server_version}/server.phar --output ./server
RUN chmod +x ./server

CMD ./server
