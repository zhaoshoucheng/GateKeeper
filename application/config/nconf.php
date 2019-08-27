<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|-----------------------------------------------------
| 对外API配置文件
|-----------------------------------------------------
*/
// 线上机器
$online_host = [
    'ipd-cloud-web00.gz01',
    'ipd-cloud-web01.gz01',
    'ipd-cloud-preweb00.gz01',
];

$hostname    = gethostname();
$development = 1; //开发环境
if (in_array($hostname, $online_host)) {
    $development = 2;
}

if ($development == 2) {
    //线上及预发环境配置

    // 路网接口服务器地址
    $waymap_server = '100.69.238.11';
    // 路网接口服务器端口
    $waymap_port = '8000';
    // 路网接口前缀
    $waymap_ext = '/its';

    // 配时接口服务器地址
    $timing_server = '100.69.238.11';
    // 配时接口服务器端口
    $timing_port = '8000';
    // 配时接口前缀
    $timing_ext = '/its/signal-mis';

    $config['redis'] = [
        'host' => '100.69.239.57',
        'port' => '3060',
        'timeout' => '3',
    ];

    // signal-mis
    $signal_mis_server = '100.69.238.11';
    $signal_mis_port   = '8000';
    $signal_mis_ext    = '/its/signal-mis';

    // signal_light
    $signal_light_server = 'http://10.88.128.40:8000/ipd-cloud/signal-platform';

    $signal_rollback_url = "http://10.85.128.81:30357/signal-control/signalprofile/rollback";
    $signal_base_url = "http://10.85.128.81:30357/signal-control/signalopt/querybasetiming";
    $signal_timing_url = "http://10.85.128.81:30357/signal-control/signal/querytiming";

    // 路口配时状态
    $signal_timing_status_url = "http://10.85.128.81:30357/signal-control/signalprofile/timingstatusbatch";
    // es 老实时轨迹、指标数据
    $es_server = 'http://10.85.128.208:8001';

    // new es 新实时轨迹、指标数据
    $quota_v2_es_server = 'http://10.85.128.208:8001/api/data';

    // new timing
    $signal_control_server = '10.88.128.149';
    $signal_control_port   = '30516';
    $signal_control_ext    = '/signal-timing';

    // xmmtrace
    $xmmtrace_server = '10.85.128.208';
    $xmmtrace_port   = '8001';
    $xmmtrace_ext    = '/api/transmit/Traffic';

    //traj  config
    $traj_server = '10.85.128.81:30187';
    $traj_ext   = '/traj-service';

    // 报警es接口
    $alarm_es_interface = [
        '1819:v19NJfhpxfL0pit@10.88.128.149:30963',
    ];
    // 报警ES索引
    $alarm_es_index = [
        'junction' => 'online_its_alarm_junction_month*',
        'flow'     => 'online_its_alarm_movement_month*',
    ];

    // 数据服务
    $data_service_server = '100.90.163.51';
    $data_service_prot = '8099';
    $data_service_ext = '';

    //报警数据历史处理
    $realtime_callback = 'http://10.85.128.81:30101';

    //需要验证城市权限
    $validateCity = 1;

    //城市自适应配时下发频率配置
    $city_upsigntime_interval = [
        "12"=>2,
        "134"=>2,
    ];

    //新版指标开城列表
    $quota_v2_city_ids = [1, 12, 57, 85, 134];

    //新版报警开城列表
    $alarm_v2_city_ids = [12, 23];
} else {
    // 路网接口服务器地址
    $waymap_server = '100.90.164.31';
    // 路网接口服务器端口
    $waymap_port = '8001';
    // 路网接口前缀
    $waymap_ext = '';

    // 配时接口服务器地址
    $timing_server = '100.90.164.31';
    // 配时接口服务器端口
    $timing_port = '8031';
    // 配时接口前缀
    $timing_ext = '/signal-timing';

    $config['redis'] = [
        'host' => '127.0.0.1',
        'port' => '6379',
        'timeout' => '3',
    ];

    // signal-mis
    $signal_mis_server = '100.90.164.31';
    $signal_mis_port   = '8006';
    $signal_mis_ext    = '/signal-mis';

    // signal_light
    $signal_light_server = 'http://10.148.28.204:8001';

    // signal_rollback
    $signal_rollback_url = "http://100.90.164.31:8016/signal-control/signalprofile/rollback";
    $signal_base_url = "http://100.90.164.31:8016/signal-control/signalopt/querybasetiming";
    $signal_timing_url = "http://100.90.164.31:8016/signal-control/signal/querytiming";

    // 路口配时状态
    $signal_timing_status_url = "http://100.90.164.31:8016/signal-control/signalprofile/timingstatusbatch";

    // es
    $es_server = 'http://10.89.236.25:8087';

    // new es
    $quota_v2_es_server = 'http://10.89.236.25:8090';
//    $quota_v2_es_server = '10.89.234.61';
//    $quota_v2_es_port   = '8090';
//    $quota_v2_es_ext    = '';

    // new timing
    $signal_control_server = '100.90.164.31';
    $signal_control_port   = '8031';
    $signal_control_ext    = '/signal-timing';

    // xmmtrace
    $xmmtrace_server = '100.69.238.158';
    $xmmtrace_port   = '8001';
    $xmmtrace_ext    = '/api/transmit/Traffic';

    //traj  config
    $traj_server = '100.90.164.31:8032';
    $traj_ext   = '/traj-service';

    // 报警es
    $alarm_es_interface = [
        '1819:v19NJfhpxfL0pit@10.88.128.149:30963',
    ];
    // 报警ES索引
    $alarm_es_index = [
        'junction' => 'its_alarm_junction_month*',
        'flow'     => 'its_alarm_movement_month*',
    ];

    // 数据服务
    $data_service_server = '100.90.164.31';
    $data_service_prot = '8093';
    $data_service_ext = '';

    //报警数据历史处理
    $realtime_callback = 'http://100.90.164.31:8033';

    //需要验证城市权限
    $validateCity = 0;

    //城市自适应配时下发频率配置
    $city_upsigntime_interval = [
        "12"=>2,
        "134"=>2,
    ];

    //新版诊断指标开城列表(后期被common_model->getV5DMPCityID替代了)
    $quota_v2_city_ids = [1,2,3,4,5,6,10,11,12,13,15,22,23,26,29,33,34,38,47,56,57,60,85,134,135,145,157,161,162,164,168,232,260,262];

    //新版报警开城列表（当接收到报警消息时，哪些城市走scala新版报警？）
    $alarm_v2_city_ids = [1,5,10,12,23,134];
}

