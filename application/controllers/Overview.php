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
        $this->load->model('redis_model');
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

        $data['date'] = $params['date'] ?? date('Y-m-d');

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
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->overview_model->operationCondition($data);

        $this->response($data);

    }

    /**
    * 路口概况
    * @param
    * @return json
    */
    public function junctionSurvey()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->overview_model->junctionSurvey($data);

        $this->response($data);

    }

    /**
    * 拥堵概览
    * @param city_id    interger Y 城市ID
    * @param date       string   Y 日期 yyyy-mm-dd
    * @param time_point stirng   Y 时间点 H:i:s
    * @return json
    */
    public function getCongestionInfo()
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

        $result = $this->overview_model->getCongestionInfo($data);

        return $this->response($result);
    }

    /**
    * 获取token
    * @param
    * @return json
    */
    public function getToken()
    {
        $token = md5(time() . random_string());

        $this->redis_model->setData('Token_' . $token, $token);
        $this->redis_model->setExpire('Token_' . $token, 60 * 30);

        $data = [$token];

        $this->response($data);
    }
}
