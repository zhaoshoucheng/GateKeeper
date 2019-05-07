<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|-----------------------------------------------------
| 配置文件
|-----------------------------------------------------
*/
// 控制参数
$config['control_params'] = [
    "city"=>[
        "default"=>[
            "queue_ratio_thresh"=>0.8,
            "movement_weight_thresh"=>0.8,
            "min_trajs_num"=>6,
            "multi_stop_upper_thresh"=>0.2,
            "non_stop_thresh_lower_bound"=>0.05,
            "queue_length_upper_thresh"=>100,
            "signal_relax_bound"=>20,
        ],
        "1"=>[
            "queue_ratio_thresh"=>0.7,
            "movement_weight_thresh"=>0.8,
            "min_trajs_num"=>6,
            "multi_stop_upper_thresh"=>0.2,
            "non_stop_thresh_lower_bound"=>0.05,
            "queue_length_upper_thresh"=>100,
            "signal_relax_bound"=>20,
        ],
        "12"=>[
            "queue_ratio_thresh"=>0.7,
            "movement_weight_thresh"=>0.8,
            "min_trajs_num"=>10,
            "multi_stop_upper_thresh"=>0.2,
            "non_stop_thresh_lower_bound"=>0.05,
            "queue_length_upper_thresh"=>120,
            "signal_relax_bound"=>20,
        ],
        "134"=>[
            "queue_ratio_thresh"=>0.7,
            "movement_weight_thresh"=>0.8,
            "min_trajs_num"=>8,
            "multi_stop_upper_thresh"=>0.2,
            "non_stop_thresh_lower_bound"=>0.05,
            "queue_length_upper_thresh"=>120,
            "signal_relax_bound"=>10,
        ],
        "85"=>[
            "queue_ratio_thresh"=>0.8,
            "movement_weight_thresh"=>0.8,
            "min_trajs_num"=>8,
            "multi_stop_upper_thresh"=>0.2,
            "non_stop_thresh_lower_bound"=>0.05,
            "queue_length_upper_thresh"=>120,
            "signal_relax_bound"=>10,
        ],
    ]
];