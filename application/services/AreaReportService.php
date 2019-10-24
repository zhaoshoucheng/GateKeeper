<?php
/**
 * 区域分析报告模块业务逻辑
 */

namespace Services;

use Services\AreaService;

class AreaReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');

        $this->load->model('openCity_model');
        $this->load->model('waymap_model');
        $this->load->model('area_model');

        $this->areaService = new AreaService();
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
}