#!/usr/bin/env bash

CURRENT_DIRECTORY="$(dirname "$0")"
source ${CURRENT_DIRECTORY}/.image_data.sh

OUTPUT=$(docker images | tail -n +2 | awk '{print $1":"$2}' | grep ${IMAGE_NAME} | wc -l)

if [ ${OUTPUT} != "1" ]; then
  echo "Tagged image \"${IMAGE_NAME}\" generation failed"
  exit 1
else
  echo "Tagged image \"${IMAGE_NAME}\" generation successful"
fi