$temp_waymap_port  = !empty($waymap_port) ? ":" . $waymap_port : "";
$temp_timing_port  = !empty($timing_port) ? ":" . $timing_port : "";
$signal_mis_port   = !empty($signal_mis_port) ? ":" . $signal_mis_port : "";
$es_port           = !empty($es_port) ? ":" . $es_port : "";
$quota_v2_es_port  = !empty($quota_v2_es_port) ? ":" . $quota_v2_es_port : "";
$alarm_port        = !empty($alarm_port) ? ":" . $alarm_port : "";
$data_service_prot = !empty($data_service_prot) ? ':' . $data_service_prot : '';

// 路网接口地址
$config['waymap_interface'] = 'http://' . $waymap_server . $temp_waymap_port . $waymap_ext;
// 路网接口token
$config['waymap_token']  = '4c3e3b6a3588161128d0604daab528db';
$config['waymap_userid'] = 'signalPro';

// 路网接口地址
$config['traj_interface'] = 'http://' . $traj_server . $traj_ext;
$config['traj_token']  = '4c3e3b6a3588161128d0604daab528dbxxxx';
$config['traj_userid'] = 'signalPro';

// 配时接口地址
$config['timing_interface'] = 'http://' . $timing_server . $temp_timing_port . $timing_ext;

// signal-mis接口地址
$config['signal_mis_interface'] = 'http://' . $signal_mis_server . $signal_mis_port . $signal_mis_ext;

// signal-light接口地址
$config['signal_light_interface'] = $signal_light_server;

// signal_rollback
$config['signal_rollback_url'] = $signal_rollback_url;
$config['signal_base_url'] = $signal_base_url;
$config['signal_timing_url'] = $signal_timing_url;

$config['signal_timing_status_url'] = $signal_timing_status_url;

// 实时指标接口地址
$config['es_interface'] = $es_server;

// 新指标接口地址
$config['new_es_interface'] = $quota_v2_es_server;

// 新版配时地址
$config['signal_control_interface'] = 'http://' . $signal_control_server . ":" . $signal_control_port . $signal_control_ext;

