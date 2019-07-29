<?php


$config['menu'] = [

    'check' => function ($user) {
        $users = [
        ];
        if(isset($_REQUEST['city_id']) && $_REQUEST['city_id']=="85"){
            return 3;
        }
        if(isset($_REQUEST['city_id']) 
            && isset($_REQUEST['conf']) 
            && $_REQUEST['city_id']=="38" 
            && $_REQUEST['conf']=="local"){
            return 4;
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
                        2 =>
                            [
                                'name' => '参数管理',
                                'url'  => 'argument',
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
                        2 =>
                            [
                                'name' => '参数管理',
                                'url'  => 'argument',
                            ],
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
                    ],
                ],
        ],
    ],
];
