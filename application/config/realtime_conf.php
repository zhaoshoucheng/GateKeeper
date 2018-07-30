<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// 指标
$config['real_time_quota'] = [
    'stop_time_cycle' => [
        'name'      => '停车次数',
        'unit'      => '',
        'round_num' => 0,
    ],
    'spillover_rate' => [
        'name'      => '溢流指标',
        'unit'      => '',
        'round_num' => 0,
    ],
    'queue_length' => [
        'name'      => '排队长队',
        'unit'      => '',
        'round_num' => 0,
    ],
    'stop_delay' => [
        'name'      => '停车延误',
        'unit'      => '',
        'round_num' => 0,
    ],
    'stop_rate' => [
        'name'      => '失调指标',
        'unit'      => '',
        'round_num' => 0,
    ],
    'twice_stop_rate' => [
        'name'      => '二次停车比例',
        'unit'      => '',
        'round_num' => 0,
    ],
    'speed' => [
        'name'      => '速度',
        'unit'      => '',
        'round_num' => 0,
    ],
    'free_flow_speed' => [
        'name'      => '行驶速度',
        'unit'      => '',
        'round_num' => 0,
    ],
];

// 路口状态
$config['junction_status'] = [
    12 => [
        1 => [
            'name' => '畅通',
            'key' => 1,
            'formula' => function ($val) { return $val <= 90;},
        ],
        2 => [
            'name' => '缓行',
            'key' => 2,
            'formula' => function ($val) { return ($val > 90 && $val <= 180);},
        ],
        3 => [
            'name' => '拥堵',
            'key' => 3,
            'formula' => function ($val) { return $val > 180;},
        ],
        4 => [
            'name' => '报警',
            'key' => 4,
            'formula' => function ($val, $key) {
                switch ($key) {
                    case 'spillover_rate':
                        return $val >= 0.2;
                        break;
                    case 'twice_stop_rate':
                        return $val >= 0.2;
                        break;
                    case 'queue_length':
                        return $val >= 180;
                        break;
                    case 'stop_delay':
                        return $val >= 50;
                        break;
                    default:
                        return false;
                        break;
                }
            },
        ],
    ],
];
