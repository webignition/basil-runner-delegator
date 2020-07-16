#!/usr/bin/env bash

docker-compose exec basil-runner ./bin/basil-runner generate --source=basil-integration/Test --target=build
docker-compose exec basil-runner ./bin/basil-runner run --path=build
