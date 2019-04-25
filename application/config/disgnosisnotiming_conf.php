<?php
// 诊断详情flow指标
$config['flow_quota_key'] = [
    'route_length' => [
        'name' => '路段长度', // 中文名称
        'unit' => '米'       // 单位
    ],
    'queue_length' => [
        'name' => '排队长度',
        'unit' => '米',
    ],
    'stop_delay' => [
        'name' => '停车延误',
        'unit' => '秒',
    ],
    'stop_time_cycle' => [
        'name' => '停车次数',
        'unit' => '',
    ],
    'spillover_rate' => [
        'name' => '溢流比率',
        'unit' => '',
    ],
    'stop_rate' => [
        'name' => '停车比率',
        'unit' => '',
    ],
    'free_flow_speed' => [
        'name' => '自由流速度',
        'unit' => '千米/时',
    ],
    'confidence_level' => [
        'name' => '置信度',
        'unit' => '',
    ],
];

// 诊断详情flow指标
$config['flow_quota_round'] = [
    'queue_length' => [
        'round' => function ($val) {
            return round($val, 2);
        },  // 格式化数据
    ],
    'route_length' => [
        'round' => function ($val) {
            return round($val, 2);
        },  // 格式化数据
    ],
    'queue_position' => [
        'round' => function ($val) {
            return round($val);
        },
    ],
    'traj_count' => [
        'round' => function ($val) {
            return round($val);
        },
    ],
    'stop_delay' => [
        'round' => function ($val) {
            return round($val, 2);
        },
    ],
    'stop_time_cycle' => [
        'round' => function ($val) {
            return round($val, 2);
        },
    ],
    'spillover_rate' => [
        'round' => function ($val) {
            return round($val, 4);
        },
    ],
    'stop_rate' => [
        'round' => function ($val) {
            return round($val, 2);
        },
    ],
    'free_flow_speed' => [
        'round' => function ($val) {
            return round($val*3.6, 2);
        },
    ],
    'confidence' => [
        'round' => function ($val) {
            if($val>=30){
                return "高";
            }elseif ($val<=10){
                return "低";
            }
            return "中";
        },
    ],
];

// 定义诊断报警规则
$config['conf_rule'] = [
    'frequency_threshold' => 0.7, //报警问题,常偶发阈值
    'alarm_field' => ['is_oversaturation', 'is_spillover', 'is_imbalance'], //报警类型对应字段
    'alarm_types' => [
        'is_spillover' =>
            [
                'name' => '溢流',
                'index' => 'spillover_index',
                'cnt' => 'is_spillover_cnt',
                'diagnose' => 'spillover_index_diagnose',
            ],
        'is_imbalance' =>
            [
                'name' => '失衡',
                'index' => 'imbalance_index',
                'cnt' => 'is_imbalance_cnt',
                'diagnose' => 'imbalance_index_diagnose',
            ],
        'is_oversaturation' =>
            [
                'name' => '过饱和',
                'index' => 'oversaturation_index',
                'cnt' => 'is_oversaturation_cnt',
                'diagnose' => 'oversaturation_index_diagnose',
            ],
    ],
    'alarm_quotas' => [
        'speed' => [
            'name' => '平均延误',
            "unit" =>  "秒",
        ],
        'delay' => [
            'name' => '平均速度',
            "unit" =>  "千米/时",
        ],
    ],
];

// 定义诊断报警规则
$config['junction_question'] = [
    'is_oversaturation' => [
        "name"=>"过饱和",
        "quota_name"=>"过饱和问题趋势",
    ],
    'is_spillover' => [
        "name"=>"溢流",
        "quota_name"=>"溢流问题趋势",
    ],
    'is_imbalance' => [
        "name"=>"失衡",
        "quota_name"=>"失衡问题趋势",
    ],
];
