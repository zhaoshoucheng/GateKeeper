<?php
/***************************************************************
# 干线绿波回调函数
# user:ningxiangbing@didichuxing.com
# date:2018-07-24
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialgreenwavecallback extends MY_Controller
{
	public function __construct()
    {
        parent::__construct();
        $this->load->model('redis_model');
    }

    /**
    * 将数据写入redis
    * @param data  数据
    * @param token 唯一标识
    */
    public function fillData()
    {
    	$params = $this->input->post();

    	if (!empty($params['data']) && !empty($params['token'])) {
    		$this->redis_model->setData($params['token'], json_encode($params['data']));
    	}

    	return [];
    }

    public function getData()
    {
    	$params = $this->input->post();
    	$res = $this->redis_model($params['token']);
    	echo "<pre>";print_r($res);
    	var_dump($res);
    }
}