#!/bin/bash
#检查参数
module=`cat modulename`
app=$module
version=$1
if [ "$version" == "" ]; then
    echo -e "set version like v1.0.1  or  latest"
    exit 1
fi

#同步数据
rm -rf ./tmp
mkdir ./tmp
ls ../ | grep -v 'local_deploy' | xargs -I {} cp -rf ../{} ./tmp/
cp database.php ./application/config/
cp nconf.php ./application/config/
cp redis.php ./application/config/backend/
cd ./tmp

#打包
echo "docker build -t cloud/$app:$version ."
docker build -t cloud/$app:$version .
echo -e "make image done"

#删除tmp目录
rm -rf ./tmp
exit 0