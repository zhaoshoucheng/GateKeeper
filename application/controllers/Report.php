<?php
/**
 * 报告相关模块
 */

use Services\ReportService;

class Report extends MY_Controller
{
    protected $reportService;

    public function __construct()
    {
        parent::__construct();

        $this->reportService = new ReportService();
    }

    /**
     *
     */
    public function test()
    {
        $data = $this->reportService->test();

        $this->response($data);
    }

    /**
     * @throws Exception
     */
    public function searchJunction()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'keyword' => 'required|trim|min_length[1]',
        ]);

        $data = $this->reportService->searchJunction($params);

        $this->response($data);
    }

    /**
     * @throws Exception
     */
    public function reportConfig()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'type' => 'required|in_list[1,2,3,4]',
        ]);

        $data = $this->reportService->reportConfig($params);

        $this->response($data);
    }

    /**
     * @throws Exception
     */
    public function generate()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'title' => 'required|trim|min_length[1]',
            'type' => 'required',
        ]);

        $data = $this->reportService->generate($params);

        $this->response($data);
    }

    /**
     * @throws Exception
     */
    public function getReportList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'type' => 'required',
            'page_no' => 'required',
            'page_size' => 'required',
        ]);

        $data = $this->reportService->getReportList($params);

        $this->response($data);
    }

    /**
     * @throws Exception
     */
    public function downReport()
    {
        $params = $this->input->get(null, true);

        $this->validate([
            'key' => 'required',
        ]);

        $this->reportService->downReport($params);
    }
}