<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['quotas'] = [
    'stop_delay' => [
        'name'      => '停车延误',
        'title'     => '各方向延误时间分析',
        'unit'      => '秒',
        'formula'   => 'max',
        'describe'  => function($a) {
            return $a[0] . '路口在基准日期内' . $a[1] . '方向延误时间最高，持续时间为' .$a[2] . '，评估日期内' . $a[3] . '方向延误时间最高，持续时间为' . $a[4] . '，同时' . $a[5] . '方向在' . $a[6] . '时刻平均延误时间由' . $a[7] . '减至' . $a[8] . '。';
        },
        'round'     => function($val){ return round($val, 2);},
        'desc'      => '平均各个日期各方向最大延误在所在时段中随时间变化规则',
    ],
    'stop_time_cycle' => [
        'name'      => '停车次数', // 指标名称
        'title'     => '各方向最大停车次数分析',
        'unit'      => '次',
        'formula'   => 'max',
        'describe'  => function($a) {
            return $a[0] . '路口在基准日期内' . $a[1] . '方向停车次数最多，持续时间为' . $a[2] . '，评估日期内' . $a[3] . '方向停车次数最多，持续时间为' . $a[4] . '，同时' . $a[5] . '方向在' . $a[6] . '时刻平均停车次数由' . $a[7] . '减至' . $a[8] . '。';
        },
        'round'     => function($val){ return round($val, 2);},
        'desc'      => '平均对各方向最大停车次数在所在时段内的变化情况进行展示并分析'
    ],
    'queue_length' => [
        'name'      => '排队长度',
        'title'     => '各方向最大排队长度分析',
        'unit'      => '米',
        'formula'   => 'max',
        'describe'  => function($a) {
            return $a[0] . '路口在基准日期内' . $a[1] . '方向排队长度最长，持续时间为' . $a[2] . '，评估日期内' . $a[3] . '方向最大排队长度最长，持续时间为' . $a[4] . '，同时' . $a[5] . '方向在' . $a[6] . '时刻平均最大排队长度由' . $a[7] . '减至' . $a[8] . '。';
        },
        'round'     => function($val){ return round($val);},
        'desc'      => '平均各个日期中各方向最大排队长度在所在时段中随时间变化规律',
    ],
    'speed' => [
        'name'      => '通过速度',
        'title'     => '各方向通过速度分析',
        'unit'      => '',
        'formula'   => 'min',
        'describe'  => function($a) {
            return $a[0] . '路口在基准日期内' . $a[1] . '方向通过速度最低，持续时间为' . $a[2] . '，评估日期内' . $a[3] . '方向通过速度最低，持续时间为' . $a[4] . '，同时' . $a[5] . '方向在' . $a[6] . '时刻平均通过速度' . $a[8] . '增至' . $a[7] . '。';
        },
        'round'     => function($val){ return round($val * 3.6, 2);}, // $val * 3.6 由米/秒 转换为千米/小时
        'desc'      => '平均各个日期中各方向停车比率在所在时段中随时间变化规律'
    ]
];

$config['road_direction'] = [
    1 => '东西',
    2 => '南北',
];