// 新版轨迹地址
$config['xmmtrace_interface'] = 'http://' . $xmmtrace_server . ":" . $xmmtrace_port . $xmmtrace_ext;

// 新版轨迹地址
$config['warning_interface'] = 'http://monitor.odin.xiaojukeji.com';

// 实时报警数据回调接口
$config['realtime_callback'] = $realtime_callback;

// 报警es接口地址
$config['alarm_es_interface'] = $alarm_es_interface;

// 报警ES索引
$config['alarm_es_index'] = $alarm_es_index;

// 是否验证城市权限
$config['validate_city'] = $validateCity;

// 城市自适应配时下发频率配置
$config['city_upsigntime_interval'] = $city_upsigntime_interval;

// 数据服务
$config['data_service_interface'] = 'http://' . $data_service_server . $data_service_prot . $data_service_ext;

// 评估置信度阈值
$confidence_threshold = 0.5;

// 评估置信度
$config['confidence'] = [
    0 => [
        // 备注
        'name' => '全部',
        // 用于组织sql语句的where条件
        'sql_where' => function ($val) {
            return [
                $val . '>' => 0,
            ];
        },
        // 用于判断
        'formula' => function ($val) {
            return [
                $val . '>' => 0,
            ];
        },
    ],
    1 => [
        'name' => '高',
        'sql_where' => function ($val) use ($confidence_threshold) {
            return [
                $val . '>' => $confidence_threshold,
            ];
        },
        'formula' => function ($val) use ($confidence_threshold) {
            return $val > $confidence_threshold;
        },
    ],
    2 => [
        'name' => '低',
        'sql_where' => function ($val) use ($confidence_threshold) {
            return [
                $val . '<' => $confidence_threshold,
            ];
        },
        'formula' => function ($val) use ($confidence_threshold) {
            return $val < $confidence_threshold;
        },
    ],
    3 => [
        'name' => '中',
        'sql_where' => function ($val) {
            return [
                $val . '>' => 0,
            ];
        },
        'formula' => function ($val) {
            return $val > 0;
        },
    ],
];

