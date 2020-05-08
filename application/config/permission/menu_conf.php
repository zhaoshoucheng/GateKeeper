<?php


$config['menu'] = [

    'check' => function ($user) {
        $users = [];
        if (isset($_REQUEST['city_id']) && $_REQUEST['city_id'] == "85") {
            return 3;
        }
        if (isset($_REQUEST['city_id']) && $_REQUEST['city_id'] == "12") {
            return 7;
        }
        if (isset($_REQUEST['city_id']) && $_REQUEST['city_id'] == "11" && strpos($_SERVER["HTTP_REFERER"], "/nanjing")) {
            return 6;
        }
        if (isset($_REQUEST['city_id']) && isset($_REQUEST['from']) && $_REQUEST['city_id'] == "11" && $_REQUEST['from'] == "nanjing") {
            return 6;
        }
        if (isset($_REQUEST['city_id']) && isset($_REQUEST['from']) && $_REQUEST['city_id'] == "23" && $_REQUEST['from'] == "suzhou") {
            return 8;
        }

        //南昌本地化
        if (
            isset($_REQUEST['city_id'])
            && isset($_REQUEST['conf'])
            && $_REQUEST['city_id'] == "38"
            && $_REQUEST['conf'] == "local"
        ) {
            return 4;
        }
        //南昌固定IP
        if ($_SERVER['REMOTE_ADDR'] == "59.52.254.218") {
            return 4;
        }
        //武汉固定IP
        if ($_SERVER['REMOTE_ADDR'] == "111.47.15.179") {
            return 5;
        }
        if (in_array($user, $users)) {
            return 2;
        }
        return 1;
    },

    'menuList' => [
        // 全部菜单
        1 => [
            0 =>
            [
                'name'   => '概览',
                'url'    => '/overview',
                'remark' => 'signal',
            ],
            1 =>
            [
                'name'   => '诊断',
                'url'    => '/diagnose/tendency',
                'remark' => 'signal',
            ],
            2 =>
            [
                'name'   => '评估',
                'url'    => '/assessment/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口评估',
                        'url'  => 'junction',
                    ],
                    1 =>
                    [
                        'name' => '干线评估',
                        'url'  => 'road',
                    ],
                    2 =>
                    [
                        'name' => '区域评估',
                        'url'  => 'area',
                    ],
                ],
            ],
            3 =>
            [
                'name'   => '优化',
                'url'    => '/optimize/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '单点时段优化',
                        'url'  => 'signal',
                    ],
                    1 =>
                    [
                        'name' => '单点绿信比优化',
                        'url'  => 'green',
                    ],
                    2 =>
                    [
                        'name' => '干线协调优化',
                        'url'  => 'coordinate',
                    ],
                ],
            ],
            4 =>
            [
                'name' => '自适应',
                'url'  => '/adaptive',
            ],
            5 =>
            [
                'name'   => '报告',
                'url'    => '/report',
                'remark' => 'signal',
            ],
            6 =>
            [
                'name'   => '管理',
                'url'    => '/manage/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '路口管理',
                        'url'  => 'configuration',
                    ],
                    1 =>
                    [
                        'name' => '信控管理',
                        'url'  => 'diagnose',
                    ],
                    /*2 =>
                            [
                                'name' => '参数管理',
                                'url'  => 'argument',
                            ],*/
                    2 =>
                    [
                        'name' => '任务管理',
                        'url'  => 'task',
                    ],
                ],
            ],
        ],
        // 北京区域受限菜单
        2 => [
            [
                'name'   => '概览',
                'url'    => '/overview',
                'remark' => 'signal',
            ],
            [
                'name'   => '诊断',
                'url'    => '/diagnose/tendency',
                'remark' => 'signal',
            ],
            [
                'name'   => '优化',
                'url'    => '/optimize/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '单点时段优化',
                        'url'  => 'signal',
                    ],
                    1 =>
                    [
                        'name' => '单点绿信比优化',
                        'url'  => 'green',
                    ],
                    2 =>
                    [
                        'name' => '干线协调优化',
                        'url'  => 'coordinate',
                    ],
                ],
            ],
        ],
        // 全部菜单
        3 => [
            0 =>
            [
                'name'   => '概览',
                'url'    => '/overview',
                'remark' => 'signal',
            ],
            1 =>
            [
                'name' => '自适应',
                'url'  => '/adaptive',
            ],
            2 =>
            [
                'name'   => '优化',
                'url'    => '/optimize/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '单点时段优化',
                        'url'  => 'signal',
                    ],
                    1 =>
                    [
                        'name' => '单点绿信比优化',
                        'url'  => 'green',
                    ],
                    2 =>
                    [
                        'name' => '干线协调优化',
                        'url'  => 'coordinate',
                    ],
                ],
            ],
            3 =>
            [
                'name'   => '诊断',
                'url'    => '/diagnose/tendency',
            ],
            4 =>
            [
                'name'   => '评估',
                'url'    => '/assessment/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口评估',
                        'url'  => 'junction',
                    ],
                    1 =>
                    [
                        'name' => '干线评估',
                        'url'  => 'road',
                    ],
                    2 =>
                    [
                        'name' => '区域评估',
                        'url'  => 'area',
                    ],
                ],
            ],
            5 =>
            [
                'name'   => '报告',
                'url'    => '/report',
                'remark' => 'signal',
            ],
            6 =>
            [
                'name'   => '管理',
                'url'    => '/manage/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '路口管理',
                        'url'  => 'configuration',
                    ],
                    1 =>
                    [
                        'name' => '信控管理',
                        'url'  => 'diagnose',
                    ],
                    /*2 =>
                            [
                                'name' => '参数管理',
                                'url'  => 'argument',
                            ],*/
                ],
            ],
        ],
        // 南昌定制菜单
        4 => [
            0 =>
            [
                'name'   => '概览',
                'url'    => '/overview',
                'remark' => 'signal',
            ],
            1 =>
            [
                'name'   => '评估',
                'url'    => '/assessment/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口评估',
                        'url'  => 'junction',
                    ],
                    1 =>
                    [
                        'name' => '干线评估',
                        'url'  => 'road',
                    ],
                    2 =>
                    [
                        'name' => '区域评估',
                        'url'  => 'area',
                    ],
                ],
            ],
            2 =>
            [
                'name'   => '诊断',
                'url'    => '/diagnose/tendency',
            ],
            3 =>
            [
                'name'   => '优化',
                'url'    => '/optimize/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '单点时段优化',
                        'url'  => 'signal',
                    ],
                    1 =>
                    [
                        'name' => '单点绿信比优化',
                        'url'  => 'green',
                    ],
                    2 =>
                    [
                        'name' => '干线协调优化',
                        'url'  => 'coordinate',
                    ],
                ],
            ],
            4 =>
            [
                'name' => '自适应',
                'url'  => '/adaptive',
            ],
            5 =>
            [
                'name'   => '报告',
                'url'    => '/report',
                'remark' => 'signal',
            ],
            6 =>
            [
                'name'   => '管理',
                'url'    => '/manage/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '路口管理',
                        'url'  => 'configuration',
                    ],
                ],
            ],
        ],
        // 武汉菜单
        5 => [
            0 =>
            [
                'name'   => '概览',
                'url'    => '/overview',
                'remark' => 'signal',
            ],
            1 =>
            [
                'name'   => '诊断',
                'url'    => '/diagnose/tendency',
                'remark' => 'signal',
            ],
            2 =>
            [
                'name'   => '评估',
                'url'    => '/assessment/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口评估',
                        'url'  => 'junction',
                    ],
                    1 =>
                    [
                        'name' => '干线评估',
                        'url'  => 'road',
                    ],
                    2 =>
                    [
                        'name' => '区域评估',
                        'url'  => 'area',
                    ],
                ],
            ],
            3 =>
            [
                'name'   => '优化',
                'url'    => '/optimize/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '单点时段优化',
                        'url'  => 'signal',
                    ],
                    1 =>
                    [
                        'name' => '单点绿信比优化',
                        'url'  => 'green',
                    ],
                    2 =>
                    [
                        'name' => '干线协调优化',
                        'url'  => 'coordinate',
                    ],
                ],
            ],
            4 =>
            [
                'name' => '自动优化',
                'url'  => '/adaptive',
            ],
            5 =>
            [
                'name'   => '报告',
                'url'    => '/report',
                'remark' => 'signal',
            ],
            6 =>
            [
                'name'   => '管理',
                'url'    => '/manage/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '路口管理',
                        'url'  => 'configuration',
                    ],
                    1 =>
                    [
                        'name' => '信控管理',
                        'url'  => 'diagnose',
                    ],
                    /*2 =>
                            [
                                'name' => '参数管理',
                                'url'  => 'argument',
                            ],*/
                ],
            ],
        ],
        6 => [
            0 =>
            [
                'name'   => '实时监测',
                'url'    => '/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口概览',
                        'url'  => 'overview',
                    ],
                    1 =>
                    [
                        'name' => '快速路监测',
                        'url'  => 'expressway',
                    ],
                    2 =>
                    [
                        'name' => '大屏可视化',
                        'url'  => 'screen',
                    ],
                ],
            ],
            1 =>
            [
                'name'   => '辅助决策',
                'url'    => '/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口诊断',
                        'url'  => 'diagnose/tendency',
                    ],
                ],
            ],
            2 =>
            [
                'name'   => '问题治理',
                'url'    => '/optimize/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '单点时段优化',
                        'url'  => 'signal',
                    ],
                    1 =>
                    [
                        'name' => '单点绿信比优化',
                        'url'  => 'green',
                    ],
                    2 =>
                    [
                        'name' => '干线协调优化',
                        'url'  => 'coordinate',
                    ],
                    3 =>
                    [
                        'name' => '公交优先',
                        'url'  => 'bus-priority',
                    ],
                ],
            ],
            3 =>
            [
                'name'   => '效益评价',
                'url'    => '/assessment/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口评估',
                        'url'  => 'junction',
                    ],
                    1 =>
                    [
                        'name' => '干线评估',
                        'url'  => 'road',
                    ],
                    2 =>
                    [
                        'name' => '区域评估',
                        'url'  => 'area',
                    ],
                ],
            ],
            4 =>
            [
                'name'   => '报告',
                'url'    => '/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '路口报告',
                        'url'  => 'report',
                    ],
                    /*1 =>
                            [
                                'name' => '工单类报告',
                                'url'  => 'worksheet',
                            ], */
                ],
            ],
            5 =>
            [
                'name'   => '管理',
                'url'    => '/manage/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '路口管理',
                        'url'  => 'configuration',
                    ],
                    1 =>
                    [
                        'name' => '信控管理',
                        'url'  => 'diagnose',
                    ],
                    2 =>
                    [
                        'name' => '参数管理',
                        'url'  => 'argument',
                    ],
                    3 =>
                    [
                        'name' => '工单管理',
                        'url'  => 'worksheet',
                    ],
                ],
            ],
        ],
        // 济南菜单
        7 => [
            0 =>
            [
                'name'   => '概览',
                'url'    => '/overview',
                'remark' => 'signal',
            ],
            1 =>
            [
                'name'   => '诊断',
                'url'    => '/diagnose/tendency',
                'remark' => 'signal',
            ],
            2 =>
            [
                'name'   => '评估',
                'url'    => '/assessment/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口评估',
                        'url'  => 'junction',
                    ],
                    1 =>
                    [
                        'name' => '干线评估',
                        'url'  => 'road',
                    ],
                    2 =>
                    [
                        'name' => '区域评估',
                        'url'  => 'area',
                    ],
                ],
            ],
            3 =>
            [
                'name'   => '优化',
                'url'    => '/optimize/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '单点时段优化',
                        'url'  => 'signal',
                    ],
                    1 =>
                    [
                        'name' => '单点绿信比优化',
                        'url'  => 'green',
                    ],
                    2 =>
                    [
                        'name' => '干线／区域协调优化',
                        'url'  => 'coordinate',
                    ],
                ],
            ],
            4 =>
            [
                'name' => '自适应',
                'url'  => '/adaptive',
            ],
            5 =>
            [
                'name'   => '报告',
                'url'    => '/report',
                'remark' => 'signal',
            ],
            6 =>
            [
                'name'   => '管理',
                'url'    => '/manage/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '路口管理',
                        'url'  => 'configuration',
                    ],
                    1 =>
                    [
                        'name' => '信控管理',
                        'url'  => 'diagnose',
                    ],
                    /*2 =>
                            [
                                'name' => '参数管理',
                                'url'  => 'argument',
                            ],*/
                    2 =>
                    [
                        'name' => '任务管理',
                        'url'  => 'task',
                    ],
                ],
            ],
        ],
        8 => [
            0 =>
            [
                'name'   => '实时监测',
                'url'    => '/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '拥堵预警',
                        'url'  => 'overview',
                    ],
                ],
            ],
            1 =>
            [
                'name'   => '评估',
                'url'    => '/assessment/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '路口评估',
                        'url'  => 'junction',
                    ],
                ],
            ],
            2 =>
            [
                'name'   => '优化',
                'url'    => '/optimize/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '单点时段优化',
                        'url'  => 'signal',
                    ],
                    1 =>
                    [
                        'name' => '绿信比优化',
                        'url'  => 'green',
                    ],
                    2 =>
                    [
                        'name' => '干线协调优化',
                        'url'  => 'coordinate',
                    ],
                    3 =>
                    [
                        'name' => '子区划分',
                        'url'  => 'area',
                    ],
                ],
            ],
            3 =>
            [
                'name'   => '重点路段保障',
                'url'    => '/',
                'remark' => 'signal',
                'son'    =>
                [
                    0 =>
                    [
                        'name' => '快速路监测',
                        'url'  => 'expressway',
                    ],
                    1 =>
                    [
                        'name' => '动态扫描',
                        'url'  => 'adaptive',
                    ],
                ],
            ],
            4 =>
            [
                'name'   => '管理',
                'url'    => '/manage/',
                'remark' => 'signal',
                'son'    => [
                    0 =>
                    [
                        'name' => '路口管理',
                        'url'  => 'configuration',
                    ],
                ],
            ],
        ],
    ],
];
