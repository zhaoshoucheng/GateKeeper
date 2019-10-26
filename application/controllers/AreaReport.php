<?php
/**
 * 城市区域分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AreaReportService;

class AreaReport extends MY_Controller
{
    protected $junctionService;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('report_conf');

        $this->areaReportService = new AreaReportService();
    }

    public function introduction() {
        $params = $this->input->get(null, true);
        // $this->validate([
        //     'city_id' => 'required|is_natural_no_zero',
        //     'area_id' => 'required|is_natural_no_zero',
        //     'start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        //     'end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        // ]);

        $data = $this->areaReportService->introduction($params);
        $this->response($data);
    }

    public function queryAreaDataComparison(){}
    public function queryAreaCongestion(){}
    public function queryAreaAlarm(){}
    public function queryAreaQuotaData(){}
    public function queryQuotaRank(){}

}