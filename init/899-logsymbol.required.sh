#!/bin/bash
mkdir -p /home/xiaoju/data1/log/
mkdir -p /home/xiaoju/data1/nginx/log
mkdir -p /home/xiaoju/data1/php7/log
mkdir -p /home/xiaoju/php7/logs/cloud/itstool/
rm -rf /home/xiaoju/log
rm -rf /home/xiaoju/nginx/logs
rm -rf /home/xiaoju/php7/logs
ln -s /home/xiaoju/data1/log /home/xiaoju/log
ln -s /home/xiaoju/data1/nginx/log /home/xiaoju/nginx/logs
ln -s /home/xiaoju/data1/php7/log /home/xiaoju/php7/logs
chown -R xiaoju.xiaoju /home/xiaoju/data1


