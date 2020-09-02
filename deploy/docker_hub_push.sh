#!/usr/bin/env bash

CURRENT_DIRECTORY="$(dirname "$0")"
source ${CURRENT_DIRECTORY}/../build/.image_data.sh
source ${CURRENT_DIRECTORY}/docker_hub_login.sh

docker push ${IMAGE_NAME}
