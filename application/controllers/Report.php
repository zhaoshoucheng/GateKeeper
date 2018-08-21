<?php
/**
 * 报告相关模块
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

    public function junctionReportConfig()
    {
        return [
            'overview'=>[
                'title'=>'路口概览',
                'desc'=>'包括扫描路口在于路网中的区位',
                'items'=>[
                    [
                        'id'=>1,
                        'title'=>'各方向最大排队长度时间变化规律',
                        'desc'=>'平均各个日期各个方向最大排队长度在24小时中的随时间变化规律',
                        'api'=>'Report/queuePosition',

                    ],
                    [
                        'id'=>2,
                        'title'=>'各方向延误时间变化规律',
                        'desc'=>'平均各个日期各个方向最大延误在24小时中随时间变化规律',
                        'api'=>'Report/stopDelay'
                    ]
                ]
            ],
            'schedule'=>[],
            'summary'=>[]
        ];

    }

    public function junctionComparisonReportConfig()
    {

    }

    public function weekReportConfig()
    {

    }

    public function monthReportConfig()
    {

    }
}