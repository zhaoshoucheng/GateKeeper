<?php
/**
 * 区域分析报告模块业务逻辑
 */

namespace Services;

use Services\AreaService;
use Services\ReportService;
use Services\DataService;

class AreaReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');

        $this->load->model('openCity_model');
        $this->load->model('waymap_model');
        $this->load->model('area_model');

        $this->areaService = new AreaService();
        $this->reportService = new ReportService();
        $this->dataService = new DataService();
    }

    public function introduction($params) {
    	$tpl = "本次报告区域为%s市，分析区域包含%s等行政区域。本次报告根据%s~%s数据对该区域进行分析。";

    	$city_id = $params['city_id'];
    	$area_id = $params['area_id'];
    	$start_date = $params['start_date'];
    	$end_date = $params['end_date'];

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
    	$districts_name = implode('、', array_unique(array_column($junctions_info, 'district_name')));

    	$desc = sprintf($tpl, $city_info['city_name'], $districts_name, date('Y年m月d日', strtotime($start_date)), date('Y年m月d日', strtotime($end_date)));

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

    	$now_data = $this->dataService->call("/report/GetStopDelayByHour", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => explode(',', $logic_junction_ids),
    	], "POST", 'json');
    	$last_data = $this->dataService->call("/report/GetStopDelayByHour", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($last_start_date, $last_end_date),
    		'logic_junction_ids' => explode(',', $logic_junction_ids),
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
          				'data' => $now_data,
          			],
          			[
          				'name' => $text[2],
          				'data' => $last_data,
          			],
				],
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

    	$morning_data = $this->dataService->call("/report/GetStopDelayByJunction", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => explode(',', $logic_junction_ids),
    		'hours' => $this->reportService->getHoursFromRange($morning_peek['start_hour'], $morning_peek['end_hour']),
    	], "POST", 'json');
    	$evening_data = $this->dataService->call("/report/GetStopDelayByJunction", [
    		'city_id' => $city_id,
    		'dates' => $this->reportService->getDatesFromRange($start_date, $end_date),
    		'logic_junction_ids' => explode(',', $logic_junction_ids),
    		'hours' => $this->reportService->getHoursFromRange($evenint_peek['start_hour'], $evenint_peek['end_hour']),
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
}