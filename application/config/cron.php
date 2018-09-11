<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['city_ids'] = [1, 3, 12, 18, 23, 38, 85, 134];
$config['checkItems'] = [
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overview/getNowDate',
		'params' => [
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overview/junctionsList',
		'params' => [
			'city_id' => 0,
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overviewalarm/sevenDaysAlarmChange',
		'params' => [
			'city_id' => 0,
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overviewalarm/todayAlarmInfo',
		'params' => [
			'city_id' => 0,
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overview/junctionSurvey',
		'params' => [
			'city_id' => 0,
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overviewalarm/realTimeAlarmList',
		'params' => [
			'city_id' => 0,
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overview/getCongestionInfo',
		'params' => [
			'city_id' => 0,
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overview/operationCondition',
		'params' => [
			'city_id' => 0,
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overviewtoplist/stopDelayTopList',
		'params' => [
			'city_id' => 0,
			'pagesize' => 20,
		],
		'checker' => '',
	],
	[
		'method' => 'POST',
		'url' => 'https://sts.didichuxing.com/signalpro/api/Overviewtoplist/stopTimeCycleTopList',
		'params' => [
			'city_id' => 0,
			'pagesize' => 20,
		],
		'checker' => '',
	],
];

$config['webhook'] = 'https://oapi.dingtalk.com/robot/send?access_token=8d7a45fd3a5a4b7758c55f790fd85aef10fb43130be60d2797a3fd6ee80f9403';
$config['app_id'] = '1001';
$config['appkey'] = 'abfeb5d614beaed21c306b915a1ca1de';
$config['basedir'] = '/home/xiaoju/webroot/cache/itstool/';

