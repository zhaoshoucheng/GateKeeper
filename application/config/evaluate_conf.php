<?php

$config['area'] = [
    [
        'key' => 'speed',
        'name' => '区域平均速度',
        'unit' => '米／秒',
    ],
    [
        'key' => 'stop_delay',
        'name' => '区域平均延误',
        'unit' => '秒',
    ],
];

$config['road'] = [
    [
        'key' => 'time',
        'name' => '干线通行时间',
        'unit' => '秒',
    ],
    [
        'key' => 'stop_time_cycle',
        'name' => '干线停车次数',
        'unit' => '次',
    ],
    [
        'key' => 'stop_delay',
        'name' => '干线平均延误',
        'unit' => '秒',
    ],
    [
        'key' => 'speed',
        'name' => '干线平均速度',
        'unit' => '千米／小时',
    ],
];

$config['area_map'] = [
    'speed' => '区域平均速度',
    'stop_delay' => '区域平均延误',
];

$config['road_map'] = [
    'stop_time_cycle' => '干线停车次数',
    'stop_delay' => '干线停车延误',
    'speed' => '干线平均速度',
    'time' => '干线通行时间',
];

$config['area_download_url_prefix'] = '/api/area/download?download_id=';

$config['excel_style'] = [
    'header' => [
        'font' => [
            'bold' => true,
            'size ' => 16,
            'color' => [
                'argb' => '00000000',
            ],
        ],
        'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => [
                'argb' => '00FFFF00',
            ],
        ],
    ],
    'title' => [
        'font' => [
            'bold' => true,
            'size ' => 12,
            'color' => [
                'argb' => '00000000',
            ],
        ],
        'fill' => [
            'type' => PHPExcel_Style_Fill::FILL_SOLID,
            'color' => [
                'argb' => '00DCDCDC',
            ],
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        ],
    ],
    'content' => [
        'borders' => [
            'allborders' => [
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => [
                    'argb' => '00000000',
                ],
            ],
        ],
        'alignment' => [
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        ],
    ],
];