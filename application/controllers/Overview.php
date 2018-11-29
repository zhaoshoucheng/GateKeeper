<?php
/***************************************************************
 * # 概览类
 * #    概览页---路口概况
 * #    概览页---路口列表
 * #    概览页---运行概况
 * #    概览页---拥堵概览
 * #    概览页---获取token
 * # user:ningxiangbing@didichuxing.com
 * # date:2018-07-25
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OverviewService;

class Overview extends MY_Controller
{
    protected $overviewService;

    public function __construct()
    {
        parent::__construct();

        $this->overviewService = new OverviewService();
    }

    /**
     * 获取路口列表
     *
     * @throws Exception
     */
    public function junctionsList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

        $params['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->overviewService->junctionsList($params);

        $this->response($data);
    }

    /**
     * 运行情况
     *
     * @throws Exception
     */
    public function operationCondition()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

        $params['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->overviewService->operationCondition($params);

        $this->response($data);

    }

    /**
     * 路口概况
     *
     * @throws Exception
     */
    public function junctionSurvey()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

        $params['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->overviewService->junctionSurvey($params);

        $this->response($data);

    }

    /**
     * 拥堵概览
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 当前时间点 格式：H:i:s 例：09:10:00
     * @return json
     * @throws Exception
     */
    public function getCongestionInfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id'    => 'required|is_natural_no_zero',
            'date'       => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'time_point' => 'exact_length[8]|regex_match[/\d{2}:\d{2}:\d{2}/]',
        ]);

        $params['date']       = $params['date'] ?? date('Y-m-d');
        $params['time_point'] = $params['time_point'] ?? date('H:i:s');

        $result = $this->overviewService->getCongestionInfo($params);

        $this->response($result);
    }

    /**
     * 获取token
     */
    public function getToken()
    {
        $data = $this->overviewService->getToken();

        $this->response($data);
    }

    /**
     * 验证token
     *
     * @throws Exception
     */
    public function verifyToken()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'tokenval' => 'required|trim|min_length[1]',
        ]);

        $data = $this->overviewService->verifyToken($params);

        $this->response($data);
    }

    /**
     * 获取当前时间和日期
     */
    public function getNowDate()
    {
        $data = $this->overviewService->getNowDate();

        $this->response($data);
    }
}
