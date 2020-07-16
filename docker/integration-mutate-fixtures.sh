#!/usr/bin/env bash

docker-compose -f chrome/docker-compose.yml -f chrome/docker-compose-test.yml exec basil-runner-chrome sed -i 's/127.0.0.1:9080/nginx/g' basil-integration/Test/index-page-test.yml
docker-compose -f chrome/docker-compose.yml -f chrome/docker-compose-test.yml exec basil-runner-chrome sed -i 's/127.0.0.1:9080/nginx/g' basil-integration/Test/form-page-test.yml
docker-compose -f chrome/docker-compose.yml -f chrome/docker-compose-test.yml exec basil-runner-chrome sed -i 's/127.0.0.1:9080/nginx/g' basil-integration/Page/form.yml
