<?php
/***************************************************************
# 概览类
#    概览页---路口概况
#    概览页---路口列表
#    概览页---运行概况
#    概览页---拥堵概览
#    概览页---获取token
# user:ningxiangbing@didichuxing.com
# date:2018-07-25
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Overview extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('overview_model');
    }

    /**
    * 获取路口列表
    * @param city_id    interger Y 城市ID
    * @param date       string   Y 日期 yyyy-mm-dd
    * @param time_point stirng   Y 时间点 H:i:s
    * @return json
    */
    public function junctionsList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        if(!isset($params['date']) ||
            date('Y-m-d', strtotime($params['date'])) !== $params['date']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The format of date is wrong.';
            return;
        }

        $data['date'] = $params['date'];

        if(!isset($params['time_point']) ||
            date('H:i:s', strtotime($params['time_point'])) !== $params['time_point']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The format of time_point is wrong.';
            return;
        }

        $data['time_point'] = $params['time_point'];

        $data = $this->overview_model->junctionsList($data);

        $this->response($data);
    }

    /**
    * 运行情况
    * @param
    * @return json
    */
    public function operationCondition()
    {


    }

    /**
    * 路口概况
    * @param
    * @return json
    */
    public function junctionSurvey()
    {


    }

    /**
    * 拥堵占比
    * @param
    * @return json
    */
    public function stopDelayRatio()
    {


    }

    /**
    * 拥堵数量
    * @param
    * @return json
    */
    public function stopDelayNum()
    {


    }

    /**
    * 获取token
    * @param
    * @return json
    */
    public function getToken()
    {


    }
}
