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
    [
        'method' => 'POST',
        'url' => 'Overviewtoplist/stopDelayTopList',    //停车时间top列表
        'params' => [
            'city_id' => 0,
            'pagesize' => 20,
        ],
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
    [
        'method' => 'POST',
        'url' => 'Overview/operationCondition',
        'params' => [
            'city_id' => 0,
        ],
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
        'checker' => function($result){
            global $junctionSurveyAlarmTotal;
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
            return true;
        },
    ],
    [
        'method' => 'POST',
        'url' => 'Overviewalarm/todayAlarmInfo',
        'params' => [
            'city_id' => 0,
        ],
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
        'url' => 'Overviewalarm/sevenDaysAlarmChange',
        'params' => [
            'city_id' => 0,
        ],
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

$config['webhook'] = 'https://oapi.dingtalk.com/robot/send?access_token=8d7a45fd3a5a4b7758c55f790fd85aef10fb43130be60d2797a3fd6ee80f9403';
$config['app_id'] = '1001';
$config['secret'] = 'e9b0a9042d1840dcdb9b9c7095391949';
$config['basedir'] = '/home/xiaoju/webroot/cache/itstool/';


