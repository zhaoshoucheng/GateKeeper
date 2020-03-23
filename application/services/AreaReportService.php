<?php
/**
 * 区域分析报告模块业务逻辑
 */

namespace Services;

use Services\AreaService;
use Services\ReportService;
use Services\DataService;
use Services\RoadReportService;

class AreaReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');

        $this->load->model('openCity_model');
        $this->load->model('waymap_model');
        $this->load->model('area_model');
        $this->load->model('pi_model');
        $this->load->model('traj_model');
        $this->load->model('thermograph_model');

        $this->areaService = new AreaService();
        $this->reportService = new ReportService();
        $this->dataService = new DataService();
        $this->roadReportService = new RoadReportService();
    }

    public function introduction($params) {

    	$city_id = $params['city_id'];
    	$area_id = $params['area_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];
        $datestr =  date('Y年m月d日', strtotime($start_date))."~".date('Y年m月d日', strtotime($end_date));
        if($start_date == $end_date){
            $datestr =  date('Y年m月d日', strtotime($start_date));
        }
        $tpl = "本次报告区域为%s市，分析区域包含%s等行政区域。本次报告根据%s数据对该区域进行分析。";


        $city_info = $this->openCity_model->getCityInfo($city_id);
    	if (empty($city_info)) {

    	}

    	$area_info = $this->area_model->getAreaInfo($area_id);
    	if (empty($area_info)) {

    	}

    	$area_detail = $this->areaService->getAreaDetail([
    		'city_id' => $city_id,
    		'area_id' => $area_id,
    	]);
    	$logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

    	$junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
    	if (empty($junctions_info)) {

    	}
        if($params['userapp'] == 'jinanits'){
            $dates = $this->getDateFromRange($start_date,$end_date);
            $pi = $this->pi_model->getGroupJuncAvgPiWithDates($city_id,explode(",",$logic_junction_ids) ,$dates,$this->createHours());
            $tpl = "本次报告区域为%s市，整体运行水平PI值为".round($pi,2).",分析区域包含%s等行政区域。本次报告根据%s数据对该区域进行分析。";
//            $tpl = "%s干线位于%s市%s，承担较大的交通压力，整体运行水平PI值为".round($pi,2)." 干线包含%s等重要路口。本次报告根据%s~%s数据对该干线进行分析。";
        }
    	$districts_name = implode('、', array_unique(array_column($junctions_info, 'district_name')));

    	$desc = sprintf($tpl, $city_info['city_name'], $districts_name,$datestr);

    	return [
    		'desc' => $desc,
    		'area_info' => $area_detail,
    	];
    }

    public function queryAreaDataComparison($params) {
    	$tpl = "上图展示了研究区域%s与%s路口平均延误的对比，%s该区域拥堵程度与%s相比%s。";

    	$city_id = intval($params['city_id']);
    	$area_id = $params['area_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	// $area_info = $this->area_model->getAreaInfo($area_id);
    	// if (empty($area_info)) {

    	// }

    	$area_detail = $this->areaService->getAreaDetail([
    		'city_id' => $city_id,
    		'area_id' => $area_id,
    	]);
    	$logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

    	$report_type = $this->reportService->report_type($start_date, $end_date);
    	$last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
    	$last_start_date = $last_report_date['start_date'];
    	$last_end_date = $last_report_date['end_date'];

    	$now_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => explode(',', $logic_junction_ids),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "hour",
    	], "POST", 'json');
    	$last_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($last_start_date, $last_end_date),
    		'logic_junction_ids' => explode(',', $logic_junction_ids),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "hour",
    	], "POST", 'json');

    	$now_data = array_map(function($item) {
            return [
            	'x' => $item['key'],
            	'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
     	}, $now_data[2]);
     	usort($now_data, function($a, $b) {
            return ($a['x'] < $b['x']) ? -1 : 1;
        });
     	$last_data = array_map(function($item) {
            return [
            	'x' => $item['key'],
            	'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
     	}, $last_data[2]);
     	usort($last_data, function($a, $b) {
            return ($a['x'] < $b['x']) ? -1 : 1;
        });

        $text = $this->reportService->getComparisonText(array_column($now_data, 'y'), array_column($last_data, 'y'), $report_type);

    	$desc = sprintf($tpl, $text[1], $text[2], $text[1], $text[2], $text[0]);

    	return [
    		'info' => [
    			'desc' => $desc,
    		],
    		'chart' => [
    			'title' => '平均延误对比',
				'scale_title' => '平均延误(s)',
				'series' => [
					[
          				'name' => $text[1],
          				'data' => $this->reportService->addto48($now_data),
          			],
          			[
          				'name' => $text[2],
          				'data' => $this->reportService->addto48($last_data),
          			],
				],
    		],
    	];
    }

    public function queryAreaDataComparisonNJ($params) {
        $tpl = "上图展示了研究区域总体运行状态（PI）%s与%s的对⽐，%s该区域拥堵程度与%s相比%s。";

        $city_id = intval($params['city_id']);
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];

        // $city_info = $this->openCity_model->getCityInfo($city_id);
        // if (empty($city_info)) {

        // }

        // $area_info = $this->area_model->getAreaInfo($area_id);
        // if (empty($area_info)) {

        // }

        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

        $report_type = $this->reportService->report_type($start_date, $end_date);
        $last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
        $last_start_date = $last_report_date['start_date'];
        $last_end_date = $last_report_date['end_date'];

        $now_data = $this->pi_model->getJunctionsPiByHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
        usort($now_data, function($a, $b) {
            return ($a['hour'] < $b['hour']) ? -1 : 1;
        });
        $last_data = $this->pi_model->getJunctionsPiByHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($last_start_date, $last_end_date));
        usort($last_data, function($a, $b) {
            return ($a['hour'] < $b['hour']) ? -1 : 1;
        });

        $text = $this->reportService->getComparisonText(array_column($now_data, 'y'), array_column($last_data, 'y'), $report_type, 'pi');

        $desc = sprintf($tpl, $text[1], $text[2], $text[1], $text[2], $text[0]);

        return [
            'info' => [
                'desc' => $desc,
            ],
            'chart' => [
                'title' => 'PI',
                'scale_title' => '',
                'series' => [
                    [
                        'name' => $text[1],
                        'data' => $this->reportService->addto48(array_map(function($item) {
                            return [
                                'x' => $item['hour'],
                                'y' => round($item['pi'], 2),
                            ];
                        }, $now_data)),
                    ],
                    [
                        'name' => $text[2],
                        'data' => $this->reportService->addto48(array_map(function($item) {
                            return [
                                'x' => $item['hour'],
                                'y' => round($item['pi'], 2),
                            ];
                        }, $last_data)),
                    ],
                ],
            ],
        ];
    }

    public function queryAreaQuotaDataNJ($params) {
        $tpl = "下图利用滴滴数据绘制了该区域全天24⼩时各项运⾏指标（停⻋次数、停⻋延误、行驶速度）。通过数据分析，该区域的早高峰约为%s-%s，晚高峰约为%s-%s。与平峰相比，早晚高峰的停车次数达到%.2f次/⻋/路口，停⻋延误接近%.2f秒/⻋/路口，⾏驶速度也达到%.2f千米/小时左右。与%s相比，%s停车次数%s，停车延误%s，行驶速度%s。";

        $city_id = intval($params['city_id']);
        $area_id = $params['area_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];

        // $city_info = $this->openCity_model->getCityInfo($city_id);
        // if (empty($city_info)) {

        // }

        // $area_info = $this->area_model->getAreaInfo($area_id);
        // if (empty($area_info)) {

        // }

        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $city_id,
            'area_id' => $area_id,
        ]);
        $logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

        $report_type = $this->reportService->report_type($start_date, $end_date);
        $last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
        $last_start_date = $last_report_date['start_date'];
        $last_end_date = $last_report_date['end_date'];

        $morning_peek = $this->reportService->getMorningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
        $morning_peek_hours = $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']);
        $evening_peek = $this->reportService->getEveningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
        $evening_peek_hours = $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']);
        $peek_hours = array_merge($morning_peek_hours, $evening_peek_hours);

        $now_data = $this->dataService->call("/report/GetIndex", [
            'city_id' => $city_id,
            'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
            'logic_junction_ids' => explode(',', $logic_junction_ids),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "hour",
        ], "POST", 'json');
        $now_data = $now_data[2];
        usort($now_data, function($a, $b) {
            return ($a['key'] < $b['key']) ? -1 : 1;
        });

        $last_data = $this->dataService->call("/report/GetIndex", [
            'city_id' => $city_id,
            'dates' => $this->reportService->getDatesFromRange($last_start_date, $last_end_date),
            'logic_junction_ids' => explode(',', $logic_junction_ids),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "hour",
        ], "POST", 'json');
        $last_data = $last_data[2];
        usort($last_data, function($a, $b) {
            return ($a['key'] < $b['key']) ? -1 : 1;
        });

        $now_stop_time_cycle_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $now_data));
        $last_stop_time_cycle_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $last_data));
        $stop_time_cycle_text = $this->reportService->getComparisonText(array_column($now_stop_time_cycle_data, 'y'), array_column($last_stop_time_cycle_data, 'y'), $report_type, 'stop_time_cycle');
        $now_stop_delay_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $now_data));
        $last_stop_delay_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
            ];
        }, $last_data));
        $stop_delay_text = $this->reportService->getComparisonText(array_column($now_stop_delay_data, 'y'), array_column($last_stop_delay_data, 'y'), $report_type, 'stop_delay');
        $now_speed_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
            ];
        }, $now_data));
        $last_speed_data = $this->reportService->addto48(array_map(function($item) {
            return [
                'x' => $item['key'],
                'y' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
            ];
        }, $last_data));
        $speed_text = $this->reportService->getComparisonText(array_column($now_speed_data, 'y'), array_column($last_speed_data, 'y'), $report_type, 'speed');

        $stop_time_cycle_data = [];
        $stop_delay_data = [];
        $speed_data = [];
        foreach ($now_data as $value) {
            if (! in_array($value['key'], $peek_hours)) {
                continue;
            }
            $stop_time_cycle_data[] = $value['stop_time_cycle']['value'] / $value['traj_count']['value'];
            $stop_delay_data[] = $value['stop_delay']['value'] / $value['traj_count']['value'];
            $speed_data[] = $value['speed']['value'] / $value['traj_count']['value'] * 3.6;
        }
        $stop_time_cycle = 0;
        if (count($stop_time_cycle_data) != 0) {
            $stop_time_cycle = round(array_sum($stop_time_cycle_data) / count($stop_time_cycle_data), 2);
        }
        $stop_delay = 0;
        if (count($stop_delay_data) != 0) {
            $stop_delay = round(array_sum($stop_delay_data) / count($stop_delay_data), 2);
        }
        $speed = 0;
        if (count($speed_data) != 0) {
            $speed = round(array_sum($speed_data) / count($speed_data), 2);
        }

        $desc = sprintf($tpl, $morning_peek['start_hour'], $morning_peek['end_hour'], $evening_peek['start_hour'], $evening_peek['end_hour'], $stop_time_cycle, $stop_delay, $speed, $stop_delay_text[2], $stop_delay_text[1], $stop_time_cycle_text[0], $stop_delay_text[0], $speed_text[0]);

        return [
            'info' => [
                'desc' => $desc,
            ],
            'chart' => [
                [
                    'title' => '停车次数',
                    'scale_title' => '停车次数',
                    'series' => [
                        [
                            'name' => $stop_delay_text[1],
                            'data' => $now_stop_delay_data,
                        ],
                        [
                            'name' => $stop_delay_text[2],
                            'data' => $last_stop_delay_data,
                        ],

                    ],
                ],
                [
                    'title' => '停车延误',
                    'scale_title' => '停车延误(s)',
                    'series' => [
                        [
                            'name' => $stop_time_cycle_text[1],
                            'data' => $now_stop_time_cycle_data,
                        ],
                        [
                            'name' => $stop_time_cycle_text[2],
                            'data' => $last_stop_time_cycle_data,
                        ],
                    ],
                ],
                [
                    'title' => '行驶速度',
                    'scale_title' => '行驶速度(km/h)',
                    'series' => [
                        [
                            'name' => $speed_text[1],
                            'data' => $now_speed_data,
                        ],
                        [
                            'name' => $speed_text[2],
                            'data' => $last_speed_data,
                        ],

                    ],
                ],
            ],
        ];
    }

    public function queryQuotaRank($params) {
    	$tpl = "需要注意的是PI指数的计算中考虑了对过饱和、失衡以及溢流状态的惩罚。例如，两个路口在同样的平均停车或延误时间的情况下，如果某个路口出现了过饱和、失衡或者溢流现象，则该路口的PI值会更高。";

    	$city_id = intval($params['city_id']);
    	$area_id = $params['area_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	// $area_info = $this->area_model->getAreaInfo($area_id);
    	// if (empty($area_info)) {

    	// }

    	$area_detail = $this->areaService->getAreaDetail([
    		'city_id' => $city_id,
    		'area_id' => $area_id,
    	]);
    	$logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

    	$junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
    	if (empty($junctions_info)) {

    	}
    	$junctions_map = [];
    	array_map(function($item) use(&$junctions_map) {
    		$junctions_map[$item['logic_junction_id']] = $item;
    	}, $junctions_info);

    	$report_type = $this->reportService->report_type($start_date, $end_date);
    	$last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
    	$last_start_date = $last_report_date['start_date'];
    	$last_end_date = $last_report_date['end_date'];

    	$morning_peek = $this->reportService->getMorningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
    	$evening_peek = $this->reportService->getEveningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
    	// var_dump($this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']));
    	// var_dump($this->reportService->getHoursFromRange($evening_peek['start_hour'], $morning_peek['end_hour']));

    	$morning_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date), $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']));
    	usort($morning_pi_data, function($a, $b) {
    		return $a['pi'] > $b['pi'] ? -1 : 1;
    	});
    	$morning_pi_data = array_slice($morning_pi_data, 0, 20);
    	$morning_last_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($last_start_date, $last_end_date), $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']));
        // print_r($morning_last_pi_data);
    	$morning_last_pi_data_rank = [];
    	for ($i = 0; $i < count($morning_last_pi_data); $i++) {
    		$morning_last_pi_data_rank[$morning_last_pi_data[$i]['logic_junction_id']] = $i + 1;
    	}
    	$morning_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => array_column($morning_pi_data, 'logic_junction_id'),
    		'hours' => $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
    	], "POST", 'json');
    	$morning_data_map = [];
    	array_map(function($item) use(&$morning_data_map) {
    		$morning_data_map[$item['key']] = [
    			'stop_delay' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
    			'stop_time_cycle' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
    			'speed' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
    		];
     	}, $morning_data[2]);

    	$evening_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date), $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']));
    	usort($evening_pi_data, function($a, $b) {
    		return $a['pi'] > $b['pi'] ? -1 : 1;
    	});
    	$evening_pi_data = array_slice($evening_pi_data, 0, 20);
    	$evening_last_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($last_start_date, $last_end_date), $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']));
        // print_r($evening_last_pi_data);
    	$evening_last_pi_data_rank = [];
    	for ($i = 0; $i < count($evening_last_pi_data); $i++) {
    		$evening_last_pi_data_rank[$evening_last_pi_data[$i]['logic_junction_id']] = $i + 1;
    	}
    	$evening_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => array_column($evening_pi_data, 'logic_junction_id'),
    		'hours' => $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
    	], "POST", 'json');
    	$evening_data_map = [];
    	array_map(function($item) use(&$evening_data_map) {
    		$evening_data_map[$item['key']] = [
    			'stop_delay' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
    			'stop_time_cycle' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
    			'speed' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
    		];
     	}, $evening_data[2]);

    	return [
    		'morning_peek' => [
    			'quota_table_desc' => $tpl,
    			'quota_table_data' => array_map(function($item) use($junctions_map, $morning_last_pi_data_rank, $morning_data_map) {
    				return [
    					'logic_junction_id' => $item['logic_junction_id'],
    					'name' => $junctions_map[$item['logic_junction_id']]['name'],
    					'last_rank' => isset($morning_last_pi_data_rank[$item['logic_junction_id']]) ? $morning_last_pi_data_rank[$item['logic_junction_id']] : '-',
    					'stop_delay' => $morning_data_map[$item['logic_junction_id']]['stop_delay'],
    					'stop_time_cycle' => $morning_data_map[$item['logic_junction_id']]['stop_time_cycle'],
    					'speed' => $morning_data_map[$item['logic_junction_id']]['speed'],
    					'PI' => round($item['pi'], 2),
    				];
    			}, $morning_pi_data),
    		],
    		'evening_peek' => [
    			'quota_table_desc' => $tpl,
    			'quota_table_data' => array_map(function($item) use($junctions_map, $evening_last_pi_data_rank, $evening_data_map) {
    				return [
    					'logic_junction_id' => $item['logic_junction_id'],
    					'name' => $junctions_map[$item['logic_junction_id']]['name'],
    					'last_rank' => isset($evening_last_pi_data_rank[$item['logic_junction_id']]) ? $evening_last_pi_data_rank[$item['logic_junction_id']] : '-',
    					'stop_delay' => $evening_data_map[$item['logic_junction_id']]['stop_delay'],
    					'stop_time_cycle' => $evening_data_map[$item['logic_junction_id']]['stop_time_cycle'],
    					'speed' => $evening_data_map[$item['logic_junction_id']]['speed'],
    					'PI' => round($item['pi'], 2),
    				];
    			}, $evening_pi_data),
    		],
    	];
    }

    public function queryAreaCongestion($params) {
    	$tpl = "下图展示了分析区域%s高峰延误排名前%d的路口。其中%s%s高峰拥堵情况严重。";

    	$city_id = intval($params['city_id']);
    	$area_id = $params['area_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	// $area_info = $this->area_model->getAreaInfo($area_id);
    	// if (empty($area_info)) {

    	// }

    	$area_detail = $this->areaService->getAreaDetail([
    		'city_id' => $city_id,
    		'area_id' => $area_id,
    	]);
    	$logic_junction_ids = implode(',', array_column($area_detail['junction_list'], 'logic_junction_id'));

    	$junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
    	if (empty($junctions_info)) {

    	}
    	$junctions_map = [];
    	array_map(function($item) use(&$junctions_map) {
    		$junctions_map[$item['logic_junction_id']] = $item;
    	}, $junctions_info);

    	$morning_peek = $this->reportService->getMorningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
    	$evenint_peek = $this->reportService->getEveningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));

    	$morning_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => explode(',', $logic_junction_ids),
    		'hours' => $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
    	], "POST", 'json');
    	$evening_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => explode(',', $logic_junction_ids),
    		'hours' => $this->reportService->getHoursFromRange($evenint_peek['start_hour'], $evenint_peek['end_hour']),
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "logic_junction_id",
    	], "POST", 'json');

    	$morning_data = array_map(function($item) use($junctions_map) {
            return [
            	'x' => $item['key'],
            	'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            	'name' => $junctions_map[$item['key']]['name'],
            	'lng' => $junctions_map[$item['key']]['lng'],
            	'lat' => $junctions_map[$item['key']]['lat'],
            ];
     	}, $morning_data[2]);
     	usort($morning_data, function($a, $b) {
            return ($a['y'] > $b['y']) ? -1 : 1;
        });
        $morning_data = array_slice($morning_data, 0, 10);
        $morning_junction_names = array_map(function($item) use($junctions_map) {
        	return $item['name'];
        }, array_slice($morning_data, 0, 3));

     	$evening_data = array_map(function($item) use($junctions_map) {
            return [
            	'x' => $item['key'],
            	'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
            	'name' => $junctions_map[$item['key']]['name'],
            	'lng' => $junctions_map[$item['key']]['lng'],
            	'lat' => $junctions_map[$item['key']]['lat'],
            ];
     	}, $evening_data[2]);
     	usort($evening_data, function($a, $b) {
            return ($a['y'] > $b['y']) ? -1 : 1;
        });
        $evening_data = array_slice($evening_data, 0, 10);
        $evening_junction_names = array_map(function($item) use($junctions_map) {
        	return $item['name'];
        }, array_slice($evening_data, 0, 3));

    	return [
    		'morning_peek' => [
	    		'info' => [
	    			'desc' => sprintf($tpl, '早', count($morning_data), implode(',', $morning_junction_names), '早'),
	    		],
	    		'center' => [
	    			'lng' => round(array_sum(array_column($morning_data, 'lng')) / count(array_column($morning_data, 'lng')), 5),
	    			'lat' => round(array_sum(array_column($morning_data, 'lat')) / count(array_column($morning_data, 'lat')), 5),
	    		],
	    		'chart' => [
	    			'title' => '平均延误对比',
					'scale_title' => '平均延误(s)',
					'series' => [
						'name' => '早高峰',
          				'data' => $morning_data,
					],
	    		],
    		],
    		'evenint_peek' => [
    			'info' => [
	    			'desc' => sprintf($tpl, '晚', count($evening_data), implode(',', $evening_junction_names), '晚'),
	    		],
	    		'center' => [
	    			'lng' => round(array_sum(array_column($evening_data, 'lng')) / count(array_column($evening_data, 'lng')), 5),
	    			'lat' => round(array_sum(array_column($evening_data, 'lat')) / count(array_column($evening_data, 'lat')), 5),
	    		],
	    		'chart' => [
	    			'title' => '平均延误对比',
					'scale_title' => '平均延误(s)',
					'series' => [
          				'name' => '晚高峰',
          				'data' => $evening_data,
					],
	    		],
    		],
    	];
    }

    public function queryTopPI($params) {
    	$city_id = intval($params['city_id']);
    	$area_id = $params['area_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	// $area_info = $this->area_model->getAreaInfo($area_id);
    	// if (empty($area_info)) {

    	// }

    	$area_detail = $this->areaService->getAreaDetail([
    		'city_id' => $city_id,
    		'area_id' => $area_id,
    	]);
    	$logic_junction_ids =array_column($area_detail['junction_list'], 'logic_junction_id');

    	$morning_peek = $this->reportService->getMorningPeekRange($city_id, $logic_junction_ids, $this->reportService->getDatesFromRange($start_date, $end_date));
    	$morning_peek_hours = $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']);
    	$evening_peek = $this->reportService->getEveningPeekRange($city_id, $logic_junction_ids, $this->reportService->getDatesFromRange($start_date, $end_date));
    	$evening_peek_hours = $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']);
    	$peek_hours = array_merge($morning_peek_hours, $evening_peek_hours);

    	$morning_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, $logic_junction_ids, $this->reportService->getDatesFromRange($start_date, $end_date), $peek_hours);
    	usort($morning_pi_data, function($a, $b) {
    		return $a['pi'] > $b['pi'] ? -1 : 1;
    	});
    	return array_slice(array_column($morning_pi_data, 'logic_junction_id'), 0, 3);
    }

    private function getDateFromRange($startdate, $enddate)
    {
        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);

        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;
        // 保存每天日期
        $date = [];
        for ($i = 0; $i < $days; $i++) {
            $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
        }
        return $date;
    }

    private function getTimeFromRange($st,$et,$step){
        $stimestamp = strtotime($st);
        $etimestamp = strtotime($et);
        $hours=[];
        for($i = $stimestamp;$i<=$etimestamp;$i+=$step*60){
            $hours[] = date('H:i', $i);
        }

        return $hours;
    }


    //时间前后取整
    private function roundingtime($time){
        $hour = date("H",strtotime($time));
        $min = date("i",strtotime($time));
        if($min < 30){
            $min = "00";
        }else{
            $min = "30";
        }
        return $hour.":".$min;
    }

    public function queryAreaAlarm($cityID,$areaID,$startTime,$endTime,$morningRushTime,$eveningRushTime){
        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $cityID,
            'area_id' => $areaID,
        ]);

        $junctionList =array_column($area_detail['junction_list'], 'logic_junction_id');
