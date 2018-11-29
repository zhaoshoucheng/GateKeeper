<?php
/**
 * 周报、月报模块
 */

use Services\PeriodReportService;

/**
 * Class PeriodReport
 *
 * @property Period_model $period_model
 */
class PeriodReport extends MY_Controller
{
    const WEEK  = 3;
    const MONTH = 4;

    const ALLDAY  = 1;
    const MORNING = 2;
    const NIGHT   = 3;

    protected $periodReportService;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('period_model');
        $this->load->library('EvaluateQuota');
        $this->load->model('waymap_model');

        $this->periodReportService = new PeriodReportService();
    }

    /**
     * 周、月报–市运行情况概述信息
     *
     * @throws Exception
     */
    public function overview()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'city_name' => 'required|min_length[1]',
            'type' => 'required|in_list[3,4]',
        ]);

        $data = $this->periodReportService->overview($params);

        $this->response($data);
    }

    /**
     * 周、月报–市平均延误运行情况表格
     * @throws Exception
     */
    public function stopDelayTable()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'city_name' => 'required|min_length[1]',
            'type' => 'required|in_list[3,4]',
        ]);

        $data = $this->periodReportService->stopDelayTable($params);

        $this->response($data);
    }

    /**
     * 行政区交通运行情况（全天，早高峰，晚高峰 ）
     * @throws Exception
     */
    public function districtReport()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'type' => 'required|in_list[3,4]',
            'time_type' => 'required|in_list[1,2,3]',
            'city_name' => 'required|min_length[1]',
        ]);

        $data = $this->periodReportService->districtReport($params);

        $this->response($data);
    }


    /**
     * 周、月报–延误最大top20(top10)
     *
     * @throws Exception
     */
    public function quotaTopJunction()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'type' => 'required|in_list[3,4]',
            'time_type' => 'required|in_list[1,2,3]',
            'city_name' => 'required|min_length[1]',
            'top_num' => 'required|is_natural',
            'quota_key' => 'required|in_list[queue_length,stop_delay]',
        ]);

        $data = $this->periodReportService->quotaTopJunction($params);

        $this->response($data);
    }

    /**
     * 周、月报–溢流问题分析
     *
     * @throws Exception
     */
    public function spilloverChart()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'type' => 'required|in_list[3,4]',
        ]);

        $data = $this->periodReportService->spilloverChart($params);

        $this->response($data);

    }

    /**
     *周、月报–早(晚)高峰平均延误柱状图
     * @throws Exception
     */
    public function delayChart()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'type' => 'required|in_list[3,4]',
            'time_type' => 'required|in_list[1,2,3]',
        ]);

        $data = $this->periodReportService->delayChart($params);

        $this->response($data);
    }

    /**
     * 周、月报–早(晚)高峰平均速度柱状图
     *
     * @throws Exception
     */
    public function speedChart()
    {
        $params = $this->input->post();

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'type' => 'required|in_list[3,4]',
            'time_type' => 'required|in_list[1,2,3]',
        ]);

        $data = $this->periodReportService->speedChart($params);

        $this->response($data);
    }

    public static function quotasort($a,$b){
        return $b[1]-$a[1];
    }


}