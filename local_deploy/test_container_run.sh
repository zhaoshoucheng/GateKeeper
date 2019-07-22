#!/bin/bash

module=`cat modulename`
app=$module
version=$1
if [ "$version" == "" ]; then
    echo -e "set version like v1.0.1  or  latest"
    exit 1
fi

echo "docker run --name $app -d cloud/$app:$version > run.pid"
docker run --name $app -d cloud/$app:$version > run.pid
run_pid=`cat run.pid`
echo "docker exec -it $run_pid /bin/bash"
docker exec -it $run_pid /bin/bash
exit 0