//        $junctionList  = explode(",",$roadInfo['logic_junction_ids']);
        $juncNameMap=[];
        $rd=[];
        $rd['junction_info']=[];
        foreach ($area_detail['junction_list'] as $k => $j){
            $juncNameMap[$j['name']] = $j['logic_junction_id'];
            $rd['junctions_info'][$j['logic_junction_id']] = ['name'=>$j['name']];
        }

        $alarmInfo = $this->diagnosisNoTiming_model->getJunctionAlarmHoursData($cityID, $junctionList, $this->getDateFromRange($startTime,$endTime));

        //1: 过饱和 2: 溢流 3:失衡
        $imbalance=[];
        $oversaturation=[];
        $spillover=[];
        //路口报警统计
        foreach ($alarmInfo as $ak => $av){
            //过滤报警不足5分钟的
            if(strtotime($av['end_time'])-strtotime($av['start_time']) < 5*60){
                continue;
            }
            switch ($av['type']){
                case 1:
                    $oversaturation[$av['logic_junction_id']][]=$av['start_time'];
                    break;
                case 2:
                    $spillover[$av['logic_junction_id']][]=$av['start_time'];
                    break;
                case 3:
                    $imbalance[$av['logic_junction_id']][]=$av['start_time'];
                    break;
            }
        }
        $imbalance = $this->sortSlice($imbalance);
        $oversaturation = $this->sortSlice($oversaturation);
        $spillover = $this->sortSlice($spillover);


        //初始化表格
        $initChartList = $this->roadReportService->initRoadAlarmChart($rd,$morningRushTime,$eveningRushTime,"区域");
        $fillChartData = $this->roadReportService->fillRoadAlarmChart($initChartList,$imbalance,$oversaturation,$spillover,$juncNameMap);


        return $fillChartData;
    }

    //各项指标数组进行排序
    private function sortSlice($orimap){
        $name = [];
        $count=[];
        foreach ($orimap as  $k=>$v){
            $name[] = $k;
            $count[] = count($v);
        }
        array_multisort($count,SORT_DESC,$name);
        $newMap=[];
        foreach ($count as $k => $v){
            $newMap[$name[$k]] = $orimap[$name[$k]];
        }
        return $newMap;

    }

    private function createHours(){
        $hours=[];
        for($i=strtotime("00:00");$i<=strtotime("23:30");$i=$i+1800){
            $hours[] = date("H:i",$i);
        }
        return $hours;
    }
    public function QueryAreaQuotaInfo($cityID,$roadID,$start_time,$end_time){
        $area_detail = $this->areaService->getAreaDetail([
            'city_id' => $cityID,
            'area_id' => $roadID,
        ]);

        $junctionIDs =array_column($area_detail['junction_list'], 'logic_junction_id');

        $dates = $this->getDateFromRange($start_time,$end_time);

        $roadQuotaData = $this->area_model->getJunctionsAllQuotaEs($dates,$junctionIDs,$cityID);


        $PiDatas = $this->pi_model->getGroupJuncPiWithDatesHours($cityID,$junctionIDs,$dates,$this->createHours());
        foreach ($PiDatas as $pk =>$pv){
            foreach ($roadQuotaData as $rk=>$rv){
                if($pk==$rv['hour']){
                    $roadQuotaData[$rk]['pi']=$pv;
                    break;
                }
            }
        }


        return $roadQuotaData;
    }

    public function saveThermograph($data,$res){
        if($res == false){
            return false;
        }
        $type=0;
        $res = json_decode($res,true);
        if($res['errorCode']!=0){
            return false;
        }
        if(isset($res['data']['figureTitle'])){
            $type=1;
        }
        if(isset($res['data']['videoTitle'])){
            $type=2;
        }
        $insertData=[
            'city_id'=>$data['city_id'],
            'area_id'=>$data['area_id'],
            'date'=>$data['date'],
            'hour'=>$data['hour'],
            'task_id'=>$res['data']['taskId'],
            'type'=>$type
        ];
        $ret =  $this->thermograph_model->save($insertData);
        return $ret;
    }

    //查询未知状态的任务
    public function queryUnreadyTask(){
        $where = [
            'status < '=>5
        ];
        $tasks = $this->thermograph_model->query($where);
        return array_column($tasks,'task_id');
    }
    //更新任务的状态
    public function updateUnreadyTasks($taskID,$status){
        return $this->thermograph_model->updateUnreadyTask($taskID,$status);
    }

    //查询热力图
    public function queryThermograph($url,$taskID,$morningRushTime){
        $ret = httpGET($url."?taskId=".$taskID);
        if($ret == false){
            return [];
        }

        $ret = json_decode($ret,true);
        if($ret['errorCode']!=0){
            return [];
        }
        $gifts = $ret['data']['giftUrls'];
        //根据早高峰过滤
        $st = $morningRushTime['s'];
        $et = $morningRushTime['e'];
        $glist = [];
        $flag = false;
        foreach ($gifts as $g){
            if(strstr($g,str_replace(":","",$st)."-")){
                $glist[] =$g;
                $flag = true;
            }elseif(strstr($g,"-".str_replace(":","",$et))){
                $glist[] =$g;
                break;
            }elseif($flag){
                $glist[] =$g;
            }
        }

        //临时兼容视频模块
        if(count($glist) == 0 && count($gifts)>0){
            $glist[] = $gifts[0];
        }
        return $glist;
    }


    public function queryThermographTaskID($cityID,$areaID,$startTime,$endTime,$type){
        $date = $startTime;
        if($startTime == $endTime){
            $date = $startTime;
        }else{
            $ds = $this->getDateFromRange($startTime,$endTime);
            foreach ($ds as $v){
                $week = date("w",strtotime($v));
                if($week == 1){
                    $date=$v;
                    break;
                }
            }
        }


        $query=[
            'city_id'=>$cityID,
            'area_id'=>$areaID,
            'date'=>$date,
            'type'=>$type,
        ];

        $ret = $this->thermograph_model->query($query);

        if(empty($ret)){
            return false;
        }
        $taskID = $ret[0]['task_id'];

        return ['task_id'=>$taskID,'date'=>$date];

    }

}