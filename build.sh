#!/bin/bash
# Halt on errors
set -e

# Be verbose
set -x
#export PATH=/home/xiaoju/php7/bin:$PATH

function compose() {
      (
          composer7 clearcache
          composer7 install &&
         echo -e "===== composer install ok ====="
     ) || ( echo -e "===== composer install failure =====" &&  exit 2)
}

function make_output() {
    # 创建output目录，用于存放产出
    local output="output"
    if [ -d $output ];then
        rm -rf $output
    fi
    mkdir -p $output
  
    # 填充output目录, output的内容即为待部署内容
    cp -rf index.php composer.json composer.lock contributing.md user_guide system vendor application ${output}/       # 拷贝必要的文件和目录至output目录,
                                             #此处$file和$directory表分别示欲拷贝的文件和目录
    local ret=$?
    return $ret
}
  
##########################################
## main
## 其中,
##
##      1.生成部署包output
##########################################
# 1.生成部署包output
#compose
make_output
ret=$?
if [ $ret -eq 0 ];then
    echo -e "===== Generate output ok ====="
else
    echo -e "===== Generate output failure ====="
fi
exit $ret
