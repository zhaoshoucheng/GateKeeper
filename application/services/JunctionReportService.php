<?php
/**
 * 路口分析报告模块业务逻辑
 */

namespace Services;

class JunctionReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');

        $this->load->model('openCity_model');
        $this->load->model('waymap_model');
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
}