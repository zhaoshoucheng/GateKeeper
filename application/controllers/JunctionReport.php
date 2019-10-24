<?php
/**
 * 路口分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\JunctionService;
use Services\JunctionReportService;

class JunctionReport extends MY_Controller
{
    protected $junctionService;

    public function __construct()
    {
        parent::__construct();

        $this->config->load('report_conf');

        $this->junctionService = new JunctionService();
        $this->junctionReportService = new JunctionReportService();
    }

    /**
     * 单点路口分析 - 数据获取
     *
     * @throws Exception
     */
    public function queryQuotaInfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required|min_length[1]',
            'evaluate_start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'evaluate_end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'week[]' => 'required',
            'schedule_start' => 'required|exact_length[5]|regex_match[/\d{2}:\d{2}/]',
            'schedule_end' => 'required|exact_length[5]|regex_match[/\d{2}:\d{2}/]',
            'quota_key' => 'required|in_list[' . implode(',', array_keys($this->config->item('quotas'))) . ']',
            'type' => 'required|in_list[1,2]',
        ]);

        $data = $this->junctionService->queryQuotaInfo($params);

        $this->response($data);
    }

    public function introduction() {
        $params = $this->input->get(null, true);
        // $this->validate([
        //     'city_id' => 'required|is_natural_no_zero',
        //     'logic_junction_id' => 'required|min_length[1]',
        //     'start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        //     'end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        // ]);

        $data = $this->junctionReportService->introduction($params);
        $this->response($data);
    }

    public function queryJuncDataComparison(){

    }

    public function queryJuncQuotaData(){

    }

    public function queryJuncQuotaAnalysis(){

    }


}