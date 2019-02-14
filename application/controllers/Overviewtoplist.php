<?php
/***************************************************************
# TOP列表类
#    概览页-延误TOP20
#    概览页-停车TOP20
# user:ningxiangbing@didichuxing.com
# date:2018-07-25
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OverviewService;

class Overviewtoplist extends MY_Controller
{
    protected $overviewService;

    public function __construct()
    {
        parent::__construct();

        $this->overviewService = new OverviewService();
    }

    /**
     * 获取延误TOP20
     * @param $params['city_id']  int    Y 城市ID
     * @param $params['date']     string N 日期 yyyy-mm-dd
     * @param $params['pagesize'] int    N 获取数量
     * @throws Exception
     */
    public function stopDelayTopList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'pagesize' => 'is_natural_no_zero'
        ]);

        $params['date'] = $params['date'] ?? date('Y-m-d');
        $params['pagesize'] = $params['pagesize'] ?? 20;
        $data = $this->overviewService->stopDelayTopList($params,$this->userPerm);
        $data = !empty($data) ? $data : (object)[];
        $this->response($data);
    }

    /**
     * 获取停车次数TOP20
     * @param $params['city_id']  int    Y 城市ID
     * @param $params['date']     string N 日期 yyyy-mm-dd
     * @param $params['pagesize'] int    N 获取数量
     * @throws Exception
     */
    public function stopTimeCycleTopList()
    {
        $params = $this->input->post();

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'pagesize' => 'is_natural_no_zero'
        ]);

        $params['date'] = $params['date'] ?? date('Y-m-d');
        $params['pagesize'] = $params['pagesize'] ?? 20;

        $data = $this->overviewService->stopTimeCycleTopList($params,$this->userPerm);
        $data = !empty($data) ? $data : (object)[];
        $this->response($data);
    }
}