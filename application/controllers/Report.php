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

    public function searchRoad() {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'keyword' => 'required|trim|min_length[1]',
        ]);

        $data = $this->reportService->searchRoad($params);

        $this->response($data);
    }

    public function searchArea() {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'keyword' => 'required|trim|min_length[1]',
        ]);

        $data = $this->reportService->searchArea($params);

        $this->response($data);
    }

    /**
     * @throws Exception
     */
    public function reportConfig()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'type' => 'required|in_list[1,2,3,4,10,11,12]',
        ]);

        $data = $this->reportService->reportConfig($params);

        $this->response($data);
    }

    /**
     * 生成报告
     * @param $params['city_id'] int    城市ID
     * @param $params['title']   string 报告标题
     * @param $params['type']    int    报告类型 1，路口分析报告；2，路口优化对比报告；3，城市分析报告（周报）；4，城市分析报告（月报）10,路口报告,11,干线报告,12,区域报告
     * @param $params['file']    binary 二进制文件
     * @param $params['timerange'] string 时间区间 : 2019-10-01~2019-10-10
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
        $data = [
            'city_id' => intval($params['city_id']),
            'title'   => trim($params['title']),
            'type'    => intval($params['type']),
        ];
        $data = $this->reportService->generate($data);

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

    public function reportProxy()
    {
        $params = $this->input->get(null, true);

        $opts = array('http' =>
            array(
                'method' => 'GET',
                'max_redirects' => '0',
                'ignore_errors' => '1'
            )
        );
        $url = base64_decode($params['url']);
        $context = stream_context_create($opts);
        $stream = fopen($url, 'r', false, $context);
        $body = stream_get_contents($stream);
        fclose($stream);
        foreach (get_headers($url) as $k=>$v){
            header($v);
        }

        echo $body;
        exit();

    }

    /**
     * @throws Exception
     */
    public function downReport()
    {
        $params = $this->input->get(null, true);
        if (empty($params['key'])) {
            throw new \Exception('key不能为空！', ERR_PARAMETERS);
        }

        $this->reportService->downReport($params);
    }
}