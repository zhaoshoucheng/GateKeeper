#!/bin/bash

run_pid=$1
if [ "$run_pid" == "" ]; then
    echo -e "set run_pid like d36cea36acb20dba"
    exit 1
fi

echo "docker stop $run_pid"
docker stop $run_pid
echo "docker rm $run_pid"
docker rm $run_pid
exit 0