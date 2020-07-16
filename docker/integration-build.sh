#!/usr/bin/env bash

cp -R ../tests/Fixtures/html/* nginx/html
docker-compose -f docker-compose.yml build
docker-compose -f chrome/docker-compose.yml -f chrome/docker-compose-test.yml build
docker-compose -f chrome/docker-compose.yml -f chrome/docker-compose-test.yml up --detach
