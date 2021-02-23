#!/usr/bin/env bash

docker build -t "smartassert/basil-runner-delegator:${TAG_NAME:-master}" .
