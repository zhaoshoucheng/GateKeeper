<?php
/**
 * 干线分析报告模块业务逻辑
 */

namespace Services;

use Services\RoadService;
use Services\ReportService;
use Services\DataService;

class RoadReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');

        $this->load->model('openCity_model');
        $this->load->model('waymap_model');
        $this->load->model('road_model');
        $this->load->model('area_model');
        $this->load->model('pi_model');
        $this->load->model('traj_model');
        $this->load->model('arterialtiming_model');
        $this->load->model('diagnosisNoTiming_model');

        $this->roadService = new RoadService();
        $this->reportService = new ReportService();
        $this->dataService = new DataService();
    }

    public function introduction($params) {
    	$tpl = "%s干线位于%s市%s，承担较大的交通压力，干线包含%s等重要路口。本次报告根据%s~%s数据对该干线进行分析。";

    	$city_id = $params['city_id'];
    	$road_id = $params['road_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	$city_info = $this->openCity_model->getCityInfo($city_id);
    	if (empty($city_info)) {

    	}

    	$road_info = $this->road_model->getRoadInfo($road_id);
    	if (empty($road_info)) {

    	}
    	$logic_junction_ids = $road_info['logic_junction_ids'];

    	$junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
    	if (empty($junctions_info)) {

    	}
    	$junctions_name = implode('、', array_column($junctions_info, 'name'));
//        if(true){
        if($params['userapp'] == 'jinanits'){
            $dates = $this->getDateFromRange($start_date,$end_date);
            $pi = $this->pi_model->getGroupJuncAvgPiWithDates($city_id,explode(",",$logic_junction_ids) ,$dates,$this->createHours());
            $tpl = "%s干线位于%s市%s，承担较大的交通压力，整体运行水平PI值为".round($pi,2).",干线包含%s等重要路口。本次报告根据%s~%s数据对该干线进行分析。";
        }

    	$desc = sprintf($tpl, $road_info['road_name'], $city_info['city_name'], $junctions_info[0]['district_name'], $junctions_name, date('Y年m月d日', strtotime($start_date)), date('Y年m月d日', strtotime($end_date)));

    	$road_detail = $this->roadService->getRoadDetail([
    		'city_id' => $city_id,
    		'road_id' => $road_id,
    		'show_type' => 0,
    	]);


    	return [
    		'desc' => $desc,
    		'road_info' => $road_detail,
    	];
    }

    public function queryRoadDataComparison($params) {
    	$tpl = "上图展示了研究干线%s与%s路口平均延误的对比，%s干线拥堵程度与%s相比%s。";

    	$city_id = intval($params['city_id']);
    	$road_id = $params['road_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	$road_info = $this->road_model->getRoadInfo($road_id);
    	if (empty($road_info)) {

    	}
    	$logic_junction_ids = $road_info['logic_junction_ids'];

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

    public function queryRoadCongestion($params) {
    	$tpl = "下图展示了分析干线%s高峰延误排名前%d的路口。其中%s%s高峰拥堵情况严重。";

    	$city_id = intval($params['city_id']);
    	$road_id = $params['road_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];
        if(empty($city_id)){
            return [];
        }
    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {
    	// }
    	$road_info = $this->road_model->getRoadInfo($road_id);
    	if (empty($road_info)) {
            return [];
    	}
    	$logic_junction_ids = $road_info['logic_junction_ids'];

    	$junctions_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
    	if (empty($junctions_info)) {

    	}
    	$junctions_map = [];
    	array_map(function($item) use(&$junctions_map) {
    		$junctions_map[$item['logic_junction_id']] = $item;
    	}, $junctions_info);

    	$morning_peek = $this->reportService->getMorningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
    	$evening_peek = $this->reportService->getEveningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));

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
    		'hours' => $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']),
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

    public function queryQuotaRank($params) {
    	$tpl = "需要注意的是PI指数的计算中考虑了对过饱和、失衡以及溢流状态的惩罚。例如，两个路口在同样的平均停车或延误时间的情况下，如果某个路口出现了过饱和、失衡或者溢流现象，则该路口的PI值会更高。";

    	$city_id = intval($params['city_id']);
    	$road_id = $params['road_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	$road_info = $this->road_model->getRoadInfo($road_id);
    	if (empty($road_info)) {

    	}
    	$logic_junction_ids = $road_info['logic_junction_ids'];

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


    	$morning_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date), $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']));
    	usort($morning_pi_data, function($a, $b) {
    		return $a['pi'] > $b['pi'] ? -1 : 1;
    	});
    	$morning_pi_data = array_slice($morning_pi_data, 0, 20);
    	$morning_last_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($last_start_date, $last_end_date), $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']));
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
    					'last_rank' => isset($morning_last_pi_data_rank[$item['logic_junction_id']]) ? $morning_last_pi_data_rank[$item['logic_junction_id']] : -1,
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
    					'last_rank' => isset($evening_last_pi_data_rank[$item['logic_junction_id']]) ? $evening_last_pi_data_rank[$item['logic_junction_id']] : -1,
    					'stop_delay' => $evening_data_map[$item['logic_junction_id']]['stop_delay'],
    					'stop_time_cycle' => $evening_data_map[$item['logic_junction_id']]['stop_time_cycle'],
    					'speed' => $evening_data_map[$item['logic_junction_id']]['speed'],
    					'PI' => round($item['pi'], 2),
    				];
    			}, $evening_pi_data),
    		],
    	];
    }

    public function queryTopPI($params) {
    	$city_id = intval($params['city_id']);
    	$road_id = $params['road_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];
    	$top = 3;
    	if (isset($params['top'])) {
    		$top = $params['top'];
    	}

    	// $city_info = $this->openCity_model->getCityInfo($city_id);
    	// if (empty($city_info)) {

    	// }

    	$road_info = $this->road_model->getRoadInfo($road_id);
    	if (empty($road_info)) {

    	}
    	$logic_junction_ids = $road_info['logic_junction_ids'];

    	$morning_peek = $this->reportService->getMorningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
    	$morning_peek_hours = $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']);
    	$evening_peek = $this->reportService->getEveningPeekRange($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date));
    	$evening_peek_hours = $this->reportService->getHoursFromRange($evening_peek['start_hour'], $evening_peek['end_hour']);
    	$peek_hours = array_merge($morning_peek_hours, $evening_peek_hours);

    	$morning_pi_data = $this->pi_model->getJunctionsPiWithDatesHours($city_id, explode(',', $logic_junction_ids), $this->reportService->getDatesFromRange($start_date, $end_date), $peek_hours);
    	usort($morning_pi_data, function($a, $b) {
    		return $a['pi'] > $b['pi'] ? -1 : 1;
    	});
    	return array_slice(array_column($morning_pi_data, 'logic_junction_id'), 0, 3);
    }

    private function createHours(){
        $hours=[];
        for($i=strtotime("00:00");$i<=strtotime("23:30");$i=$i+1800){
            $hours[] = date("H:i",$i);
        }
        return $hours;
    }

    public function QueryRoadQuotaInfo($cityID,$roadID,$start_time,$end_time){
        $road_info = $this->road_model->getRoadInfo($roadID);
        $junctionIDs = $road_info['logic_junction_ids'];
        $dates = $this->getDateFromRange($start_time,$end_time);


        $roadQuotaData = $this->area_model->getJunctionsAllQuotaEs($dates,explode(",",$junctionIDs),$cityID);

        $PiDatas = $this->pi_model->getGroupJuncPiWithDatesHours($cityID,explode(",",$junctionIDs),$dates,$this->createHours());

        //数据合并

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

    //将结果路口运行情况查询结果转换为前端需要的表格
    public function transRoadQuota2Chart($data){
        $charts=[];
        $stopTimeChartData =[
            "title"=> "车均停车次数",
            "scale_title"=> "停车次数",
            "series"=> [],
        ];
        $speedChartData =[
            "title"=> "车均行驶速度",
            "scale_title"=> "行驶速度(km/h)",
            "series"=> [],
        ];
        $stopDelayChartData =[
            "title"=> "车均停车延误",
            "scale_title"=> "停车延误(s)",
            "series"=> [],
        ];
        $piChartData=[
            "title"=> "PI",
            "scale_title"=> "",
            "series"=> [],
        ];
        $stopTimeCycleChart = [];
        $speedCycleChart = [];
        $stopDelayCycleChart = [];
        $piChart=[];
        foreach ($data as $h => $v){
            $stopTimeCycleChart[] = [
                "x"=>$v['hour'],
                "y"=>round($v['stop_time_cycle'],2)
            ];
            $speedCycleChart[] = [
                "x"=>$v['hour'],
                "y"=>round($v['speed'],2)
            ];
            $stopDelayCycleChart[] = [
                "x"=>$v['hour'],
                "y"=>round($v['stop_delay'],2)
            ];
            $piChart[] = [
                "x"=>$v['hour'],
                "y"=>round($v['pi'],2)
            ];
        }
        //时间排序
        usort($stopTimeCycleChart, function($a, $b) {
            return (strtotime($a['x']) < strtotime($b['x'])) ? -1 : 1;
        });
        usort($speedCycleChart, function($a, $b) {
            return (strtotime($a['x']) < strtotime($b['x'])) ? -1 : 1;
        });
        usort($stopDelayCycleChart, function($a, $b) {
            return (strtotime($a['x']) < strtotime($b['x'])) ? -1 : 1;
        });
        usort($piChart, function($a, $b) {
            return (strtotime($a['x']) < strtotime($b['x'])) ? -1 : 1;
        });

        $stopTimeChartData['series'] =['name'=>"",'data'=>$stopTimeCycleChart];
        $speedChartData['series'] =['name'=>"",'data'=>$speedCycleChart];
        $stopDelayChartData['series'] =['name'=>"",'data'=>$stopDelayCycleChart];
        $piChartData['series'] =['name'=>"",'data'=>$piChart];

        $charts[] = $stopTimeChartData;
        $charts[] = $stopDelayChartData;
        $charts[] = $speedChartData;
        $charts[] = $piChartData;

        return $charts;
    }

    //计算早高峰,晚高峰的指标平均值
    public function queryChartAvg($from,$to,$chart){
        $value=0;
        $count=0;
        $flag=false;
        $d = $chart['series']['data'];
        foreach ($d as $k=>$v){
            if($v['x']==$from){
                $value += $v['y'];
                $count++;
                $flag=true;
            }elseif ($flag && $v['x']!= $to){
                $value += $v['y'];
                $count++;
            }elseif($v['x']==$to){
                break;
            }
        }
        if($count==0){
            return 0;
        }
        return round($value/$count,2);
    }

    //计算早高峰,默认时间已经排序
    public function getMorningRushHour($chart){
        //07:00-09:00,07:30-09:30,08:00-10:00
        $suma=0;
        $sumb=0;
        $sumc=0;
        $d = $chart['series']['data'];
        foreach ($d as $k => $v){
            if ($v["x"]=="07:00"){
                $suma = $d[$k]['y']+$d[$k+1]['y']+$d[$k+2]['y']+$d[$k+3]['y'];
                $sumb = $d[$k+1]['y']+$d[$k+2]['y']+$d[$k+3]['y']+$d[$k+4]['y'];
                $sumc = $d[$k+2]['y']+$d[$k+3]['y']+$d[$k+4]['y']+$d[$k+5]['y'];
                break;
            }
        }
        $max = max([$suma,$sumb,$sumc]);
        if($max == $suma){
            return ["s"=>"07:00","e"=>"09:00"];
        }elseif ($max==$sumb){
            return ["s"=>"07:30","e"=>"09:30"];
        }else{
            return ["s"=>"08:00","e"=>"10:00"];
        }

    }

    //计算晚高峰,默认时间已经排序
    public function getEveningRushHour($chart){
        //17:00-19:00,17:30-19:30,18:00-20:00
        $suma=0;
        $sumb=0;
        $sumc=0;
        $d = $chart['series']['data'];
        foreach ($d as $k => $v){
            if ($v["x"]=="17:00"){
                $suma = $d[$k]['y']+$d[$k+1]['y']+$d[$k+2]['y']+$d[$k+3]['y'];
                $sumb = $d[$k+1]['y']+$d[$k+2]['y']+$d[$k+3]['y']+$d[$k+4]['y'];
                $sumc = $d[$k+2]['y']+$d[$k+3]['y']+$d[$k+4]['y']+$d[$k+5]['y'];
                break;
            }
        }
        $max = max([$suma,$sumb,$sumc]);
        if($max == $suma){
            return ["s"=>"17:00","e"=>"19:00"];
        }elseif ($max==$sumb){
            return ["s"=>"17:30","e"=>"19:30"];
        }else{
            return ["s"=>"18:00","e"=>"20:00"];
        }

    }



    //求pi加权平均
    public function queryParamGroup($data,$quotaKey,$trajKey){
        $res=[];
        foreach ($data as $v){
            if(!isset($res[$v['date']." ".$v['hour']])){
                $res[$v['date']." ".$v['hour']]=['value'=>0,'count'=>0];
            }
            $res[$v['date']." ".$v['hour']]['value']+=$v[$quotaKey]*$v[$trajKey];
            $res[$v['date']." ".$v['hour']]['count']+=$v[$trajKey];
        }
        $final=[];
        foreach ($res as $k=> $r){
            $t = explode(" ",$k);
            $final[] = [
                'date'=>$t[0],
                'hour'=>$t[1],
                $quotaKey=>round($r['value']/$r['count'],2),
            ];
        }
        return $final;
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

    //报警热力图最多保留20个路口的数据
    private function shortenChart($chartList){
        $newChartList = [];
        foreach ($chartList as $chartData){
            if($chartData['chart']['one_dimensional']<=20){
                $newChartList[] = $chartData;
            }
            $tmpChartData = $chartData;
            //默认数据已经排序
            $tmpChartData['chart']['one_dimensional'] = array_slice($tmpChartData['chart']['one_dimensional'], 0, 20);
            $data = [];
            foreach ($tmpChartData['chart']['data'] as $v){
                if($v[1]>=20){
                    continue;
                }
                $data[] = $v;
            }
            $tmpChartData['chart']['data'] = $data;
            $newChartList[] = $tmpChartData;
        }

        return $newChartList;
    }
    //填充表格
    public function fillRoadAlarmChart($chartList,$imbalanceData,$overData,$spillData,$juncName){
        //前三个早高峰,后三个晚高峰,表格排序失衡,过饱和,溢流

        $juncIndex=[];
        $morningIndex=[];
        $eveningIndex=[];

        foreach ($chartList[0]['chart']['one_dimensional'] as $k=>$v){
            $juncIndex[$juncName[$v]] = $k;
        }
        foreach ($chartList[0]['chart']['two_dimensional'] as $k=>$v){
            $morningIndex[$v] = $k;
        }
        foreach ($chartList[4]['chart']['two_dimensional'] as $k=>$v){
            $eveningIndex[$v] = $k;
        }
        $morningmaxscale=0;
        $eveningmaxscale=0;
        $morningdata=[];
        $eveningdata=[];
        foreach ($imbalanceData as $k=> $v){
            foreach ($v as $t){
                $time = $this->roundingtime($t);
                if(isset($morningIndex[$time])){
                    if(!isset($morningdata[$juncIndex[$k]])){
                        $morningdata[$juncIndex[$k]]=[];
                    }
                    if(!isset($morningdata[$juncIndex[$k]][$morningIndex[$time]])){
                        $morningdata[$juncIndex[$k]][$morningIndex[$time]]=0;
                    }
                    $morningdata[$juncIndex[$k]][$morningIndex[$time]]+=1;
                }elseif(isset($eveningIndex[$time])){
                    if(!isset($eveningdata[$juncIndex[$k]])){
                        $eveningdata[$juncIndex[$k]]=[];
                    }
                    if(!isset($eveningdata[$juncIndex[$k]][$eveningIndex[$time]])){
                        $eveningdata[$juncIndex[$k]][$eveningIndex[$time]]=0;
                    }
                    $eveningdata[$juncIndex[$k]][$eveningIndex[$time]]+=1;
                }
            }
        }
        if(!empty($morningdata)){
            $mcd = &$chartList[0];
            foreach ($morningdata as $k1=>$v1){
                foreach ($v1 as $k2=>$v2){
                    if($v2>$morningmaxscale){
                        $morningmaxscale=$v2;
                    }

                    $mcd['chart']['data'][] = [$k2,$k1,$v2];
                }
            }
            $mcd['chart']['scale']['max']=$morningmaxscale+5;

        }
        if(!empty($eveningdata)){
            $ecd=&$chartList[3];
            foreach ($eveningdata as $k1=>$v1){
                foreach ($v1 as $k2=>$v2){
                    if($v2>$eveningmaxscale){
                        $eveningmaxscale=$v2;
                    }

                    $ecd['chart']['data'][] = [$k2,$k1,$v2];
                }
            }
            $ecd['chart']['scale']['max']=$eveningmaxscale+5;
        }
        //TODO 后续三段合成一段
        $morningmaxscale=0;
        $eveningmaxscale=0;
        $morningdata=[];
        $eveningdata=[];
        foreach ($overData as $k=> $v){
            foreach ($v as $t){
                $time = $this->roundingtime($t);
                if(isset($morningIndex[$time])){
                    if(!isset($morningdata[$juncIndex[$k]])){
                        $morningdata[$juncIndex[$k]]=[];
                    }
                    if(!isset($morningdata[$juncIndex[$k]][$morningIndex[$time]])){
                        $morningdata[$juncIndex[$k]][$morningIndex[$time]]=0;
                    }
                    $morningdata[$juncIndex[$k]][$morningIndex[$time]]+=1;
                }elseif(isset($eveningIndex[$time])){
                    if(!isset($eveningdata[$juncIndex[$k]])){
                        $eveningdata[$juncIndex[$k]]=[];
                    }
                    if(!isset($eveningdata[$juncIndex[$k]][$eveningIndex[$time]])){
                        $eveningdata[$juncIndex[$k]][$eveningIndex[$time]]=0;
                    }
                    $eveningdata[$juncIndex[$k]][$eveningIndex[$time]]+=1;
                }
            }
        }
        if(!empty($morningdata)){
            $mcd = &$chartList[1];
            foreach ($morningdata as $k1=>$v1){
                foreach ($v1 as $k2=>$v2){
                    if($v2>$morningmaxscale){
                        $morningmaxscale=$v2;
                    }

                    $mcd['chart']['data'][] = [$k2,$k1,$v2];
                }
            }
            $mcd['chart']['scale']['max']=$morningmaxscale+5;
        }
        if(!empty($eveningdata)){
            $ecd=&$chartList[4];
            foreach ($eveningdata as $k1=>$v1){
                foreach ($v1 as $k2=>$v2){
                    if($v2>$eveningmaxscale){
                        $eveningmaxscale=$v2;
                    }

                    $ecd['chart']['data'][] = [$k2,$k1,$v2];
                }
            }
            $ecd['chart']['scale']['max']=$eveningmaxscale+5;
        }
        //TODO 后续三段合成一段
        $morningmaxscale=0;
        $eveningmaxscale=0;
        $morningdata=[];
        $eveningdata=[];
        foreach ($spillData as $k=> $v){
            foreach ($v as $t){
                $time = $this->roundingtime($t);
                if(isset($morningIndex[$time])){
                    if(!isset($morningdata[$juncIndex[$k]])){
                        $morningdata[$juncIndex[$k]]=[];
                    }
                    if(!isset($morningdata[$juncIndex[$k]][$morningIndex[$time]])){
                        $morningdata[$juncIndex[$k]][$morningIndex[$time]]=0;
                    }
                    $morningdata[$juncIndex[$k]][$morningIndex[$time]]+=1;
                }elseif(isset($eveningIndex[$time])){
                    if(!isset($eveningdata[$juncIndex[$k]])){
                        $eveningdata[$juncIndex[$k]]=[];
                    }
                    if(!isset($eveningdata[$juncIndex[$k]][$eveningIndex[$time]])){
                        $eveningdata[$juncIndex[$k]][$eveningIndex[$time]]=0;
                    }
                    $eveningdata[$juncIndex[$k]][$eveningIndex[$time]]+=1;
                }
            }
        }
        if(!empty($morningdata)){
            $mcd = &$chartList[2];
            foreach ($morningdata as $k1=>$v1){
                foreach ($v1 as $k2=>$v2){
                    if($v2>$morningmaxscale){
                        $morningmaxscale=$v2;
                    }

                    $mcd['chart']['data'][] = [$k2,$k1,$v2];
                }
            }
            $mcd['chart']['scale']['max']=$morningmaxscale+5;
        }
        if(!empty($eveningdata)){
            $ecd=&$chartList[5];
            foreach ($eveningdata as $k1=>$v1){
                foreach ($v1 as $k2=>$v2){
                    if($v2>$eveningmaxscale){
                        $eveningmaxscale=$v2;
                    }

                    $ecd['chart']['data'][] = [$k2,$k1,$v2];
                }
            }
            $ecd['chart']['scale']['max']=$eveningmaxscale+5;
        }

        return $this->shortenChart($chartList);
    }

    //先初始化表格,然后填充数据
    public function initRoadAlarmChart($roadInfo,$morningRushTime,$eveningRushTime,$type="干线"){
        $chartList=[];
        $junctionList=[];
        foreach ($roadInfo['junctions_info'] as $k => $j){
            $junctionList[] = $j['name'];
        }
        $morningTimes = $this->getTimeFromRange(explode("~",$morningRushTime)[0],explode("~",$morningRushTime)[1],30);
        $eveningTimes = $this->getTimeFromRange(explode("~",$eveningRushTime)[0],explode("~",$eveningRushTime)[1],30);
        $morningImbalanceChart=[
            "desc"=>$type."早高峰失衡报警持续5分钟以上的路口排名如下图所示。",
            "chart"=>[
                'title'=>$morningRushTime."重点路口持续5分钟以上失衡报警",
                'scale'=>['min'=>0,'max'=>50],
                'one_dimensional'=>$junctionList,
                'two_dimensional'=>$morningTimes,
                'data'=>[],
            ],
        ];
        $morningOversaturationChart=[
            "desc"=>$type."早高峰过饱和报警持续5分钟以上的路口排名如下图所示。",
            "chart"=>[
                'title'=>$morningRushTime."重点路口持续5分钟以上过饱和报警",
                'scale'=>['min'=>0,'max'=>50],
                'one_dimensional'=>$junctionList,
                'two_dimensional'=>$morningTimes,
                'data'=>[],
            ],
        ];
        $morningSpilloverChart=[
            "desc"=>$type."早高峰溢流报警持续5分钟以上的路口排名如下图所示。",
            "chart"=>[
                'title'=>$morningRushTime."重点路口持续5分钟以上溢流报警",
                'scale'=>['min'=>0,'max'=>50],
                'one_dimensional'=>$junctionList,
                'two_dimensional'=>$morningTimes,
                'data'=>[],
            ],
        ];

        $eveningImbalanceChart=[
            "desc"=>$type."晚高峰失衡报警持续5分钟以上的路口排名如下图所示。",
            "chart"=>[
                'title'=>$eveningRushTime."重点路口持续5分钟以上失衡报警",
                'scale'=>['min'=>0,'max'=>50],
                'one_dimensional'=>$junctionList,
                'two_dimensional'=>$eveningTimes,
                'data'=>[],
            ],
        ];
        $evemingOversaturationChart=[
            "desc"=>$type."晚高峰过饱和报警持续5分钟以上的路口排名如下图所示。",
            "chart"=>[
                'title'=>$eveningRushTime."重点路口持续5分钟以上过饱和报警",
                'scale'=>['min'=>0,'max'=>50],
                'one_dimensional'=>$junctionList,
                'two_dimensional'=>$eveningTimes,
                'data'=>[],
            ],
        ];
        $eveningSpilloverChart=[
            "desc"=>$type."晚高峰溢流报警持续5分钟以上的路口排名如下图所示。",
            "chart"=>[
                'title'=>$eveningRushTime."重点路口持续5分钟以上溢流报警",
                'scale'=>['min'=>0,'max'=>50],
                'one_dimensional'=>$junctionList,
                'two_dimensional'=>$eveningTimes,
                'data'=>[],
            ],
        ];
        $chartList[]=$morningImbalanceChart;
        $chartList[]=$morningOversaturationChart;
        $chartList[]=$morningSpilloverChart;
        $chartList[]=$eveningImbalanceChart;
        $chartList[]=$evemingOversaturationChart;
        $chartList[]=$eveningSpilloverChart;

        return $chartList;
    }



    public function queryRoadAlarm($cityID,$roadID,$startTime,$endTime,$morningRushTime,$eveningRushTime){
        $roadInfo= $this->road_model->getRoadInfo($roadID);

        $roadDetail = $this->arterialtiming_model->getJunctionFlowInfos($cityID,0,explode(",",$roadInfo['logic_junction_ids']));

        $junctionList  = explode(",",$roadInfo['logic_junction_ids']);


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

        $juncNameMap=[];
        foreach ($roadDetail['junctions_info'] as $k => $j){
            $juncNameMap[$j['name']] = $k;
        }

        //初始化表格
        $initChartList = $this->initRoadAlarmChart($roadDetail,$morningRushTime,$eveningRushTime);
        $fillChartData = $this->fillRoadAlarmChart($initChartList,$imbalance,$oversaturation,$spillover,$juncNameMap);



        return $fillChartData;
    }

    //干线协调相关代码
    public function queryRoadCoordination($city_id,$road_id,$startTime,$endTime,$morningRushTime,$eveningRushTime){
        $road_info = $this->road_model->getRoadInfo($road_id);
        $ret = $this->arterialtiming_model->getJunctionFlowInfos($city_id,0,explode(",",$road_info['logic_junction_ids']));

        $forwardFlows=[];
        $backwardFlows=[];
        $flowmap=[];
        $juncNameMap=[];
        foreach ($ret['junctions_info'] as $jk=>$jv){
            $juncNameMap[$jk] = $jv['name'];
        }
        foreach ($ret['forward_path_flows'] as $fk=>$fv){
            $forwardFlows[] = $fv['logic_flow']['logic_flow_id'];
            $flowmap[$fv['logic_flow']['logic_flow_id']]=  $juncNameMap[$fv['logic_flow']['logic_junction_id']];
        }

        foreach ($ret['backward_path_flows'] as $bk=>$bv){
            $backwardFlows[] = $bv['logic_flow']['logic_flow_id'];
            $flowmap[$bv['logic_flow']['logic_flow_id']]=  $juncNameMap[$bv['logic_flow']['logic_junction_id']];
        }

        $flows = array_merge($forwardFlows,$backwardFlows);
        $quota = $this->diagnosisNoTiming_model->getSpecialFlowQuota($city_id,$flows,$startTime,$endTime);


        //早高峰数据过滤
        $morningData = $this->caculateRoadCoordinationTimeData($quota,$morningRushTime[0],$morningRushTime[1]);
        //晚高峰数据过滤
        $eveningData = $this->caculateRoadCoordinationTimeData($quota,$eveningRushTime[0],$eveningRushTime[1]);


        $morningChart=[
            'info'=>[
                'desc'=>"以下两张图表现了早高峰".$road_info['road_name']."干线不同方向路口平均停车次数与路口延误随时间变化的趋势(蓝色为正,黄色为反)。"
            ],
            'chart_list'=>[]
        ];
        $morningChart['chart_list']=$this->transRoadCoordination2Chart($morningData,$forwardFlows,$backwardFlows,$flowmap);
        $morningChart['info']['desc'].="车辆通过干线正向方向的".count($forwardFlows)."个路口平均需要停车".$morningChart['chart_list'][0]['series'][0]['avg']."次,其中".$morningChart['chart_list'][0]['series'][0]['max_key']."路口停车比例最高,为".$morningChart['chart_list'][0]['series'][0]['max']."次;
        平均路口延误为".$morningChart['chart_list'][1]['series'][0]['avg']."秒,其中".$morningChart['chart_list'][1]['series'][0]['max_key']."路口延误时间最高,为".$morningChart['chart_list'][1]['series'][0]['max']."秒。
                车辆通过干线反向方向的".count($backwardFlows)."个路口平均需要停车".$morningChart['chart_list'][0]['series'][1]['avg']."次,其中".$morningChart['chart_list'][0]['series'][1]['max_key']."路口停车比例最高,为".$morningChart['chart_list'][0]['series'][1]['max']."次;
                平均路口延误为".$morningChart['chart_list'][1]['series'][1]['avg']."秒,其中".$morningChart['chart_list'][1]['series'][1]['max_key']."路口延误时间最高,为".$morningChart['chart_list'][1]['series'][1]['avg']."秒。";
        $eveningChart=[
            'info'=>[
                'desc'=>"以下两张图表现了晚高峰".$road_info['road_name']."干线不同方向路口平均停车次数与路口延误随时间变化的趋势(蓝色为正,黄色为反)。"
            ],
            'chart_list'=>[]
        ];
        $eveningChart['chart_list']=$this->transRoadCoordination2Chart($eveningData,$forwardFlows,$backwardFlows,$flowmap);
        $eveningChart['info']['desc'].="车辆通过干线正向方向的".count($forwardFlows)."个路口平均需要停车".$eveningChart['chart_list'][0]['series'][0]['avg']."次,其中".$eveningChart['chart_list'][0]['series'][0]['max_key']."路口停车比例最高,为".$eveningChart['chart_list'][0]['series'][0]['max']."次;
        平均路口延误为".$eveningChart['chart_list'][1]['series'][0]['avg']."秒,其中".$eveningChart['chart_list'][1]['series'][0]['max_key']."路口延误时间最高,为".$eveningChart['chart_list'][1]['series'][0]['max']."秒。
                车辆通过干线反向方向的".count($backwardFlows)."个路口平均需要停车".$eveningChart['chart_list'][0]['series'][1]['avg']."次,其中".$eveningChart['chart_list'][0]['series'][1]['max_key']."路口停车比例最高,为".$eveningChart['chart_list'][0]['series'][1]['max']."次;
                平均路口延误为".$eveningChart['chart_list'][1]['series'][1]['avg']."秒,其中".$eveningChart['chart_list'][1]['series'][1]['max_key']."路口延误时间最高,为".$eveningChart['chart_list'][1]['series'][1]['avg']."秒。";

        return [$morningChart,$eveningChart];
    }

    //查询表格中的平均值与最大值


    //将干线协调结果转换成图表
    public function transRoadCoordination2Chart($data,$forward,$backward,$flow2JuncMap){
        $chart_list=[
        ];
        $stopTimeCycleChart=[
            "title"=>"干线路口停车次数(次/路口)",
            "scale_title"=> "",
            "series"=>[]
        ];
        $stopDelayChart=[
            "title"=>"干线路口延误(秒)",
            "scale_title"=>"",
            "series"=>[]
        ];
        $cycseriesf=[
            'name'=>"正向",
            "avg"=>0,
            "max"=>0,
            'data'=>[]
        ];
        $delayseriesf=[
            'name'=>"正向",
            "avg"=>0,
            "max"=>0,
            'data'=>[]
        ];
        $cycseriesb=[
            'name'=>"反向",
            "avg"=>0,
            "max"=>0,
            'data'=>[]
        ];
        $delayseriesb=[
            'name'=>"反向",
            "avg"=>0,
            "max"=>0,
            'data'=>[]
        ];
        foreach ($data as $k=>$v){

            if(in_array($k,$forward)){
                $cycseriesf['data'][] = [
                    'x'=>$flow2JuncMap[$k],
                    'y'=>$v['stop_time_cycle'],
                ];
                $delayseriesf['data'][]=[
                    'x'=>$flow2JuncMap[$k],
                    'y'=>$v['stop_delay'],
                ];
            }elseif (in_array($k,$backward)){
                $cycseriesb['data'][] = [
                    'x'=>$flow2JuncMap[$k],
                    'y'=>$v['stop_time_cycle'],
                ];
                $delayseriesb['data'][]=[
                    'x'=>$flow2JuncMap[$k],
                    'y'=>$v['stop_delay'],
                ];
            }
        }

        $cycseriesfcol = array_column($cycseriesf['data'],'y');

//        $cfs = array_multisort(array_column($cycseriesf['data'],'y'),SORT_DESC,$cycseriesf['data']);
        $cycseriesf['max']=max($cycseriesfcol);
        $key = array_search(max($cycseriesfcol),$cycseriesfcol);
        $cycseriesf['max_key']=$cycseriesf['data'][$key]['x'];
        $cycseriesf['avg']=round(array_sum($cycseriesfcol)/count($cycseriesfcol),2);

        $cycseriesbcol = array_column($cycseriesb['data'],'y');
        $cycseriesb['max']=max($cycseriesbcol);
        $key = array_search(max($cycseriesbcol),$cycseriesbcol);
        $cycseriesb['max_key']=$cycseriesb['data'][$key]['x'];
        $cycseriesb['avg']=round(array_sum($cycseriesbcol)/count($cycseriesbcol),2);

        $delayseriesfcol = array_column($delayseriesf['data'],'y');
        $delayseriesf['max']=max($delayseriesfcol);
        $key = array_search(max($delayseriesfcol),$delayseriesfcol);
        $delayseriesf['max_key']=$delayseriesf['data'][$key]['x'];
        $delayseriesf['avg']=round(array_sum($delayseriesfcol)/count($delayseriesfcol),2);

        $delayseriesbcol = array_column($delayseriesb['data'],'y');
        $delayseriesb['max']=max($delayseriesbcol);
        $key = array_search(max($delayseriesbcol),$delayseriesbcol);
        $delayseriesb['max_key']=$delayseriesb['data'][$key]['x'];
        $delayseriesb['avg']=round(array_sum($delayseriesbcol)/count($delayseriesbcol),2);

        $stopTimeCycleChart['series'][] = $cycseriesf;
        $stopTimeCycleChart['series'][] = $cycseriesb;
        $stopDelayChart['series'][] = $delayseriesf;
        $stopDelayChart['series'][] = $delayseriesb;

        $chart_list[] = $stopTimeCycleChart;
        $chart_list[] = $stopDelayChart;
        return $chart_list;

    }

    //抽取早晚高峰的数据,并求平均
    public function caculateRoadCoordinationTimeData($data,$s,$e){
        $ret = [];
        $stime = strtotime($s);
        $etime = strtotime($e);
        foreach ($data as  $fk=>$fv){
            $ret[$fk] = [
                'speed'=>0,
                'stop_delay'=>0,
                'stop_time_cycle'=>0,
                'count'=>0,
            ];
            foreach ($fv as $yk => $yv){
                foreach ($yv as $d){
                    if($stime<=strtotime($d['hour']) && $etime>=strtotime($d['hour']) ){
                        $ret[$fk]['speed']+=$d['speed'];
                        $ret[$fk]['stop_delay']+=$d['stop_delay'];
                        $ret[$fk]['stop_time_cycle']+=$d['stop_time_cycle'];
                        $ret[$fk]['count']+=1;
                    }
                }
            }
        }
        $final=[];
        foreach ($ret as $rfk => $rfv){
//            if($rfv['traj_count']==0){
//                $final[$rfk] = [
//                    'speed'=>0,
//                    'stop_delay'=>0,
//                    'stop_time_cycle'=>0,
//                ];
//            }else{
                $final[$rfk] = [
                    'speed'=>round($rfv['speed']*3.6/$rfv['count'],2),
                    'stop_delay'=>round($rfv['stop_delay']/$rfv['count'],2),
                    'stop_time_cycle'=>round($rfv['stop_time_cycle']/$rfv['count'],2),
                ];
//            }

        }
        return $final;
    }

}