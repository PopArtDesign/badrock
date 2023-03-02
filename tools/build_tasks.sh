#!/usr/bin/env sh

phive install --copy wp &&
    composer install --no-dev --optimize-autoloader