// 路口指标
$config['junction_quota_key'] = [
    'imbalance_index' => [
        'name' => '失衡指数',                                  // 名称
        'status_formula' => function ($val) {                  // 状态判断规则
            if ($val > 0.6) {
                return 1; // 高
            } elseif ($val > 0.3 && $val <= 0.6) {
                return 2; // 中
            } else {
                return 3; // 低
            }
        },
        'round' => function ($val) {
            return round($val, 2);
        }, // 格式化数据
        'unit' => ''                                        // 单位
    ],
    'spillover_index' => [
        'name' => '溢流指数',
        'status_formula' => function ($val) {
            if ($val > 0.08) {
                return 1;
            } elseif ($val > 0.04 && $val <= 0.08) {
                return 2;
            } else {
                return 3;
            }
        },
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
    'incoordination_index' => [
        'name' => '失调指数',
        'status_formula' => function ($val) {
            if ($val > 0.7) {
                return 1;
            } elseif ($val > 0.4 && $val <= 0.7) {
                return 2;
            } else {
                return 3;
            }
        },
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
    'saturation_index' => [
        'name' => '饱和指数',
        'status_formula' => function ($val) {
            if ($val > 0.9) {
                return 1;
            } elseif ($val > 0.3 && $val <= 0.9) {
                return 2;
            } else {
                return 3;
            }
        },
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
    'stop_cycle_time' => [
        'name' => '停车次数',
        'status_formula' => function ($val) {
            if ($val > 2) {
                return 1;
            } elseif ($val > 1 && $val <= 2) {
                return 2;
            } else {
                return 3;
            }
        },
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
    'stop_delay' => [
        'name' => '平均延误',
        'status_formula' => function ($val) {
            if ($val > 40) {
                return 1;
            } elseif ($val > 20 && $val <= 40) {
                return 2;
            } else {
                return 3;
            }
        },
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '秒',
    ],
    'avg_speed' => [
        'name' => '平均速度',
        'status_formula' => function ($val) {
            if ($val > 40) {
                return 1;
            } elseif ($val > 20 && $val <= 40) {
                return 2;
            } else {
                return 3;
            }
        },
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '千米/时',
    ],
];

//flow诊断指标
$config['diagnosis_flow_quota_key'] = [
    'route_length' => [
        'name' => '路段长度', // 中文名称
        'round' => function ($val) {
            return round($val);
        },  // 格式化数据
        'unit' => '米'       // 单位
    ],
    'confidence'=>[
        'name'=>'置信度',
        'unit'=>'',
        'round'=>function($val){
            return $val;
        },
    ],//置信度
    'queue_length' => [
        'name' => '排队长度',
        'round' => function ($val) {
            return round($val);
        },
        'unit' => '米',
    ],
//    'saturation_degree' => [
//        'name' => '饱和度',
//        'round' => function ($val) {
//            return round($val, 2);
//        },
//        'unit' => '',
//    ],
    'stop_delay' => [
        'name' => '停车延误',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '秒',
    ],
    'stop_time_cycle' => [
        'name' => '停车次数',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
    'spillover_rate' => [
        'name' => '溢流比率',
        'round' => function ($val) {
            return round($val, 4);
        },
        'unit' => '',
    ],
    'stop_rate' => [
        'name' => '停车比率',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
//    'flow_num' => [
//        'name' => '流量',
//        'round' => function ($val) {
//            return round($val);
//        },
//        'unit' => '每小时/车道',
//    ],
    'free_flow_speed' => [
        'name' => '自由流',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '千米/时',
    ],
];
// flow指标
$config['flow_quota_key'] = [
    'route_length' => [
        'name' => '路段长度', // 中文名称
        'round' => function ($val) {
            return round($val);
        },  // 格式化数据
        'unit' => '米'       // 单位
    ],
    'queue_position' => [
        'name' => '排队长度',
        'round' => function ($val) {
            return round($val);
        },
        'unit' => '米',
    ],
    'saturation_degree' => [
        'name' => '饱和度',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
    'stop_delay' => [
        'name' => '停车延误',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '秒',
    ],
    'stop_time_cycle' => [
        'name' => '停车次数',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
    'spillover_rate' => [
        'name' => '溢流比率',
        'round' => function ($val) {
            return round($val, 4);
        },
        'unit' => '',
    ],
    'stop_rate' => [
        'name' => '停车比率',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '',
    ],
    'flow_num' => [
        'name' => '流量',
        'round' => function ($val) {
            return round($val);
        },
        'unit' => '每小时/车道',
    ],
    'free_flow_speed' => [
        'name' => '自由流',
        'round' => function ($val) {
            return round($val, 2);
        },
        'unit' => '千米/时',
    ],
    'saturation' =>[
        'name'=>'饱和度',
        'round'=>function($val){
            return round($val,4);
        },
        'unit'=>'',
    ],
];

// 诊断问题
$config['diagnose_key'] = [
    'spillover_index' => [
        'name' => '溢流',
        'junction_diagnose_formula' => function ($val) {
            return $val > 0.008;
        },
        'sql_where' => function () {
            return [
                'spillover_index >' => 0.005,
            ];
        },
        'nature_formula' => function ($val) {
            if ($val > 0.6) {
                return 1;
            } elseif ($val > 0.3 && $val <= 0.6) {
                return 2;
            } elseif ($val > 0 && $val <= 0.3) {
                return 3;
            } else {
                return 0;
            }
        },
        'flow_quota' => [
            'spillover_rate' => [
                'name' => '溢流比率',
                'unit' => '',
            ],
            'queue_position' => [
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
            'route_length' => [
                'name' => '路段长度',
                'unit' => '米',
            ],
        ],
        // flow级的诊断问题 规则：溢流比率 > 0.008
        'flow_diagnose' => [
            'quota' => 'spillover_rate',
            'formula' => function ($val) { // $val = ['spillover_rate'=>xxx]
                return $val > 0.008;
            },
        ],
    ],
    'imbalance_index' => [
        'name' => '失衡',
        'junction_diagnose_formula' => function ($val) {
            return $val > 0;
        },
        'sql_where' => function () {
            return [
                'imbalance_index > ' => 0,
            ];
        },
        'nature_formula' => function ($val) {
            if ($val > 0.08) {
                return 1;
            } elseif ($val > 0.04 && $val <= 0.08) {
                return 2;
            } elseif ($val > 0.005 && $val <= 0.04) {
                return 3;
            } else {
                return 0;
            }
        },
        'flow_quota' => [
            'saturation_degree' => [
                'name' => '饱和度',
                'unit' => '',
            ],
            'queue_position' => [
                'name' => '排队长度',
                'unit' => '米',
            ],
            'stop_delay' => [
                'name' => '停车延误',
                'unit' => '秒',
            ],
            'stop_time_cycle' => [
                'name' => '停车次数',
                'unit' => '秒',
            ],
            'route_length' => [
                'name' => '路段长度',
                'unit' => '米',
            ],
        ],
        // flow级的诊断问题 规则：饱和度 >= 0 或 饱和度 < 2
        'flow_diagnose' => [
            'quota' => 'saturation_degree',
            'formula' => function ($val) { // $val = ['spillover_rate'=>xxx]
                return ($val > 0 || $val < 2);
            },
        ],
    ],
    'saturation_index' => [
        'name' => '空放',
        'junction_diagnose_formula' => function ($val) {
            return $val < 0.3;
        },
        'sql_where' => function () {
            return [
                'saturation_index < ' => 0.3,
            ];
        },
        'nature_formula' => function ($val) {
            if ($val < 0.1) {
                return 1;
            } elseif ($val >= 0.1 && $val < 0.2) {
                return 2;
            } elseif ($val >= 0.2 && $val < 0.3) {
                return 3;
            } else {
                return 0;
            }
        },
        'flow_quota' => [
            'saturation_degree' => [
                'name' => '饱和度',
                'unit' => '',
            ],
            'stop_delay' => [
                'name' => '停车延误',
                'unit' => '秒',
            ],
            'stop_time_cycle' => [
                'name' => '停车次数',
                'unit' => '',
            ],
        ],
        // flow级的诊断问题 规则：饱和度 < 0.3
        'flow_diagnose' => [
            'quota' => 'saturation_degree',
            'formula' => function ($val) { // $val = ['spillover_rate'=>xxx]
                return $val < 0.3;
            },
        ],
    ],
    'over_saturation' => [
        'name' => '过饱和',
        'junction_diagnose_formula' => function ($val) {
            return $val > 1;
        },
        'sql_where' => function () {
            return [
                'saturation_index > ' => 1,
            ];
        },
        'nature_formula' => function ($val) {
            if ($val > 1.5) {
                return 1;
            } elseif ($val > 1.2 && $val <= 1.5) {
                return 2;
            } elseif ($val > 1 && $val <= 1.2) {
                return 3;
            } else {
                return 0;
            }
        },
        'flow_quota' => [
            'saturation_degree' => [
                'name' => '饱和度',
                'unit' => '',
            ],
            'stop_delay' => [
                'name' => '停车延误',
                'unit' => '秒',
            ],
            'stop_time_cycle' => [
                'name' => '停车次数',
                'unit' => '',
            ],
            'queue_position' => [
                'name' => '排队长度',
                'unit' => '米',
            ],
        ],
        // flow级的诊断问题 饱和度 > 0.9
        'flow_diagnose' => [
            'quota' => 'saturation_degree',
            'formula' => function ($val) { // $val = ['spillover_rate'=>xxx]
                return $val > 0.9;
            },
        ],
    ],
];


// 诊断置信度阈值
$config['diagnose_confidence_threshold'] = 0.5;

// 排序
$config['sort_conf'] = [
    1 => 'asc',
    2 => 'desc',
];

// result_comment配置
$config['result_comment'] = [
    'signal_mes_error' => '配时信息与实际车流不符',
];

// 绿信比优化建议
$config['split_opt_suggest'] = [
    'over_saturation_flow' => '相位:movement过饱和，建议增加绿灯时长',
    'green_loss_flow' => '相位:movement存在空放，可缩短绿灯时长',
];

// 反推配时名单

$config['back_timing_roll'] = [
    '13114526633', '15589979969', '15011161396', '15210612210', '15893024010', '18661627981',
];

// 定义tracelog的action
$config['action_log_map'] = [
    'adapt_area_switch_edit' => '自适应区域配时开关修改',
];

// 定义实时报警规则
$config['realtimewarning_rule'] = [
    'default' => [
        'isOverFlow' => [
            'spillover_rate' => 0.2,
            'stop_delay' => 40,
        ],
        'isSAT' => [
            'twice_stop_rate' => 0.2,
            'queue_length' => 180,
            'stop_delay' => 50,
        ],
        'where' => ' and traj_count >= 10',
    ],
    '12' => [
        'isOverFlow' => [
            'spillover_rate' => 0.2,
            'stop_delay' => 40,
        ],
        'isSAT' => [
            'twice_stop_rate' => 0.2,
            'queue_length' => 180,
            'stop_delay' => 50,
        ],
        'where' => ' and traj_count >= 10',
    ],
];

//配置gift
$config['gift'] = [
    'upload' => [
        'itstool_public' => 'http://100.69.238.36:8000/resource/itstool_public',
        'itstool_private' => 'http://100.69.238.36:8000/resource/itstool_private',
    ],
    'get' => [
        'itstool_public' => 'http://100.69.238.37:8000/resource/itstool_public',
        'itstool_private' => 'http://100.69.238.37:8000/resource/itstool_private',
    ],
    'batch' => [
        'itstool_public' => 'http://100.69.238.37:8000/batch/resource/itstool_public',
        'itstool_private' => 'http://100.69.238.37:8000/batch/resource/itstool_private',
    ],
];

//配置
$config['inroute'] = [
    'default' => [
        'allow_host_ip' => [
            "10.89.236.25",     //三台jixiang机器
            "10.89.234.61",
            "10.89.235.12",
            "100.90.164.31",    //server01
            "100.90.163.51",    //web00
            "100.90.163.52",    //web01
            "172.25.32.135",    //测试
            "100.90.165.32",    //preweb00
        ],
        'salt_token' => '99f8698a68a2fa78',
    ],
];


// 屏控服务开关
$config['security_frequency_switch'] = true;

$config['user_feedback_types'] = [
    1 => '报警信息',
    2 => '指标计算',
    3 => '诊断问题',
    4 => '评估内容',
    5 => '优化结果',
    6 => '页面交互',
    7 => '其他',
];

//广州大屏城市Id对应junctionIds
//关键路口
$config['key_junction_list'] = [
    "3" => ['2017030116_1325121','2017030116_1354281','2017030116_1313534','2017030116_47331750','2018062712_1319149','ebc663511bf8cc63105cb63ed406873f','e99d8510ccae3f0081a51f06e6792438','a0a55f02a34723d3763eb612f79dc0d2','80540994a2781cb355b8832b516b76ab','4c12fb6e2a0499f5313107993a226417','7ef723cc51f7616791d7a30f51c9c2fc','ef2761d061f11a46014a7b920e2cd445','f04abf18906dfe64a0de80e41164adba',],
];
//配时路口
$config['timing_junction_list'] = [
    "3" => [
        'f04abf18906dfe64a0de80e41164adba'=>"越秀中路_中山四路",
        '2017030116_1322134'=>"仓边路_中山四路",
        '2017030116_1322138'=>"中山四路_德政北路",
        '1860bd7677da44c9e60ee9405d33709a'=>"中山四路_榨粉街(秉政街)",
        '2017030116_1325121'=>"江南大道中_江南西路",
        '2017030116_1354281'=>"解放北路_三元里大道",
        '2017030116_1313534'=>"天河路_体育东路_1313534",
        '2017030116_47331750'=>"西场立交桥_内环路",
        '2018062712_1319149'=>"广园东路_禺东西路",
        'ebc663511bf8cc63105cb63ed406873f'=>"金穗路-猎德大道",
        'e99d8510ccae3f0081a51f06e6792438'=>"广园路-天寿路",
        'a0a55f02a34723d3763eb612f79dc0d2'=>"花城大道_冼村路",
        '80540994a2781cb355b8832b516b76ab'=>"花城大道-平云路（华南快速出口）",
        '4c12fb6e2a0499f5313107993a226417'=>"天河路-天河城大街东行人过街",
        '7ef723cc51f7616791d7a30f51c9c2fc'=>"黄埔大道西-体育西路",
        'ef2761d061f11a46014a7b920e2cd445'=>"体育西路_天河北路",
        'f04abf18906dfe64a0de80e41164adba'=>"越秀中路_中山四路",
    ],
];

//upm权限
$config['upm_usergroup_prefix'] = "signal_gateway_upm_";

// 搜索引擎
$config['data_engine'] = 'elastic';

//新版指标开城列表
$config['quota_v2_city_ids'] = $quota_v2_city_ids;

//新版报警开城列表及ip限制
$config['alarm_v2_city_ids'] = $alarm_v2_city_ids;
$config['alarm_v2_client_ips'] = ["100.90.165.32","100.90.164.31",];    //默认沙盒触发