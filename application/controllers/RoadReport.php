<?php
/**
 * 干线分析报告模块
 */


defined('BASEPATH') OR exit('No direct script access allowed');



class RoadReport extends MY_Controller
{
    protected $junctionService;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('report_conf');
    }

    public function introduction(){}
    public function queryRoadDataComparison(){}
    public function queryRoadQuotaData(){}
    public function queryRoadCoordination(){}
    public function queryRoadCongestion(){}
    public function queryRoadAlarm(){}
    public function queryQuotaRank(){}

}