<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// 指标
$config['real_time_quota'] = [
    'stop_delay' => [
        'name'     => '停车延误',
        'unit'     => '秒',
        'round'    => function ($val) {
            return round($val, 2);
        },
        'escolumn' => 'stopDelayUp',
    ],
    'stop_time_cycle' => [
        'name'     => '停车次数', // 指标名称
        'unit'     => '次',        // 指标单位
        'round'    => function ($val) {
            return round($val, 2);
        }, // 取指标几位小数 用于返给前端时round()
        'escolumn' => 'avgStopNumUp',
    ],
    'spillover_rate' => [
        'name'     => '溢流指标',
        'unit'     => '',
        'round'    => function ($val) {
            return round($val, 4);
        },
        'escolumn' => 'spilloverRateDown',
    ],
    'queue_length' => [
        'name'     => '排队长度',
        'unit'     => '米',
        'round'    => function ($val) {
            return round($val);
        },
        'escolumn' => 'queueLengthUp',
    ],
    'stop_rate' => [
        'name'     => '失调指标',
        'unit'     => '',
        'round'    => function ($val) {
            return round($val, 4);
        },
        'escolumn' => 'oneStopRatioUp+multiStopRatioUp',
    ],
    'saturation' => [
        'name'     => '饱和度',
        'unit'     => '',
        'round'    => function ($val) {
            return round($val, 4);
        },
        'escolumn' => 'multiStopRatioUp+multiStopRatioUp',
    ],
];

// 路口状态
$config['junction_status'] = [
    // 畅通：停车延误 <= 阈值
    1 => [
        'name' => '畅通',    // 状态名
        'key' => 1,        // 状态KEY
    ],
    // 缓行：停车延误 > 阈值 && 停车延误 <= 阈值
    2 => [
        'name' => '缓行',
        'key' => 2,
    ],
    // 拥堵：停车延误 > 阈值
    3 => [
        'name' => '拥堵',
        'key' => 3,
    ],
];

// 路口状态计算规则
$config['junction_status_formula'] = function ($val) {
    if ($val >= 50) {
        return 3; // 拥堵
    } elseif ($val < 50 && $val >= 40) {
        return 2; // 缓行
    } else {
        return 1; // 畅通
    }
};

// 报警类别
$config['alarm_category'] = [
    1 => [
        'name' => '过饱和', // 类别名称
        'key' => 1,       // 类别KEY
        'desc' => '',      // 描述
    ],
    2 => [
        'name' => '溢流',
        'key' => 2,
        'desc' => '',
    ],
    3 => [
        'name' => '失衡',
        'key' => 3,
        'desc' => '',
    ],
];

// 相位报警类型
$config['flow_alarm_category'] = [
    1 => [
        'name' => '过饱和', // 类别名称
        'key' => 1,       // 类别KEY
        'desc' => '',      // 描述
        'order' => '3',
    ],
    2 => [
        'name' => '溢流',
        'key' => 2,
        'desc' => '',
        'order' => '4',
    ],
    3 => [
        'name' => '空放',
        'key' => 3,
        'desc' => '',
        'order' => '1',
    ],
    4 => [
        'name' => '轻度过饱和',
        'key' => 4,
        'desc' => '',
        'order' => '2',
    ],
];

// 指标评估数据redis KEY前缀
$config['quota_evaluate_key_prefix'] = 'quotaEvaluateDataKey_';

// 求指标平均值时的key
$config['avg_quota_key'] = [
    'avgSpeed' => [
        'name'     => '平均速度',    // 名称
        'esColumn' => 'avgSpeedUp', // 对应新ES字段
    ],
    'stopDelay' => [
        'name'     => '平均延误',
        'esColumn' => 'stopDelayUp',
    ],
];
