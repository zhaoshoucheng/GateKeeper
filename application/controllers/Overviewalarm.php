<?php
/***************************************************************
# 概览页报警类
#    7日报警
#    今日报警
#    实时报警列表
# user:ningxiangbing@didichuxing.com
# date:2018-07-25
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Overviewalarm extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('overviewalarm_model');
    }

    /**
    * 获取今日报警占比
    * @param city_id    interger Y 城市ID
    * @param date       string   Y 日期 yyyy-mm-dd
    * @param time_point stirng   Y 时间点 H:i:s
    * @return json
    */
    public function todayAlarmInfo()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'    => 'min:1',
                'date'       => 'nullunable',
                'time_point' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id'    => intval($params['city_id']),
            'date'       => date('Y-m-d', strtotime(strip_tags(trim($params['date'])))),
            'time_point' => date('H:i:s', strtotime(strip_tags(trim($params['time_point'])))),
        ];

        $result = $this->overviewalarm_model->todayAlarmInfo($data);

        return $this->response($result);
    }

    /**
    * 获取七日报警变化
    * @param
    * @return json
    */
    public function sevenDaysAlarmChange()
    {


    }

    /**
    * 获取实时报警列表
    * @param
    * @return json
    */
    public function realTimeAlarmList()
    {


    }
}
