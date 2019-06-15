<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['city_ids'] = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,26,27,28,29,30,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,104,105,106,107,108,109,110,111,112,113,114,116,117,118,119,120,121,122,123,124,125,125,126,126,127,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,190,191,191,192,192,193,193,194,194,195,196,197,198,199,200,201,202,203,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,230,231,232,233,234,235,236,237,238,239,240,241,242,243,244,245,246,247,248,249,250,251,252,253,254,255,256,257,258,259,260,261,262,263,264,265,266,267,268,269,270,271,272,273,274,275,276,277,278,279,280,281,282,283,284,285,286,287,288,289,290,291,292,293,294,295,296,297,298,299,300,301,302,303,304,305,306,307,308,309,310,311,312,313,314,315,316,317,322,323,324,325,326,327,328,329,330,331,332,333,334,335,336,337,338,339,340,341,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356,357,360,361,362,363,364,365];

$online_host = array(
    'ipd-cloud-web00.gz01',
    'ipd-cloud-web01.gz01',
);
if (in_array(gethostname(), $online_host)) {
    $config['base_url'] = 'http://127.0.0.1:8000/signalpro/api';
}else{
    $config['base_url'] = 'http://100.90.165.32:8088/signalpro/api';
}

// $config['base_url'] = 'http://100.90.164.31:8100/itstool/';
$config['open_file'] = 'open.json';
$config['checkItems'] = [
    [
        'method' => 'POST',
        'url' => 'Overviewalarm/realTimeAlarmList', //实时报警列表
        'params' => [
            'city_id' => 0,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data.dataList isset',
        'checker' => function($result){
            global $realTimeAlarmListCount;
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            $realTimeAlarmListCount = 0;
            if(isset($ret['data']['dataList'])){
                $realTimeAlarmListCount = count($ret['data']['dataList']);
            }
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overviewtoplist/stopTimeCycleTopList',    //停车次数top列表
        'params' => [
            'city_id' => 0,
            'pagesize' => 20,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data isset',
        'checker' => function($result){
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            //避免误报
            if(intval(date("H"))>=6){
                if(!isset($ret['data']) || count($ret['data'])==0){
                    return false;
                }
            }
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overviewtoplist/stopDelayTopList',    //停车时间top列表
        'params' => [
            'city_id' => 0,
            'pagesize' => 20,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data isset',
        'checker' => function($result){
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            if(intval(date("H"))>=6){
                if(!isset($ret['data']) || count($ret['data'])==0){
                    return false;
                }
            }
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overview/operationCondition',
        'params' => [
            'city_id' => 0,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data.dataList isset,count($.data.dataList)>0 when date("H")>0',
        'checker' => function($result){
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            if(!isset($ret['data']['dataList'])){
                return false;
            }
            if(date('H')>0 && count($ret['data']['dataList'])==0){
                return false;
            }
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overview/getCongestionInfo',
        'params' => [
            'city_id' => 0,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data.count isset and count($.data.count)>0',
        'checker' => function($result){
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            if(!isset($ret['data']['count']) || count($ret['data']['count'])==0){
                return false;
            }
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overview/junctionSurvey',
        'params' => [
            'city_id' => 0,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data.junction_total isset and count($.data.junction_total)>0,$.data.alarm_total isset',
        'checker' => function($result){
            global $junctionSurveyAlarmTotal;
            global $junctionTotal;
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            if(!isset($ret['data']['junction_total']) || $ret['data']['junction_total']==0){
                return false;
            }


            if(!isset($ret['data']['alarm_total'])){
                return false;
            }
            $junctionSurveyAlarmTotal = $ret['data']['alarm_total'];
            $junctionTotal = $ret['data']['junction_total'];
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overviewalarm/todayAlarmInfo',
        'params' => [
            'city_id' => 0,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data isset',
        'checker' => function($result){
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            if(!isset($ret['data'])){
                return false;
            }
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overviewalarm/sevenDaysAlarmChange',
        'params' => [
            'city_id' => 0,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data.dataList isset and count($.data.dataList)>0',
        'checker' => function($result){
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            if(!isset($ret['data']['dataList']) || count($ret['data']['dataList'])==0){
                return false;
            }
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overview/junctionsList',
        'params' => [
            'city_id' => 0,
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data.center.lat isset and $.data.center.lng isset,$.data.dataList isset and count($.data.dataList)>0',
        'checker' => function($result){
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            if(!isset($ret['data']['center']['lat']) || !isset($ret['data']['center']['lng'])){
                return false;
            }
            if(!isset($ret['data']['dataList']) || count($ret['data']['dataList'])==0){
                return false;
            }
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Feedback/getTypes',
        'params' => [
        ],
        'checkerInfo' => '$.errno isset and gt 0,$.data isset',
        'checker' => function($result){
            $ret = json_decode($result,true);
            if(!isset($ret['errno']) || $ret['errno']!=0){
                return false;
            }
            if(!isset($ret['data']) || count($ret['data'])==0){
                return false;
            }
            return true;
        },
    ],
];

$config['webhook'] = 'https://oapi.dingtalk.com/robot/send?access_token=9123576d4bb80d3a963c5addd4e4f5a3152d85a62484b14a50efb777e8c68f78';
$config['app_id'] = '1001';
$config['secret'] = 'e9b0a9042d1840dcdb9b9c7095391949';
$config['basedir'] = '/home/xiaoju/webroot/cache/itstool/';


