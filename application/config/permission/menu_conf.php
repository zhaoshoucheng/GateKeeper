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
            [
                'name' => '概览',
                'url' => '#/overview',
                'remark ' => 'signal',
            ],
            [
                'name' => '诊断',
                'url' => '/signalpro/#/',
                'remark ' => 'signal',
                'son' => [
                    [
                        'name' => '趋势',
                        'url' => 'tendency',
                    ],
                    [
                        'name' => '详情',
                        'url' => 'diagnose',
                    ],
                ],
            ],
            [
                'name' => '评估',
                'url' => '#/assessment/',
                'remark ' => 'signal',
            ],
            [
                'name' => '优化',
                'url' => '/signalpro/#/',
                'remark ' => 'signal',
                'son' => [
                    [
                        'name' => '单点时段优化',
                        'url' => 'signal-optimize',
                    ],
                    [
                        'name' => '单点绿信比优化',
                        'url' => 'green-optimize',
                    ],
                    [
                        'name' => '干线协调优化',
                        'url' => 'coordinate-optimize',
                    ],
                ],
            ],
            [
                'name' => '报告',
                'url' => '#/report',
                'remark ' => 'signal',
            ],
            [
                    'name' => '管理',
                    'url' => '#/configuration',
                    'remark ' => 'signal',
            ],
        ],
        // 北京区域受限菜单
        2 => [
            [
                'name' => '概览',
                'url' => '#/overview',
                'remark ' => 'signal',
            ],
            [
                'name' => '诊断',
                'url' => '/signalpro/#/',
                'remark ' => 'signal',
                'son' => [
                    [
                        'name' => '趋势',
                        'url' => 'tendency',
                    ],
                    [
                        'name' => '详情',
                        'url' => 'diagnose',
                    ],
                ],
            ],
            [
                'name' => '优化',
                'url' => '/signalpro/#/',
                'remark ' => 'signal',
                'son' => [
                    [
                        'name' => '单点时段优化',
                        'url' => 'signal-optimize',
                    ],
                    [
                        'name' => '单点绿信比优化',
                        'url' => 'green-optimize',
                    ],
                    [
                        'name' => '干线协调优化',
                        'url' => 'coordinate-optimize',
                    ],
                ],
            ],
        ]
    ],
];