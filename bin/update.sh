#!/usr/bin/env bash

SCRIPT_BASEDIR=$(dirname "$0")

set -e
cd "${SCRIPT_BASEDIR}/.."

mkdir -p tmp/twig_cache

set -x
./bin/console hotnano:update $*
