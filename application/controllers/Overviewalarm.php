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
    * @param
    * @return json
    */
    public function todayAlarmRatio()
    {


    }

    /**
    * 获取今日报警数量
    * @param
    * @return json
    */
    public function todayAlarmNum()
    {


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
