<?php


/***************************************************************
# 快速路需求
# user:zhuyewei@didichuxing.com
# date:2019-11-18
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\ExpresswayService;

class Expressway extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->expresswayService = new ExpresswayService();

    }

    /*
     * 快速路概览
     * */
    public function overview(){
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        //查询路口列表
        $data = $this->expresswayService->queryOverview($params['city_id']);

        $this->response($data);
    }

    /*
     * 快速路拥堵概览
     * */
    public function stopDelayTopList(){
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);
        //查询路口列表
        $data = $this->expresswayService->queryOverview($params['city_id']);

        $this->response($data);
    }

    /*
     * 快速路指标详情
     * */
    public function detail(){

    }

    /*
     * 快速路报警列表
     * */
    public function alarmlist(){

    }




}