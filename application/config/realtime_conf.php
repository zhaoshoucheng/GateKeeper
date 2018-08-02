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
        'name'      => '排队长队',
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
        'name'    => '畅通',    // 状态名
        'key'     => 1,        // 状态KEY
        'en_key'  => 'open', // 英文KEY
        'formula' => function ($val) { return $val < 90;}, // 计算规则
    ],
    // 缓行：停车延误 > 阈值 && 停车延误 <= 阈值
    2 => [
        'name'    => '缓行',
        'key'     => 2,
        'en_key'  => 'amble',
        'formula' => function ($val) { return ($val >= 90 && $val < 180);},
    ],
    // 拥堵：停车延误 > 阈值
    3 => [
        'name'    => '拥堵',
        'key'     => 3,
        'en_key'  => 'congestion',
        'formula' => function ($val) { return $val >= 180;},
    ],
];

// 报警类别
$config['alarm_category'] = [
    1 => [
        'name'    => '溢流', // 类别名称
        'key'     => 1,       // 类别KEY
        'desc'    => '',      // 描述
        'formula' => function($val) { return $val >= 0.2;}, // 判断规则
    ],
    2 => [
        'name'    => '过饱和',
        'key'     => 2,
        'desc'    => '',
        'formula' => function($val) { // $val = ['twice_stop_rate'=>xx, 'queue_length'=>xx, ...]
            return ($val['twice_stop_rate'] >= 0.02
                && $val['queue_length'] >= 180
                && $val['stop_delay'] >= 50);
        },
    ],
];
