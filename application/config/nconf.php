<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|-----------------------------------------------------
| 对外API配置文件
|-----------------------------------------------------
*/
// 线上机器
$online_host = array(
    'ipd-cloud-web00.gz01',
    'ipd-cloud-web01.gz01',
    'ipd-cloud-preweb00.gz01',
);

$hostname = gethostname();
$development = 1; //开发环境
if (in_array($hostname, $online_host)) {
    $development = 2;
}

if($development == 2){
	// 路网接口服务器地址
	$waymap_server = '100.69.238.11';
	// 路网接口服务器端口
	$waymap_port = '8000';
	// 路网接口前缀
	$waymap_ext = '/its';

	// 配时接口服务器地址
	$timing_server = '100.69.238.11';
	// 配时接口服务器端口
	$timing_port = '8000';
	// 配时接口前缀
	$timing_ext = '/its';

	$config['redis'] = [
		'host' => '100.69.239.57',
		'port' => '3060'
	];
}else{
	// 路网接口服务器地址
	$waymap_server = '100.90.164.31';
	// 路网接口服务器端口
	$waymap_port = '8001';
	// 路网接口前缀
	$waymap_ext = '';

	// 配时接口服务器地址
	$timing_server = '100.90.164.31';
	// 配时接口服务器端口
	$timing_port = '8006';
	// 配时接口前缀
	$timing_ext = '';

	$config['redis'] = [
		'host' => '127.0.0.1',
		'port' => '6379'
	];
}

$temp_waymap_port = !empty($waymap_port) ? ":" . $waymap_port : "";
$temp_timing_port = !empty($timing_port) ? ":" . $timing_port : "";
// 路网接口地址
$config['waymap_interface'] = 'http://' . $waymap_server . $temp_waymap_port . $waymap_ext;
// 路网接口token
$config['waymap_token'] = '4c3e3b6a3588161128d0604daab528db';
$config['waymap_userid'] = 'signalPro';

// 配时接口地址
$config['timing_interface'] = 'http://' . $timing_server . $temp_timing_port . $timing_ext;

// 评估置信度阈值
$confidence_threshold = 0.5;

// 评估置信度
$config['confidence'] = [
	0=>[
		'name'      => '全部',
		'sql_where' => function($val) { return $val . ' > 0';},
		'formula'   => function($val) { return $val > 0;},
	],
	1=>[
		'name'      => '高',
		'sql_where' => function($val) use($confidence_threshold) { return $val . ' > ' . $confidence_threshold;},
		'formula'   => function($val) use($confidence_threshold) { return $val > $confidence_threshold;},
	],
	2=>[
		'name'      => '低',
		'sql_where' => function($val) use($confidence_threshold) { return $val . ' < ' . $confidence_threshold;},
		'formula'   => function($val) use($confidence_threshold) { return $val < $confidence_threshold;},
	],
	3=>[
		'name'      => '中',
		'sql_where' => function($val) { return $val . ' > 0';},
		'formula'   => function($val) { return $val > 0;},
	]
];

// 路口指标
$config['junction_quota_key'] = [
	'imbalance_index' => [
		'name'		 => '失衡指数',
		'status_max' => 0.6,
		'status_min' => 0.3,
		'round'      => function($val) { return round($val, 2);},
		'unit'       => ''
	],
	'spillover_index' => [
		'name'       => '溢流指数',
		'status_max' => 0.08,
		'status_min' => 0.04,
		'round'      => function($val) { return round($val, 2);},
		'unit'       => ''
	],
	'incoordination_index' => [
		'name'       => '失调指数',
		'status_max' => 0.7,
		'status_min' => 0.4,
		'round'      => function($val) { return round($val, 2);},
		'unit'       => ''
	],
	'saturation_index' => [
		'name'       => '饱和指数',
		'status_max' => 0.9,
		'status_min' => 0.3,
		'round'      => function($val) { return round($val, 2);},
		'unit'       => ''
	],
	'stop_cycle_time' => [
		'name'       => '停车次数',
		'status_max' => 2,
		'status_min' => 1,
		'round'      => function($val) { return round($val, 2);},
		'unit'       => ''
	],
	'stop_delay' => [
		'name'       => '平均延误',
		'status_max' => 40,
		'status_min' => 20,
		'round'      => function($val) { return round($val, 2);},
		'unit'       => 's'
	],
	'avg_speed' => [
		'name'       => '平均速度',
		'status_max' => 40,
		'status_min' => 20,
		'round'      => function($val) { return round($val, 2);},
		'unit'       => 'km/h'
	]
];

