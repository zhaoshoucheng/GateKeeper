#!/bin/bash
#############################################
## main
## 以托管方式, 启动服务
## control.sh脚本, 必须实现start方法
#############################################

action=$1
case $action in
    "start" )
        # 启动服务, 以前台方式启动, 否则无法托管, 注意app和conf中是否有""出现, 保证脚本的正确性
        #exec "./bin/${app}"
        ;;
    * )
        # 非法命令, 已非0码退出
        #echo "unknown command"
        exit 1
        ;;
esac