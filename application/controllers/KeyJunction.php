<?php
/***************************************************************
# 干线绿波类
# user:ningxiangbing@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class KeyJunction extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function stopDelayTopList(){
        echo '{
    "errno": 0,
    "errmsg": "",
    "data": {
        "dataList": [
            {
                "time": "07:20",               
                "logic_junction_id": "xxxxxxxx",
                "junction_name": "yyyyyyy",
                "stop_delay":120,
                "quota_unit":"秒"
            },
            {
                "time": "07:20",
                "logic_junction_id": "xxxxxxxx",
                "junction_name": "yyyyyyy",
                "stop_delay":120,
                "quota_unit":"秒"                  
            }
       ]
    },
    "username": "unknown"
}';
    exit;
    }


    public function stopDelayCurve(){
        echo '{
    "errno": 0,
    "errmsg": "",
    "data": {
        "datalist": {
            "2018-12-11": [ 
                [9.72, "00:00"],
                [9.75, "00:06"],
                [9.66, "00:10"],
                [9.57, "00:15"],
                [9.54, "00:20"],
                [9.33, "00:27"]
            ]
        },
        "info": {
            "value": 0,
            "unit": "秒"
        }
    },
    "traceid": "645acf295c0f682bb59a0cd30db17a02",
    "username": "18953101270",
    "time": {
        "a": "0.1136秒",
        "s": "0.0438秒"
    }
}';
exit;
    }

    public function getJunctionTiming()
    {
        echo '{"errno":0,"errmsg":"","data":{"cycle":"200","offset":"161","timing":[{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"北左"},{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"北直"},{"state":"1","start_time":"0","duration":"35","end_time":35,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"西左"},{"state":"1","start_time":"35","duration":"83","end_time":118,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"东直"},{"state":"1","start_time":"35","duration":"83","end_time":118,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"东左"},{"state":"1","start_time":"118","duration":"82","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"南直"},{"state":"1","start_time":"118","duration":"54","end_time":172,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"南左"},{"state":"1","start_time":"50","duration":"53","end_time":103,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"西右"}]},"traceid":"546b8cd7777a4b27a3d28046abf2bb35","username":"unknown","time":{"a":"0.0868秒","s":"0.0837秒"}}';
        exit;
    }
}