// flow指标
$config['flow_quota_key'] = [
	'route_length' => [
		'name'      => '路段长度', // 中文名称
		'round'     => function($val) { return round($val);},  // 格式化数据
		'unit'      => 'm'       // 单位
	],
	'queue_position' => [
		'name'      =>'排队长度',
		'round'     => function($val) { return round($val);},
		'unit'      => 'm'
	],
	'saturation_degree'	=> [
		'name'      =>'饱和度',
		'round'     => function($val) { return round($val, 2);},
		'unit'      => ''
	],
	'stop_delay' => [
		'name'      => '停车延误',
		'round'     => function($val) { return round($val, 2);},
		'unit'      => 's'
	],
	'stop_time_cycle' => [
		'name'      =>'停车次数',
		'round'     => function($val) { return round($val, 2);},
		'unit'      => ''
	],
	'spillover_rate' => [
		'name'      => '溢流比率',
		'round'     => function($val) { return round($val, 5);},
		'unit'      => ''
	],
	'stop_rate' => [
		'name'      => '停车比率',
		'round'     => function($val) { return round($val, 2);},
		'unit'      => ''
	],
	'flow_num' => [
		'name'      => '流量',
		'round'     => function($val) { return round($val);},
		'unit'      => 'veh/h/lane'
	],
	'free_flow_speed' => [
		'name'      => '自由流',
		'round'     => function($val) { return round($val, 2);},
		'unit'      => 'km/h'
	],
];

