<?php
/**
 * 周报模块
 */


class Report extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('report_model');
        $this->load->library('EvaluateQuota');
    }

    public function test()
    {
        $evaluate = new EvaluateQuota();
        //1,model层获取基准数据
        $jdata = $this->report_model->test();
        //library层处理具体数据
        $ret = $evaluate->getJunctionDurationDelay($jdata,"start","end");
        return $this->response($ret);
    }
}