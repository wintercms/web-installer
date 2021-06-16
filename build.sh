#!/usr/bin/env bash

usage() {
    cat << NOTICE
OPTIONS
-h      show this message
-s      hide npm/composer output
-v      enable verbose output
NOTICE
}

say() {
    [ "$VERBOSE" ] || [ "$2" ] && echo "==> $1"
}

prepare_command() {
    local COMMAND="$1"
    
    if [ "$SILENT" ]; then
        COMMAND="${COMMAND} > /dev/null 2>&1"
    fi

    echo "$COMMAND"
}

BASE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
NPM_PATH=$(which npm)
COMPOSER_PATH=$(which composer)

if [ -z "$NPM_PATH" ]; then
    say "npm is not installed, exiting." 1
    exit 1
fi

if [ -z "$COMPOSER_PATH" ]; then
    say "composer is not installed, exiting." 1
    exit 1
fi

VERBOSE=""
SILENT=""

while getopts hsv opts; do
    case ${opts} in
        h) usage && exit 0 ;;
        s) SILENT=1 ;;
        v) VERBOSE=1 ;;
        *);;
    esac
done

COMMAND="${NPM_PATH} --prefix ${BASE_DIR} run build"
COMMAND=$(prepare_command "$COMMAND")

say "running npm production build"
sh -c "$COMMAND"

COMMAND="${COMPOSER_PATH} install -d ${BASE_DIR}/dist/install/api --no-dev --optimize-autoloader"
COMMAND=$(prepare_command "$COMMAND")

say "running composer install"
sh -c "$COMMAND"

