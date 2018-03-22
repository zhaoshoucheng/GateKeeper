<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|-----------------------------------------------------
| 对外API配置文件
|-----------------------------------------------------
*/

// 路网接口服务器地址
$waymap_server = '100.90.164.31';
// 路网接口服务器端口
$waymap_port = '8001';
// 路网接口地址
$config['waymap_interface'] = 'http://' . $waymap_server . ":" . $waymap_port;
// 路网接口token
$config['waymap_token'] = '4c3e3b6a3588161128d0604daab528db';

// 配时接口服务器地址
$timing_server = '100.90.164.31';
// 配时接口服务器端口
$timing_port = '8006';
// 配时接口地址
$config['timing_interface'] = 'http://' . $timing_server . ":" . $timing_port;

// 评估置信度阈值
$confidence_threshold = 0.5;

// 评估置信度
$config['confidence'] = [
	1=>[
		'name'		=> '高',
		'expression'=> '>=' . $confidence_threshold
	],
	2=>[
		'name'		=> '低',
		'expression'=> '<' . $confidence_threshold
	]
];

// 路口指标
$config['junction_quota_key'] = [
	'imbalance_index' => [
		'name'		=>'失衡指数',
		'status_max'=>'0.6',
		'status_min'=>'0.1'
	],
	'spillover_index' => [
		'name'=>'溢流指数',
		'status_max'=>'0.6',
		'status_min'=>'0.1'
	],
	'incoordination_index' => [
		'name'=>'失调指数',
		'status_max'=>'0.6',
		'status_min'=>'0.1'
	],
	'saturation_index' => [
		'name'=>'饱和指数',
		'status_max'=>'0.6',
		'status_min'=>'0.1'
	],
	'stop_cycle_time' => [
		'name'=>'停车周期次数',
		'status_max'=>'0.6',
		'status_min'=>'0.1'
	],
	'stop_delay' => [
		'name'=>'停车延误',
		'status_max'=>'0.6',
		'status_min'=>'0.1'
	]
];

// flow指标
$config['flow_quota_key'] = [
	'stop_delay'		=> '停车延误',
	'saturation_degree'	=> '饱和度',
	'queue_position'	=> '排队长度',
	'stop_time_cycle'	=> '停车周期次数',
	'spillover_rate'	=> '溢流比率',
	'stop_rate'			=> '停车比率'
];

// 诊断问题
$config['diagnose_key']	= [
	'imbalance_index'=>[
		'name'				=>'失衡',
		'junction_threshold'=>0.5,
		'flow_quota'=>[
			'stop_time_cycle'=>[
				'name'=>'停车周期次数',
				'threshold'=>0.5
			],
			'queue_position'=>[
				'name'=>'排队长度',
				'threshold'=>0.5
			],
			'stop_delay'=>[
				'name'=>'停车延误',
				'threshold'=>0.5
			]
		]
	],
	'spillover_index'	=> [
		'name'				=>'溢流',
		'junction_threshold'=>0.5,
		'flow_quota'=>[
			'queue_position'=>[
				'name'=>'排队长度',
				'threshold'=>0.5
			],
			'stop_time_cycle'=>[
				'name'=>'停车周期次数',
				'threshold'=>0.5
			],
			'stop_delay'=>[
				'name'=>'停车延误',
				'threshold'=>0.5
			],
			'spillover_rate'=>[
				'name'=>'溢流比率',
				'threshold'=>0.5
			]
		]
	],
	'saturation_index'	=> [
		'name'				=>'空放',
		'junction_threshold'=>0.5,
		'flow_quota'=>[
			'stop_delay'=>[
				'name'=>'停车延误',
				'threshold'=>0.5
			],
			'stop_time_cycle'=>[
				'name'=>'停车周期次数',
				'threshold'=>0.5
			],
			'saturation_degree'=>[
				'name'=>'饱和度',
				'threshold'=>0.5
			],
		]
	]
];


// 诊断置信度阈值
$config['diagnose_confidence_threshold'] = 0.5;

// 排序
$config['sort_conf'] = [
	1	=> 'asc',
	2	=> 'desc'
];



