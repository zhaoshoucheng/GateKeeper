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

    pubic function stopDelayCurve(){
        echo '{
    "errno": 0,
    "errmsg": "",
    "data": {
        "dataList": [   // 数据集合
            {
                "time": "07:20",                  // 开始时间
                "logic_junction_id": "xxxxxxxx",  // 路口ID
                "junction_name": "yyyyyyy",       // 路口名称
                "stop_delay":120,                 // 指标值
                "quota_unit":''                   // 指标单位
            },
            {
                "time": "07:20",
                "logic_junction_id": "xxxxxxxx",
                "junction_name": "yyyyyyy",
                "stop_delay":120,
                "quota_unit":''                  
            }
            ......
       ]
    },
    "username": "unknown"
}';
    exit;
    }


    pubic function stopDelayCurve(){
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
        echo '{"errno":0,"errmsg":"","data":{"total_plan":9,"plan_list":[{"id":"62640","start_time":"00:00:00","end_time":"06:00:00"},{"id":"62642","start_time":"06:00:00","end_time":"07:00:00"},{"id":"62636","start_time":"07:00:00","end_time":"09:45:00"},{"id":"62637","start_time":"09:45:00","end_time":"12:15:00"},{"id":"62638","start_time":"12:15:00","end_time":"16:30:00"},{"id":"62639","start_time":"16:30:00","end_time":"19:15:00"},{"id":"62644","start_time":"19:15:00","end_time":"20:30:00"},{"id":"62643","start_time":"20:30:00","end_time":"23:00:00"},{"id":"62641","start_time":"23:00:00","end_time":"24:00:00"}],"timing_detail":{"62636":{"cycle":"200","offset":"161","timing":[{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"0","duration":"35","end_time":35,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"35","duration":"83","end_time":118,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"35","duration":"83","end_time":118,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"118","duration":"82","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"118","duration":"54","end_time":172,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"50","duration":"53","end_time":103,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]},"62637":{"cycle":"200","offset":"66","timing":[{"state":"1","start_time":"170","duration":"30","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"170","duration":"30","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"0","duration":"42","end_time":42,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"42","duration":"79","end_time":121,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"121","duration":"49","end_time":170,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"121","duration":"79","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"42","duration":"79","end_time":121,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"59","duration":"36","end_time":95,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]},"62638":{"cycle":"200","offset":"25","timing":[{"state":"1","start_time":"174","duration":"26","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"0","duration":"28","end_time":28,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"174","duration":"26","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"28","duration":"97","end_time":125,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"28","duration":"97","end_time":125,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"125","duration":"75","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"125","duration":"49","end_time":174,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"51","duration":"48","end_time":99,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]},"62639":{"cycle":"200","offset":"195","timing":[{"state":"1","start_time":"174","duration":"26","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"174","duration":"26","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"0","duration":"38","end_time":38,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"38","duration":"92","end_time":130,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"38","duration":"92","end_time":130,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"130","duration":"70","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"130","duration":"44","end_time":174,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"65","duration":"40","end_time":105,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]},"62640":{"cycle":"70","offset":"11","timing":[{"state":"1","start_time":"40","duration":"30","end_time":70,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"40","duration":"30","end_time":70,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"0","duration":"40","end_time":40,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"0","duration":"40","end_time":40,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"0","duration":"40","end_time":40,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"40","duration":"30","end_time":70,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"40","duration":"30","end_time":70,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"0","duration":"40","end_time":40,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]},"62641":{"cycle":"70","offset":"11","timing":[{"state":"1","start_time":"40","duration":"30","end_time":70,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"40","duration":"30","end_time":70,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"0","duration":"40","end_time":40,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"0","duration":"40","end_time":40,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"0","duration":"40","end_time":40,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"40","duration":"30","end_time":70,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"40","duration":"30","end_time":70,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"0","duration":"40","end_time":40,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]},"62642":{"cycle":"200","offset":"179","timing":[{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"0","duration":"32","end_time":32,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"32","duration":"89","end_time":121,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"32","duration":"89","end_time":121,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"121","duration":"79","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"121","duration":"51","end_time":172,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"45","duration":"56","end_time":101,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]},"62643":{"cycle":"200","offset":"179","timing":[{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"172","duration":"28","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"0","duration":"32","end_time":32,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"32","duration":"89","end_time":121,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"32","duration":"89","end_time":121,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"121","duration":"79","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"121","duration":"51","end_time":172,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"45","duration":"56","end_time":101,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]},"62644":{"cycle":"200","offset":"153","timing":[{"state":"1","start_time":"174","duration":"26","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_74541670","comment":"\u5317\u5de6"},{"state":"1","start_time":"174","duration":"26","end_time":200,"logic_flow_id":"2017030116_i_603227360_2017030116_o_877943190","comment":"\u5317\u76f4"},{"state":"1","start_time":"0","duration":"38","end_time":38,"logic_flow_id":"2017030116_i_74064960_2017030116_o_603229990","comment":"\u897f\u5de6"},{"state":"1","start_time":"38","duration":"92","end_time":130,"logic_flow_id":"2017030116_i_74541671_2017030116_o_73821520","comment":"\u4e1c\u76f4"},{"state":"1","start_time":"38","duration":"92","end_time":130,"logic_flow_id":"2017030116_i_74541671_2017030116_o_877943190","comment":"\u4e1c\u5de6"},{"state":"1","start_time":"130","duration":"70","end_time":200,"logic_flow_id":"2017030116_i_877943201_2017030116_o_603229990","comment":"\u5357\u76f4"},{"state":"1","start_time":"130","duration":"44","end_time":174,"logic_flow_id":"2017030116_i_877943201_2017030116_o_73821520","comment":"\u5357\u5de6"},{"state":"1","start_time":"55","duration":"60","end_time":115,"logic_flow_id":"2017030116_i_74064960_2017030116_o_877943190","comment":"\u897f\u53f3"}]}}},"traceid":"7116e4ef887c4fdd978a193076496a77","username":"unknown","time":{"a":"0.0986\u79d2","s":"0.0954\u79d2"}}';
        exit;
    }
}
