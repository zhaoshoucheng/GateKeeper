<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['city_ids'] = [1, 3, 12, 18, 23, 38, 85, 134];

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


