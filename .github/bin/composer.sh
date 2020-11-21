#!/bin/bash

EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
  >&2 echo 'ERROR: Invalid installer checksum'
  rm composer-setup.php
  exit 1
else
  php composer-setup.php --install-dir="/usr/bin" --filename=composer
  RESULT=$?
  rm composer-setup.php
  composer config platform.php 5.6.50
  exit $RESULT
fi
