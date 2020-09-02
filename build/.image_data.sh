#!/usr/bin/env bash

IMAGE_REPOSITORY="smartassert/basil-runner-delegator"

DEFAULT_TAG="${TRAVIS_BRANCH:-master}"
TAG="${1:-${DEFAULT_TAG}}"

IMAGE_NAME=${IMAGE_REPOSITORY}:${TAG}
echo "Image name: "${IMAGE_NAME}
