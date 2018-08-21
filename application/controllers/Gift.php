<?php
/***************************************************************
# 路口类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Gift extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('junction_model');
        $this->load->model('timing_model');
        $this->load->config('nconf');
    }

    public function Upload(){

    }

}
