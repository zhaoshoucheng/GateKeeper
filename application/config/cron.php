<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['city_ids'] = [1, 12];
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
			'city_id' => 12,
		],
		'checker' => '',
	],
];

$config['webHook'] = 'https://oapi.dingtalk.com/robot/send?access_token=8d7a45fd3a5a4b7758c55f790fd85aef10fb43130be60d2797a3fd6ee80f9403';
$config['token'] = 'aedadf3e3795b933db2883bd02f31e1d';

