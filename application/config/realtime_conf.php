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

// 南昌的路口过滤
$config['nanchang_filter_junction_ids'] = [
    "2017030116_4408929","2017030116_12424219","2017030116_14456340","2017030116_15035820","2017030116_13719018","2017030116_13719016","2017030116_13719010","2017030116_23164495","2017030116_23164500","2017030116_4379048","2017030116_4379046","2017030116_4413483","2017030116_4379014","2017030116_4379008","2017030116_3902627","2017030116_3896652","2017030116_19666313","2017030116_4395659","2017030116_4395673","2017030116_4411170","2017030116_3867298","2017030116_3870001","2017030116_3905785","2017030116_3902748","2017030116_4395841","2017030116_4395832","2017030116_4395670","2017030116_12735336","2017030116_3905786","2017030116_3872885","2017030116_3905781","2017030116_3872882","4e0d83c085ccc14b8712183ab915366e","2017030116_4377388","2017030116_4377394","2d8f4f2f5e7a7014bd47702a01762b08","f6b3cfea9189ff5dd1ab1ed4187b5f2d","8f5944455ce6bb1fde74fec132b7a46e","21cde51f211a19f8a6eef9552389b722","7554f5008b8ea08d0b7010c2690f87e5","057ee022e2b777768e9682d1d10d0959","2017030116_4411593","2017030116_4411597","fe7ca854517a7345b31f7faed46dc81c","1125485175fd15260a1b64415d88eca5","c974907be7af6e0bc479f1973cba81e5","c338286231fcaf0ca3b6f4404a59bc9b","2017030116_4377362","2017030116_4414341","2017030116_4414342","2017030116_4414343","2017030116_4414349","e85053624cd05d4313520db1444ffaec","2017030116_4391079","2017030116_4414398","2017030116_4390991","2017030116_4377419","76b8d26ec048c64cfcc27923410b33af","2017030116_4414417","8aa59aa36a47ae49799756c5b2f820f0","44ac4e5c26aca3fa81648e8297459a27","2017030116_4309913","2017030116_4373700","2017030116_4309917","2017030116_4390134","2017030116_4376020","2017030116_4376022","2017030116_4390998","d65169a5a058d27c96f188d149727b0d","fd00a10c577bda74f1bc19198b0a779c"
];


