FROM runner

RUN curl https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb --output chrome.deb \
    && apt-get install -y ./chrome.deb \
    && rm ./chrome.deb

RUN cd vendor/symfony/panther/chromedriver-bin \
    && ./update.sh \
    && cd ../../../..

RUN apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
