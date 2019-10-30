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
    	$tpl = "本次报告分析路口位于%s市%s。本次报告根据%s~%s数据对该路口进行分析。";

    	$city_id = $params['city_id'];
    	$logic_junction_id = $params['logic_junction_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

    	$city_info = $this->openCity_model->getCityInfo($city_id);
    	if (empty($city_info)) {

    	}

    	$junction_info = $this->waymap_model->getJunctionInfo($logic_junction_id);
    	if (empty($junction_info)) {

    	} else {
    		$junction_info = $junction_info[0];
    	}

    	$desc = sprintf($tpl, $city_info['city_name'], $junction_info['district_name'], date('Y年m月d日', strtotime($start_date)), date('Y年m月d日', strtotime($end_date)));

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

    	$now_data = $this->dataService->call("/report/GetStopDelayByHour", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => [$logic_junction_id],
    	], "POST", 'json');
    	$last_data = $this->dataService->call("/report/GetStopDelayByHour", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($last_start_date, $last_end_date),
    		'logic_junction_ids' => [$logic_junction_id],
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

    public function queryJuncQuotaData($params) {
    	$tpl = "下图利用滴滴数据绘制了该路口全天24小时各项运行指标（车均停车次数、车均停车延误、车均行驶速度、PI）。通过数据分析，该路口的早高峰约为%s-%s，晚高峰约为%s-%s。与平峰相比，早晚高峰的停车次数达到%.2f次/车/路口，停车延误接近%.2f秒/车/路口，车均行驶速度也达到%.2f千米/小时左右。";
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

    	$index_data = $this->dataService->call("/report/GetIndexByHour", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => [$logic_junction_id],
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

    	$desc = sprintf($tpl, $morning_peek['start_hour'], $morning_peek['end_hour'], $evening_peek['start_hour'], $evening_peek['end_hour'], round(array_sum($stop_time_cycle_data) / count($stop_time_cycle_data), 2), round(array_sum($stop_delay_data) / count($stop_delay_data), 2), round(array_sum($speed_data) / count($speed_data), 2));

    	return [
    		'info' => [
    			'desc' => $desc,
    			'instructions' => $instructions,
    		],
    		'chart' => [
    			[
	    			'title' => '车均停车次数',
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
	    			'title' => '车均停车延误',
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
	    			'title' => '车均行驶速度',
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

    //es数据转换为表格
    public function trans2Chart($flowQuota,$flowInfo){
        $stopTimeChartData =[
            "quotaname"=>"车均停车次数",
            "quotakey"=>"stop_time_cycle",
            "analysis"=>"",
            "flowlist"=>[],
        ];
        $speedChartData =[
            "quotaname"=>"车均行驶速度",
            "quotakey"=>"speed",
            "analysis"=>"",
            "flowlist"=>[],
        ];
        $stopDelayChartData =[
            "quotaname"=>"车均停车延误",
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
                    "series"=>[["name"=>"","data"=>$stopTimeCycleChart]],
                ],
            ];
            $speedChartData['flowlist'][]=[
                "logic_flow_id"=>$fk,
                "chart"=>[
                    "title"=>$flowInfo[$fk],
                    "scale_title"=>"km/h",
                    "series"=>[["name"=>"","data"=>$speedCycleChart]]
                ],
            ];
            $stopDelayChartData['flowlist'][]=[
                "logic_flow_id"=>$fk,
                "chart"=>[
                    "title"=>$flowInfo[$fk],
                    "scale_title"=>"S",
                    "series"=>[["name"=>"","data"=>$stopDelayCycleChart]]
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
                    $chartData[$k]["analysis"]="该路口在评估日期内".$maxQuotaFlow['max_flow']."方向的停车次数最大,其中在".$maxQuotaFlow['max_range'][0]."-".end($maxQuotaFlow['max_range'])."时段内的停车次数最大,需重点关注。";
                    break;
                case "speed":
                    $minQuotaFlow = $this->queryMinQuotaFlow($chartData[$k]['flowlist']);
                    $chartData[$k]["analysis"]="该路口在评估日期内".$minQuotaFlow['min_flow']."方向的行驶速度最小,其中在".$minQuotaFlow['min_range'][0]."-".end($minQuotaFlow['min_range'])."时段内的行驶速度最小,需重点关注。";
                    break;
                case "stop_delay":
                    $maxQuotaFlow = $this->queryMaxQuotaFlow($chartData[$k]['flowlist']);
                    $chartData[$k]["analysis"]="该路口在评估日期内".$maxQuotaFlow['max_flow']."方向的停车延误最大,其中在".$maxQuotaFlow['max_range'][0]."-".end($maxQuotaFlow['max_range'])."时段内的停车延误最大,需重点关注。";
                    break;
            }
        }
        return $chartData;
    }

    private function queryMaxQuotaFlow($flowlist){
        $bucket=[];
        foreach ($flowlist as $f => $v){
            foreach ($v['chart']['series'][0]['data'] as $s){
                if(!isset($bucket[$s['x']])){
                    $bucket[$s['x']] = [
                        'flowname'=>$v['chart']['title'],
                        'value'=>0
                    ];
                }
                if ($s['y'] > $bucket[$s['x']]['value']){
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
        $bucket=[];
        foreach ($flowlist as $f => $v){
            foreach ($v['chart']['series'][0]['data'] as $s){
                if(!isset($bucket[$s['x']])){
                    $bucket[$s['x']] = [
                        'flowname'=>$v['chart']['title'],
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