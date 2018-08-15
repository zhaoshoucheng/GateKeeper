<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// 指标
$config['real_time_quota'] = [
    'stop_time_cycle' => [
        'name'      => '停车次数', // 指标名称
        'unit'      => '次',        // 指标单位
        'round'     => function($val) { return round($val, 2);}, // 取指标几位小数 用于返给前端时round()
    ],
    'spillover_rate' => [
        'name'      => '溢流指标',
        'unit'      => '',
        'round'     => function($val) { return round($val, 5);},
    ],
    'queue_length' => [
        'name'      => '排队长度',
        'unit'      => '米',
        'round'     => function($val) { return round($val);},
    ],
    'stop_delay' => [
        'name'      => '停车延误',
        'unit'      => '秒',
        'round'     => function($val) { return round($val, 2);},
    ],
    'stop_rate' => [
        'name'      => '失调指标',
        'unit'      => '',
        'round'     => function($val) { return round($val, 4);},
    ],
];

// 路口状态
$config['junction_status'] = [
    // 畅通：停车延误 <= 阈值
    1 => [
        'name' => '畅通',    // 状态名
        'key'  => 1,        // 状态KEY
    ],
    // 缓行：停车延误 > 阈值 && 停车延误 <= 阈值
    2 => [
        'name' => '缓行',
        'key'  => 2,
    ],
    // 拥堵：停车延误 > 阈值
    3 => [
        'name' => '拥堵',
        'key'  => 3,
    ],
];

// 路口状态计算规则
$config['junction_status_formula'] = function($val) {
    if ($val >= 50) {
        return 3; // 拥堵
    } else if ($val < 50 && $val >= 40) {
        return 2; // 缓行
    } else {
        return 1; // 畅通
    }
};

// 报警类别
$config['alarm_category'] = [
    1 => [
        'name'    => '溢流', // 类别名称
        'key'     => 1,       // 类别KEY
        'desc'    => '',      // 描述
    ],
    2 => [
        'name'    => '过饱和',
        'key'     => 2,
        'desc'    => '',
    ],
];

// 报警计算规则 $val = ['指标KEY' => 指标值]
$config['alarm_formula'] = function($val) {
    $res = [];
    if (array_key_exists('spillover_rate', $val)
        && $val['spillover_rate'] >= 0.2
        && $val['traj_count'] >= 10
        && $val['stop_delay'] >= 40)
    {
        array_push($res, 1);
    }

    if ((array_key_exists('twice_stop_rate', $val) && $val['twice_stop_rate'] >= 0.2)
        && (array_key_exists('queue_length', $val) && $val['queue_length'] >= 180)
        && (array_key_exists('stop_delay', $val) && $val['stop_delay'] >= 50)
        && $val['traj_count'] >= 10)
    {
        array_push($res, 2);
    }

    return $res;
};

// 指标评估数据redis KEY前缀
$config['quota_evaluate_key_prefix'] = 'quotaEvaluateDataKey_';
