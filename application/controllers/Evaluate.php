<?php
/***************************************************************
# 评估类
#    评估页-获取路口列表
# user:ningxiangbing@didichuxing.com
# date:2018-07-25
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Evaluate extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('evaluate_model');
    }

    /**
    * 获取路口列表
    * @param 
    * @return json
    */
    public function junctionList()
    {


    }
}