// 诊断问题
$config['diagnose_key']	= [
	'spillover_index'	=> [
		'name'				        => '溢流',
		'junction_diagnose_formula' => function($val) { return $val > 0.008;},
		'sql_where' => function() { return '`spillover_index` > 0.008';},
		'nature_formula'  => function($val) {
			if ($val > 0.6) {
				return 1;
			} else if ($val > 0.3 && $val <= 0.6) {
				return 2;
			} else if ($val > 0 && $val <= 0.3){
				return 3;
			} else {
				return 0;
			}
		},
		'flow_quota'=>[
			'spillover_rate'=>[
				'name'=>'溢流比率',
				'unit'=>''
			],
			'queue_position'=>[
				'name'=>'排队长度',
				'unit'=>'m'
			],
			'stop_delay'=>[
				'name'=>'停车延误',
				'unit'=>'s'
			],
			'stop_time_cycle'=>[
				'name'=>'停车次数',
				'unit'=>''
			],
			'route_length'  => [
				'name'=>'路段长度',
				'unit'=>'m'
			],
		],
		// flow级的诊断问题
		'flow_diagnose' => [
			'spillover_rate' => [
				[
					'threshold' => 0.008,
					'formula'   => '>'
				],
			]
		],
	],
	'imbalance_index'=>[
		'name'				        => '失衡',
		'junction_diagnose_formula' => function($val) { return $val > 0;},
		'sql_where' => function() { return '`imbalance_index` > 0';},
		'nature_formula'  => function($val) {
			if ($val > 0.08) {
				return 1;
			} else if ($val > 0.04 && $val <= 0.08) {
				return 2;
			} else if ($val > 0.005 && $val <= 0.04) {
				return 3;
			} else {
				return 0;
			}
		},
		'flow_quota'=>[
			'saturation_degree'=>[
				'name'=>'饱和度',
				'unit'=>''
			],
			'queue_position'=>[
				'name'=>'排队长度',
				'unit'=>'m'
			],
			'stop_delay'=>[
				'name'=>'停车延误',
				'unit'=>'s'
			],
			'stop_time_cycle'=>[
				'name'=>'停车次数',
				'unit'=>'s'
			],
			'route_length'  => [
				'name'=>'路段长度',
				'unit'=>'m'
			]
		],
		// flow级的诊断问题 如果有两个则是or的关系
		'flow_diagnose' => [
			'saturation_degree' => [
				[
					'threshold' => 0,
					'formula'   => '>='
				],
				[
					'threshold' => 2,
					'formula'   => '<'
				]
			]
		],
	],
	'saturation_index'	=> [
		'name'				        => '空放',
		'junction_diagnose_formula' => function($val) { return $val < 0.3;},
		'sql_where' => function() { return '`saturation_index` < 0.3';},
		'nature_formula'  => function($val) {
			if ($val < 0.1) {
				return 1;
			} else if ($val >= 0.1 && $val < 0.2) {
				return 2;
			} else if ($val >= 0.2 && $val < 0.3) {
				return 3;
			} else {
				return 0;
			}
		},
		'flow_quota'=>[
			'saturation_degree'=>[
				'name'=>'饱和度',
				'unit'=>''
			],
			'stop_delay'=>[
				'name'=>'停车延误',
				'unit'=>'s'
			],
			'stop_time_cycle'=>[
				'name'=>'停车次数',
				'unit'=>''
			]
		],
		// flow级的诊断问题
		'flow_diagnose' => [
			'saturation_degree' => [
				[
					'threshold' => 0.3,
					'formula'   => '<'
				],
			]
		],
	],
	'over_saturation'	=> [
		'name'				        => '过饱和',
		'junction_diagnose_formula' => function($val) { return $val > 0.9;},
		'sql_where' => function() { return '`saturation_index` > 1';},
		'nature_formula'  => function($val) {
			if ($val < 1.2) {
				return 1;
			} else if ($val > 1 && $val <= 1.2) {
				return 2;
			} else if ($val > 0.9 && $val < 1) {
				return 3;
			} else {
				return 0;
			}
		},
		'flow_quota'=>[
			'saturation_degree'=>[
				'name'=>'饱和度',
				'unit'=>''
			],
			'stop_delay'=>[
				'name'=>'停车延误',
				'unit'=>'s'
			],
			'stop_time_cycle'=>[
				'name'=>'停车次数',
				'unit'=>''
			],
			'queue_position'=>[
				'name'=>'排队长队',
				'unit'=>'m'
			]
		],
		// flow级的诊断问题
		'flow_diagnose' => [
			'saturation_degree' => [
				[
					'threshold' => 0.3,
					'formula'   => '<'
				],
			]
		],
	]
];


// 诊断置信度阈值
$config['diagnose_confidence_threshold'] = 0.5;

// 排序
$config['sort_conf'] = [
	1	=> 'asc',
	2	=> 'desc'
];

// result_comment配置
$config['result_comment'] = [
	'signal_mes_error' => '配时信息与实际车流不符'
];

// 绿信比优化建议
$config['split_opt_suggest'] = [
	'over_saturation_flow' => '相位:movement过饱和，建议增加绿灯时长',
	'green_loss_flow'      => '相位:movement存在空放，可缩短绿灯时长',
];

// 反推配时名单

$config['back_timing_roll'] = [
	'unknown',
	'algorithm_group_sts_p',
	'lixiaolx_v', 'duzelong', 'wangzhimingjay','tanchenxi', 'tjlifuliang_i',
	'hanyuhan','umzhaoyan_i','yangjianhao','wangxingmin_i','larrymeiyu_i','mengyuan','wangzihao_i','zhengjianfeng','tianshanshan','wangshengguang',
];


// 定义tracelog的action
$config['action_log_map'] = [
    'adapt_area_switch_edit' => '自适应区域配时开关修改',
];