#!/bin/bash
# Halt on errors
set -e
cd /home/xiaoju/webroot/ipd-cloud/application/itstool
git reset --hard
git pull
cp  -rf application /home/xiaoju/webroot/ipd-cloud/application/itstoolweb/