#!/usr/bin/env sh

export PATH="${PATH}:$(realpath "$(dirname $0)")"

wp core is-installed && \
    wp maintenance-mode activate && \
    wp core update-db && \
    wp cache flush && \
    wp maintenance-mode deactivate
