# Version 0.1

FROM registry.xiaojukeji.com/didionline/sre-didi-lnp7-centos6-base-v2:stable

RUN mkdir -p /home/xiaoju/webroot/logs/itstool

COPY ./init /etc/container/init

COPY ./ /home/xiaoju/webroot/itstool

COPY ./init/nginx.conf /home/xiaoju/nginx/conf/nginx.conf

COPY ./init/itstool.conf /home/xiaoju/nginx/conf/conf.d/itstool.conf

COPY ./init/php.ini /home/xiaoju/php7/etc/php.ini

COPY ./init/php-fpm.conf /home/xiaoju/php7/etc/php-fpm.conf