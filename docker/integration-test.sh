#!/usr/bin/env bash

docker-compose -f chrome/docker-compose.yml exec basil-runner-chrome ./bin/basil-runner generate --source=basil-integration/Test --target=build
docker-compose -f chrome/docker-compose.yml exec basil-runner-chrome ./bin/basil-runner run --path=build
