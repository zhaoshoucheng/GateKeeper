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


    }

    /**
    * 获取停车TOP20
    * @param
    * @return json
    */
    public function stopTimeCycleTopList()
    {


    }
}