<?php
/**
 * 路口分析报告模块业务逻辑
 */

namespace Services;

use Services\ReportService;
use Services\DataService;

class JunctionReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');
        $this->load->model('diagnosisNoTiming_model');

        $this->load->model('openCity_model');
        $this->load->model('waymap_model');
        $this->load->model('pi_model');

        $this->reportService = new ReportService();
        $this->dataService = new DataService();
    }

    public function introduction($params) {
    	$tpl = "本次报告分析路口位于%s市%s。本次报告根据%s数据对该路口进行分析。";

    	$city_id = $params['city_id'];
    	$logic_junction_id = $params['logic_junction_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];
        $datestr =  date('Y年m月d日', strtotime($start_date))."~".date('Y年m月d日', strtotime($end_date));
        if($start_date == $end_date){
            $datestr =  date('Y年m月d日', strtotime($start_date));
        }

    	$city_info = $this->openCity_model->getCityInfo($city_id);
    	if (empty($city_info)) {

    	}

    	$junction_info = $this->waymap_model->getJunctionInfo($logic_junction_id);
    	if (empty($junction_info)) {

    	} else {
    		$junction_info = $junction_info[0];
    	}

    	$desc = sprintf($tpl, $city_info['city_name'], $junction_info['district_name'], $datestr);

    	return [
    		'desc' => $desc,
    		'junction_info' => $junction_info,
    	];
    }

    public function queryJuncDataComparison($params) {
    	$tpl = "上图展示了分析路口%s与%s路口平均延误的对比，%s路口拥堵程度与%s相比%s。";

    	$city_id = intval($params['city_id']);
    	$logic_junction_id = $params['logic_junction_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	// $junction_info = $this->waymap_model->getJunctionInfo($logic_junction_id);
    	// if (empty($junction_info)) {

    	// } else {
    	// 	$junction_info = $junction_info[0];
    	// }

    	$report_type = $this->reportService->report_type($start_date, $end_date);
    	$last_report_date = $this->reportService->last_report_date($start_date, $end_date, $report_type);
    	$last_start_date = $last_report_date['start_date'];
    	$last_end_date = $last_report_date['end_date'];

    	$now_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => [$logic_junction_id],
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(traj_count) as traj_count",
            "group_by" => "hour",
    	], "POST", 'json');
    	$last_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($last_start_date, $last_end_date),
    		'logic_junction_ids' => [$logic_junction_id],
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

    public function queryJunctionDataComparisonNJ($params) {
        $tpl = "上图展示了研究路口总体运行状态（PI）%s与%s的对⽐，%s该路口拥堵程度与%s相比%s。";

        $city_id = intval($params['city_id']);
        $logic_junction_id = $params['logic_junction_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];

        // $city_info = $this->openCity_model->getCityInfo($city_id);
        // if (empty($city_info)) {

        // }

        // $junction_info = $this->waymap_model->getJunctionInfo($logic_junction_id);
        // if (empty($junction_info)) {

        // } else {
        //  $junction_info = $junction_info[0];
        // }

        $logic_junction_ids = $logic_junction_id;

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

    public function queryJunctionQuotaDataNJ($params) {
        $tpl = "下图利用滴滴数据绘制了该路口全天24⼩时各项运⾏指标（⻋均停⻋次数、⻋均停⻋延误、⻋均行驶速度）。通过数据分析，该路口的早高峰约为%s-%s，晚高峰约为%s-%s。与平峰相比，早晚高峰的停车次数达到%.2f次/⻋/路口，停⻋延误接近%.2f秒/⻋/路口，⾏驶速度也达到%.2f千米/小时左右。与%s相比，%s停车次数%s，停车延误%s，行驶速度%s。";

        $city_id = intval($params['city_id']);
        $logic_junction_id = $params['logic_junction_id'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];

        // $city_info = $this->openCity_model->getCityInfo($city_id);
        // if (empty($city_info)) {

        // }

        // $junction_info = $this->waymap_model->getJunctionInfo($logic_junction_id);
        // if (empty($junction_info)) {

        // } else {
        //  $junction_info = $junction_info[0];
        // }

        $logic_junction_ids = $logic_junction_id;

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

    public function queryJuncQuotaData($params) {
    	$tpl = "下图利用滴滴数据绘制了该路口全天24小时各项运行指标（停车次数、停车延误、行驶速度、PI）。通过数据分析，该路口的早高峰约为%s-%s，晚高峰约为%s-%s。与平峰相比，早晚高峰的停车次数达到%.2f次/车/路口，停车延误接近%.2f秒/车/路口，行驶速度也达到%.2f千米/小时左右。";
    	$instructions = "报告采用综合评估指数（PI）来分析路口整体及各维度交通运行情况XXXX";

    	$city_id = intval($params['city_id']);
    	$logic_junction_id = $params['logic_junction_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	// $junction_info = $this->waymap_model->getJunctionInfo($logic_junction_id);
    	// if (empty($junction_info)) {

    	// } else {
    	// 	$junction_info = $junction_info[0];
    	// }

    	$morning_peek = $this->reportService->getMorningPeekRange($city_id, [$logic_junction_id], $this->reportService->getDatesFromRange($start_date, $end_date));
    	$morning_peek_hours = $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']);
    	$evening_peek = $this->reportService->getEveningPeekRange($city_id, [$logic_junction_id], $this->reportService->getDatesFromRange($start_date, $end_date));
    	$evening_peek_hours = $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']);
    	$peek_hours = array_merge($morning_peek_hours, $evening_peek_hours);

    	$index_data = $this->dataService->call("/report/GetIndex", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => [$logic_junction_id],
            "select" => "sum(stop_delay * traj_count) AS stop_delay, sum(stop_time_cycle * traj_count) AS stop_time_cycle, sum(speed * traj_count) AS speed, sum(traj_count) as traj_count",
            "group_by" => "hour",
    	], "POST", 'json');
    	$index_data = $index_data[2];
    	usort($index_data, function($a, $b) {
            return ($a['key'] < $b['key']) ? -1 : 1;
        });

    	$pi_data = $this->pi_model->getJunctionsPiByHours($city_id, [$logic_junction_id], $this->reportService->getDatesFromRange($start_date, $end_date));
    	usort($pi_data, function($a, $b) {
            return ($a['hour'] < $b['hour']) ? -1 : 1;
        });

    	$stop_delay_data = [];
    	$stop_time_cycle_data = [];
    	$speed_data = [];
    	foreach ($index_data as $value) {
    		if (! in_array($value['key'], $peek_hours)) {
    			continue;
    		}
    		$stop_delay_data[] = $value['stop_delay']['value'] / $value['traj_count']['value'];
    		$stop_time_cycle_data[] = $value['stop_time_cycle']['value'] / $value['traj_count']['value'];
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

    	$desc = sprintf($tpl, $morning_peek['start_hour'], $morning_peek['end_hour'], $evening_peek['start_hour'], $evening_peek['end_hour'], $stop_time_cycle, $stop_delay, $speed);

    	return [
    		'info' => [
    			'desc' => $desc,
    			'instructions' => $instructions,
    		],
    		'charts' => [
    			[
	    			'title' => '停车次数',
					'scale_title' => '停车次数',
					'series' => [
						'name' => "",
						'data' => $this->reportService->addto48(array_map(function($item) {
							return [
								'x' => $item['key'],
								'y' => round($item['stop_time_cycle']['value'] / $item['traj_count']['value'], 2),
							];
						}, $index_data)),
					],
    			],
    			[
	    			'title' => '停车延误',
					'scale_title' => '停车延误(s)',
					'series' => [
						'name' => "",
						'data' => $this->reportService->addto48(array_map(function($item) {
							return [
								'x' => $item['key'],
								'y' => round($item['stop_delay']['value'] / $item['traj_count']['value'], 2),
							];
						}, $index_data)),
					],
    			],
    			[
	    			'title' => '行驶速度',
					'scale_title' => '行驶速度(km/h)',
					'series' => [
						'name' => "",
						'data' => $this->reportService->addto48(array_map(function($item) {
							return [
								'x' => $item['key'],
								'y' => round($item['speed']['value'] / $item['traj_count']['value'] * 3.6, 2),
							];
						}, $index_data)),
					],
    			],
    			[
	    			'title' => 'PI',
					'scale_title' => '',
					'series' => [
						'name' => "",
						'data' => $this->reportService->addto48(array_map(function($item) {
							return [
								'x' => $item['hour'],
								'y' => round($item['pi'], 2),
							];
						}, $pi_data)),
					],
    			],
    		],
    	];
    }

    public function queryJuncInfo($logicJunctionID){
        return $this->waymap_model->getJunctionDetail($logicJunctionID);
    }

    public function queryJuncQuotaDetail($cityID,$logicJunctionID,$startTime,$endTime){
        //查询路网flow信息
        $flowsMovement = $this->waymap_model->getFlowMovement($cityID, $logicJunctionID, "all", 1);
        $flowsMovement = array_map(function ($v) {
            $v = $this->adjustPhase($v);
            return $v;
        }, $flowsMovement);
        $flowPhases = array_column($flowsMovement,"phase_name","logic_flow_id");
        //查询指标详情
        $quotaInfo = $this->diagnosisNoTiming_model->getFlowAllQuotaList($cityID,$logicJunctionID,$startTime,$endTime);

        $ret = [
            "flow_info"=>$flowPhases,
            "quota_info"=>$quotaInfo
        ];
        return $ret;

    }
    /**
     * 修改路口的flow，校准 phase_id 和 phase_name
     *
     * @param $flows
     *
     * @return array
     */
    private function adjustPhase($flow)
    {
        $phaseId = phase_map($flow['in_degree'], $flow['out_degree']);
        $phaseName = phase_name($phaseId);
        $flow['phase_id'] = $phaseId;
        $flow['phase_name'] = $phaseName;
        $flow['sort_key'] = phase_sort_key($flow['in_degree'], $flow['out_degree']);
        return $flow;
    }
    private function sortAndFillHour($data){
        $newData=[];
        //初始化24小时的时段
        for($i=0;$i<48;$i++){
            $newData[] = [
                "x"=>date("H:i",strtotime("00:00")+$i*30*60),
                "y"=>0,
            ];
        }
        foreach ($newData as $k => $v){
            foreach ($data as $d){
                if($d['x']==$v['x']){
                    $newData[$k]['y']=$d['y'];
                }
            }
        }
        return $newData;
    }

    //es数据转换为表格
    public function trans2Chart($flowQuota,$flowInfo){
        $stopTimeChartData =[
            "quotaname"=>"停车次数",
            "quotakey"=>"stop_time_cycle",
            "analysis"=>"",
            "flowlist"=>[],
        ];
        $speedChartData =[
            "quotaname"=>"行驶速度",
            "quotakey"=>"speed",
            "analysis"=>"",
            "flowlist"=>[],
        ];
        $stopDelayChartData =[
            "quotaname"=>"停车延误",
            "quotakey"=>"stop_delay",
            "analysis"=>"",
            "flowlist"=>[],
        ];
        foreach ($flowQuota as $fk => $fv){
            $stopTimeCycleChart = [];
            $speedCycleChart = [];
            $stopDelayCycleChart = [];
            foreach ($fv as $h => $series){
                $stopTimeCycleChart[] = [
                    "x"=>$h,
                    "y"=>round($series['stop_time_cycle']/$series['traj_count'],2)
                ];
                $speedCycleChart[] = [
                    "x"=>$h,
                    "y"=>round($series['speed']/$series['traj_count'],2)
                ];
                $stopDelayCycleChart[] = [
                    "x"=>$h,
                    "y"=>round($series['stop_delay']/$series['traj_count'],2)
                ];
            }

            $stopTimeChartData['flowlist'][]=[
                "logic_flow_id"=>$fk,
                "chart"=>[
                    "title"=>$flowInfo[$fk],
                    "scale_title"=>"次/车",
                    "series"=>[["name"=>"","data"=>$this->sortAndFillHour($stopTimeCycleChart)]],
                ],
            ];
            $speedChartData['flowlist'][]=[
                "logic_flow_id"=>$fk,
                "chart"=>[
                    "title"=>$flowInfo[$fk],
                    "scale_title"=>"公里/小时",
                    "series"=>[["name"=>"","data"=>$this->sortAndFillHour($speedCycleChart)]]
                ],
            ];
            $stopDelayChartData['flowlist'][]=[
                "logic_flow_id"=>$fk,
                "chart"=>[
                    "title"=>$flowInfo[$fk],
                    "scale_title"=>"秒",
                    "series"=>[["name"=>"","data"=>$this->sortAndFillHour($stopDelayCycleChart)]]
                ],
            ];
        }

        $chartDataList=[];
        $chartDataList[]= $stopTimeChartData;
        $chartDataList[]= $speedChartData;
        $chartDataList[]= $stopDelayChartData;

        return $chartDataList;
    }

    //对表格数据进行分析
    public function chartAnalysis($chartData){
        foreach ($chartData as $k=> $v){
            switch ($v['quotakey']){
                case "stop_time_cycle":
                    $maxQuotaFlow = $this->queryMaxQuotaFlow($chartData[$k]['flowlist']);
                    if($maxQuotaFlow != false){
                        $chartData[$k]["analysis"]="该路口在评估日期内".$maxQuotaFlow['max_flow']."方向的停车次数最大,其中在".$maxQuotaFlow['max_range'][0]."-".end($maxQuotaFlow['max_range'])."时段内的停车次数最大,需重点关注。";
                    }
                    break;
                case "speed":
                    $minQuotaFlow = $this->queryMinQuotaFlow($chartData[$k]['flowlist']);
                    if($minQuotaFlow != false){
                        $chartData[$k]["analysis"]="该路口在评估日期内".$minQuotaFlow['min_flow']."方向的行驶速度最小,其中在".$minQuotaFlow['min_range'][0]."-".end($minQuotaFlow['min_range'])."时段内的行驶速度最小,需重点关注。";
                    }
                    break;
                case "stop_delay":
                    $maxQuotaFlow = $this->queryMaxQuotaFlow($chartData[$k]['flowlist']);
                    if($maxQuotaFlow != false){
                        $chartData[$k]["analysis"]="该路口在评估日期内".$maxQuotaFlow['max_flow']."方向的停车延误最大,其中在".$maxQuotaFlow['max_range'][0]."-".end($maxQuotaFlow['max_range'])."时段内的停车延误最大,需重点关注。";
                    }
                    break;
            }
        }
        return $chartData;
    }

    private function queryMaxQuotaFlow($flowlist){
        if(count($flowlist) == 0){
            return false;
        }
        //每个时间点的桶
        $bucket=[];
        foreach ($flowlist as $f => $v){
            foreach ($v['chart']['series'][0]['data'] as $s){
                if(!isset($bucket[$s['x']])){
                    $bucket[$s['x']] = [
                        'flowname'=>"",
                        'value'=> 0
                    ];
                }
                if ($s['y'] > $bucket[$s['x']]['value'] ){
                    $bucket[$s['x']] = [
                        'flowname'=>$v['chart']['title'],
                        'value'=>$s['y']
                    ];
                }
            }
        }
        $count=[];
        $maxRange=[];
        $tempRange=[];
        foreach ($bucket as $k=> $v){
            if(!isset( $count[$v['flowname']])){
                $count[$v['flowname']]=0;
            }
            $count[$v['flowname']]+=1;
        }
        $maxFlow = array_keys($count, max($count))[0];
        foreach ($bucket as $k=>$v){
            if($v['flowname'] == $maxFlow){
                $tempRange[] = $k;
            }else{
                if(count($tempRange)>count($maxRange)) {
                    $maxRange = $tempRange;
                    $tempRange=[];
                }
            }
        }
        if(count($tempRange)>0 && count($maxRange)==0){
            $maxRange = $tempRange;
        }

        return ["max_flow"=>$maxFlow,"max_range"=>$maxRange];
    }
    //查询指标最高的flow
    private function queryMinQuotaFlow($flowlist){
        if(count($flowlist) == 0){
            return false;
        }
        $bucket=[];
        foreach ($flowlist as $f => $v){
            foreach ($v['chart']['series'][0]['data'] as $s){
                if(!isset($bucket[$s['x']])){
                    $bucket[$s['x']] = [
                        'flowname'=>"",
                        'value'=>999999
                    ];
                }
                if ($s['y'] < $bucket[$s['x']]['value']){
                    $bucket[$s['x']] = [
                        'flowname'=>$v['chart']['title'],
                        'value'=>$s['y']
                    ];
                }
            }
        }
        $count=[];
        $minRange=[];
        $tempRange=[];
        foreach ($bucket as $k=> $v){
            if(!isset( $count[$v['flowname']])){
                $count[$v['flowname']]=0;
            }
            $count[$v['flowname']]+=1;
        }
        $mixFlow = array_keys($count, min($count))[0];
        foreach ($bucket as $k=>$v){
            if($v['flowname'] == $mixFlow){
                $tempRange[] = $k;
            }else{
                if(count($tempRange)>count($minRange)) {
                    $minRange = $tempRange;
                    $tempRange=[];
                }
            }
        }
        if(count($tempRange)>0 && count($minRange)==0){
            $minRange = $tempRange;
        }

        return ["min_flow"=>$mixFlow,"min_range"=>$minRange];

    }

}