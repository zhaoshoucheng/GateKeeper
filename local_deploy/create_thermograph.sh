#!/bin/bash
d1=`date -d "yesterday" +%Y-%m-%d`
ret = `curl 'http://test.sts.xiaojukeji.com/sg1/api/signalpro/api/AreaReport/createAreaThermograph?city_id=12&area_id=504&date=$d1'`
sleep 10
echo $ret