#!/bin/bash

echo "deb https://dl.hhvm.com/ubuntu $(lsb_release -sc)-lts-$1 main" >> /etc/apt/sources.list
apt-get update
apt-get --allow-downgrades --reinstall install hhvm/$(lsb_release -sc)-lts-$1
