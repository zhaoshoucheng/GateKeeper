<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['quotas'] = [
    'stop_delay' => [
        'name'      => '停车延误',
        'unit'      => '秒',
        'formula'   => 'max',
        'describe'  => function($a) {
            $format = '%s路口在基准日期内%s方向延误时间最高，持续时间为%s，评估日期内%s方向延误时间最高，持续时间为%s，同时%s方向在%s时刻平均延误时间由%s减至%s。';
            return sprintf($format, $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7], $a[8], $a[9]);
        },
        'desc' => [
            1 => '各方向延误时间变化规律',
            2 => '各方向延误时间分析'
        ],
    ],
    'stop_time_cycle' => [
        'name'      => '停车次数', // 指标名称
        'unit'      => '次',
        'formula'   => 'max',
        'describe'  => function($a) {
            $format = '%s路口在基准日期内%s方向停车次数最多，持续时间为%s，评估日期内%s方向停车次数最多，持续时间为%s-%s，同时%s方向在%s时刻平均停车次数由%s减至%s。';
            return sprintf($format, $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7], $a[8], $a[9]);
        },
        'desc' => [
            1 => '各方向平均停车次数时间变化规律',
            2 => '各方向平均停车次数分析'
        ]
    ],
    'queue_length' => [
        'name'      => '排队长度',
        'unit'      => '米',
        'formula'   => 'max',
        'describe'  => function($a) {
            $format = '%s路口在基准日期内%s方向排队长度最长，持续时间为%s-%s，评估日期内%s方向最大排队长度最长，持续时间为%s-%s，同时%s方向在%s时刻平均最大排队长度由%s减至%s。';
            return sprintf($format, $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7], $a[8], $a[9]);
        },
        'desc' => [
            1 => '各方向最大排队长度时间变化规律',
            2 => '各方向最大排队长度分析'
        ],
    ],
    'speed' => [
        'name'      => '通过速度',
        'unit'      => '',
        'formula'   => 'min',
        'describe'  => function($a) {
            $format = '%s路口在基准日期内%s方向通过速度最低，持续时间为%s-%s，评估日期内%s方向通过速度最低，持续时间为%s-%s，同时%s方向在%s时刻平均通过速度%s增至%s。';
            return sprintf($format, $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6], $a[7], $a[8], $a[9]);
        },
        'desc' => [
            1 => '各方向通过速度时间变化规律',
            2 => '各方向通过速度分析'
        ]
    ]
];