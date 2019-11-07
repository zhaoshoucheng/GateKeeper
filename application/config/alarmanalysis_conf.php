<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
|-----------------------------------------------------
| 报警分析配置文件
|-----------------------------------------------------
*/


// 报警频率配置
$config['frequency_type'] = [
    0 => '全部',
    1 => '常发',
    2 => '偶发',
];

// 相位报警类型
$config['flow_alarm_type'] = [
    1 => '过饱和',
    2 => '溢流',
    3 => '空放',
    4 => '轻度过饱和',
];

// 路口报警类型
$config['junction_alarm_type'] = [
    1 => '过饱和',
    2 => '溢流',
    3 => '失衡',
];


//绿波优化工具参数&&TOP20延误阈值参数
$config['tool_param_default'] = '{
    "cycle_optimization_limit": "1",
    "cycle_optimization_lower_limit": "2",
    "congestion_level_lower_limit": "0",
    "slow_down_level_lower_limit": "0"
}';

//默认离线报警参数
$config['alarm_param_realtime_default'] = '{
    "overSatuTrailNumPara": "10",
    "greenSlackTrailNumPara": "5",
    "stopDelayPara": "40.0",
    "multiStopUpperBound": "0.2",
    "multiStopLowerBound": "0.05",
    "noneStopUpperBound": "0.5",
    "noneStopLowerBound": "0.1",
    "queueLengthUpperBound": "140.0",
    "queueLengthLowerBound": "50.0",
    "queueRatioLowBound": "0.25",
    "spilloverTrailNumPara": "10",
    "queueRatioPara": "0.8",
    "spilloverAlarmTrailNumPara": "2",
    "spilloverStopDelayPara": "40.0"
}';

//默认离线报警参数
$config['alarm_param_offline_default'] = '{
    "over_saturation_traj_num": "10",
    "over_saturation_multi_stop_ratio_up": "0.3",
    "over_saturation_none_stop_ratio_up": "0.05",
    "over_saturation_queue_length_up": "180",
    "over_saturation_queue_rate_up": "0.4",
    "spillover_traj_num": "10",
    "spillover_rate_down": "0.2",
    "spillover_queue_rate_down": "0.9",
    "spillover_avg_speed_down": "5",
    "unbalance_traj_num": "5",
    "unbalance_free_multi_stop_ratio_up": "0.05",
    "unbalance_free_none_stop_ratio_up": "0.4",
    "unbalance_free_queue_length_up": "70",
    "unbalance_over_saturation_multi_stop_ratio_up": "0.2",
    "unbalance_over_saturation_none_stop_ratio_up": "0.05",
    "unbalance_over_saturation_queue_length_up": "150"
}';
