<?php
/**
 * 干线分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\RoadReportService;

class RoadReport extends MY_Controller
{
    protected $junctionService;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('report_conf');

        $this->roadReportService = new RoadReportService();
    }

    public function introduction() {
        $params = $this->input->get(null, true);
        // $this->validate([
        //     'city_id' => 'required|is_natural_no_zero',
        //     'road_id' => 'required|min_length[1]',
        //     'start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        //     'end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        // ]);

        $data = $this->roadReportService->introduction($params);
        $this->response($data);
    }
    public function queryRoadDataComparison(){}
    public function queryRoadQuotaData(){}
    public function queryRoadCoordination(){}
    public function queryRoadCongestion(){}
    public function queryRoadAlarm(){}
    public function queryQuotaRank(){}

}