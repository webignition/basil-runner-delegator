#!/usr/bin/env bash

./integration-mutate-fixtures.sh
./integration-test.sh
./integration-reset-fixtures.sh
