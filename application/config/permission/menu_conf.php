<?php



$config['menu'] = [

    'check' => function ($user) {
        $users = [
            '18611198971',
            '13718307981',
            '13810015784',
            '13261208949',
            '13810530502',
            '18612588551',
            '13911697834'
        ];

        if(in_array($user, $users)) {
            return 2;
        }

        return 1;
    },

    'menuList' => [
        // 全部菜单
        1 => [
            0 =>
                [
                    'name' => '概览',
                    'url' => '/signalpro/overview',
                    'remark ' => 'signal',
                ],
            1 =>
                [
                    'name' => '诊断',
                    'url' => '/signalpro/diagnose/',
                    'remark ' => 'signal',
                    'son' =>
                        [
                            0 =>
                                [
                                    'name' => '趋势',
                                    'url' => 'tendency',
                                ],
                            1 =>
                                [
                                    'name' => '详情',
                                    'url' => 'index',
                                ],
                        ],
                ],
            2 =>
                [
                    'name' => '评估',
                    'url' => '/signalpro/assessment/',
                    'remark ' => 'signal',
                    'son' =>
                        [
                            0 =>
                                [
                                    'name' => '路口评估',
                                    'url' => 'junction',
                                ],
                            1 =>
                                [
                                    'name' => '干线评估',
                                    'url' => 'road',
                                ],
                            2 =>
                                [
                                    'name' => '区域评估',
                                    'url' => 'area',
                                ],
                        ],
                ],
            3 =>
                [
                    'name' => '优化',
                    'url' => '/signalpro/optimize/',
                    'remark ' => 'signal',
                    'son' =>
                        [
                            0 =>
                                [
                                    'name' => '单点时段优化',
                                    'url' => 'signal',
                                ],
                            1 =>
                                [
                                    'name' => '单点绿信比优化',
                                    'url' => 'green',
                                ],
                            2 =>
                                [
                                    'name' => '干线协调优化',
                                    'url' => 'coordinate',
                                ],
                        ],
                ],
            4 =>
                [
                    'name' => '自适应',
                    'url' => '/signalpro/adaptive',
                ],
            5 =>
                [
                    'name' => '报告',
                    'url' => '/signalpro/report',
                    'remark ' => 'signal',
                ],
            6 =>
                [
                    'name' => '管理',
                    'url' => '/signalpro/manage/configuration',
                    'remark ' => 'signal',
                ],
        ],
        // 北京区域受限菜单
        2 => [
            [
                [
                    'name' => '概览',
                    'url' => '/signalpro/overview',
                    'remark ' => 'signal',
                ],
                [
                    'name' => '诊断',
                    'url' => '/signalpro/diagnose/',
                    'remark ' => 'signal',
                    'son' =>
                        [
                            0 =>
                                [
                                    'name' => '趋势',
                                    'url' => 'tendency',
                                ],
                            1 =>
                                [
                                    'name' => '详情',
                                    'url' => 'index',
                                ],
                        ],
                ],
                [
                    'name' => '优化',
                    'url' => '/signalpro/optimize/',
                    'remark ' => 'signal',
                    'son' =>
                        [
                            0 =>
                                [
                                    'name' => '单点时段优化',
                                    'url' => 'signal',
                                ],
                            1 =>
                                [
                                    'name' => '单点绿信比优化',
                                    'url' => 'green',
                                ],
                            2 =>
                                [
                                    'name' => '干线协调优化',
                                    'url' => 'coordinate',
                                ],
                        ],
                ]
            ]
        ]
    ],
];
