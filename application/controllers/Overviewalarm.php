<?php
/***************************************************************
 * # 概览页报警类
 * #    7日报警
 * #    今日报警
 * #    实时报警列表
 * # user:ningxiangbing@didichuxing.com
 * # date:2018-07-25
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OverviewService;

class Overviewalarm extends MY_Controller
{
    protected $overviewService;

    public function __construct()
    {
        parent::__construct();

        $this->overviewService = new OverviewService();
    }

    /**
     * 获取今日报警概览
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @throws Exception
     * @return json
     */
    public function todayAlarmInfo()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'time_point' => 'exact_length[8]|regex_match[/\d{2}:\d{2}:\d{2}/]',
        ]);

        $params['date']       = $params['date'] ?? date('Y-m-d');
        $params['time_point'] = $params['time_point'] ?? date('H:i:s');

        $data = $this->overviewService->todayAlarmInfo($params);

        $this->response($data);
    }

    /**
     * 获取七日报警变化
     * 规则：取当前日期前六天的报警路口数+当天到现在时刻的报警路口数
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @throws Exception
     * @return json
     */
    public function sevenDaysAlarmChange()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'time_point' => 'exact_length[8]|regex_match[/\d{2}:\d{2}:\d{2}/]',
        ]);

        $params['date']       = $params['date'] ?? date('Y-m-d');
        $params['time_point'] = $params['time_point'] ?? date('H:i:s');

        $data = $this->overviewService->sevenDaysAlarmChange($params,$this->userPerm);

        $this->response($data);
    }

    /**
     * 获取实时报警列表
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 当前时间点 格式：H:i:s 例：09:10:00
     * @return json
     * @throws Exception
     */
    public function realTimeAlarmList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'date' => 'exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'time_point' => 'exact_length[8]|regex_match[/\d{2}:\d{2}:\d{2}/]',
        ]);

        $params['date']       = $params['date'] ?? date('Y-m-d');
        $params['time_point'] = $params['time_point'] ?? date('H:i:s');

        $result = $this->overviewService->realTimeAlarmList($params);

        return $this->response($result);
    }
}
