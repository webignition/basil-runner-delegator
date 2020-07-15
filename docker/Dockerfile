FROM php:7.4-cli-buster

WORKDIR /app

COPY bin /app/bin
COPY src /app/src
COPY composer.json /app
COPY composer.lock /app
COPY phpunit.run.xml /app

RUN apt-get update \
    && apt-get install -y libzip-dev nano zip \
    && docker-php-ext-install zip > /dev/null

RUN curl https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb --output chrome.deb \
    && apt-get install -y ./chrome.deb \
    && rm ./chrome.deb

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer setup

RUN cd vendor/symfony/panther/chromedriver-bin \
    && ./update.sh \
    && cd ../../../..

RUN apt-get autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN mkdir /app/build
