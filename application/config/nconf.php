<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|-----------------------------------------------------
| 对外API配置文件
|-----------------------------------------------------
*/

// 任务接口服务器地址
$task_server = '';
// 任务接口服务器端口号
$task_port = '';
// 任务接口地址
$config['task_interface'] = 'http://' . $task_server . ':' . $task_port;

$config['task_type'] = [0=>'周期任务', 1=>'自定义任务'];

// 路网接口服务器地址
$waymap_server = '100.90.164.31';
// 路网接口服务器端口
$waymap_port = '8001';
// 路网接口地址
$config['waymap_interface'] = 'http://' . $waymap_server . ":" . $waymap_port;
// 路网接口token
$config['waymap_token'] = '4c3e3b6a3588161128d0604daab528db';

// 置信度
$config['confidence'] = [
							1=>[
								'name'		=> '高',
								'expression'=> '>=50'
							],
							2=>[
								'name'		=> '低',
								'expression'=> '<50'
							]
						];

// 路口指标
$config['junction_quota_key'] = [
									'imbalance_index'		=> '失衡指数',
									'spillover_index'		=> '溢流指数',
									'incoordination_index'	=> '失调指数',
									'saturation_index'		=> '饱和指数'
								];

// flow指标
$config['flow_quota_key'] = [
								'stop_delay'		=> '停车延误',
								'saturation_degree'	=> '饱和度',
								'queue_position'	=> '排队长度',
								'stop_time_cycle'	=> '停车周期次数'
							];



