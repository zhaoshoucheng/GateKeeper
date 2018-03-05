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

