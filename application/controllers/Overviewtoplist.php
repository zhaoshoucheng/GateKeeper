<?php
/***************************************************************
# TOP列表类
#    概览页-延误TOP20
#    概览页-停车TOP20
# user:ningxiangbing@didichuxing.com
# date:2018-07-25
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Overviewtoplist extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('overviewtoplist_model');
    }

    /**
    * 获取延误TOP20
    * @param
    * @return json
    */
    public function stopDelayTopList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        if(!isset($params['pagesize'])) {
            $data['pagesize'] = 20;
        } elseif (!is_numeric($params['pagesize'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of pagesize must be integer.';
            return;
        } elseif($params['pagesize'] <= 0) {
            $data['pagesize'] = 20;
        } else {
            $data['pagesize'] = $params['pagesize'];
        }

        $data = $this->overviewtoplist_model->stopDelayTopList($data);

        return $this->response($data);

    }

    /**
    * 获取停车TOP20
    * @param
    * @return json
    */
    public function stopTimeCycleTopList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        if(!isset($params['pagesize'])) {
            $data['pagesize'] = 20;
        } elseif (!is_numeric($params['pagesize'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of pagesize must be integer.';
            return;
        } elseif($params['pagesize'] <= 0) {
            $data['pagesize'] = 20;
        } else {
            $data['pagesize'] = $params['pagesize'];
        }

        $data = $this->overviewtoplist_model->stopTimeCycleTopList($data);

        return $this->response($data);
    }
}