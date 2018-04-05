#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname "$0")


set -e
which docker &> /dev/null || { echo 'ERROR: docker not found in PATH'; exit 1; }

cd "${SCRIPT_BASEDIR}/.."
. ./.env

set -x
docker run \
	--rm \
	--tty \
	--interactive \
	--publish ${LOCAL_PORT}:7075/udp \
	--publish ${LOCAL_PORT}:7075/tcp \
	--publish 127.0.0.1:${RPC_PORT}:7076 \
	--volume "$HOME/Library/RaiBlocks_HotNano":/root \
	--name nano \
	--hostname nano \
	nanocurrency/nano
