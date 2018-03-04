#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname "$0")


set -e
which docker &> /dev/null || { echo 'ERROR: docker not found in PATH'; exit 1; }

cd "${SCRIPT_BASEDIR}/.."
. ./.env

docker run \
	--rm \
	--tty \
	--interactive \
	--publish 7075:7075/udp \
	--publish 7075:7075/tcp \
	--publish 127.0.0.1:7076:7076 \
	--volume "$HOME/RaiBlocks":/root \
	--name nano \
	--hostname nano \
	nanocurrency/nano
