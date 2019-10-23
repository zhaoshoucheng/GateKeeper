<?php
/**
 * 城市区域分析报告模块
 */



defined('BASEPATH') OR exit('No direct script access allowed');



class AreaReport extends MY_Controller
{
    protected $junctionService;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('report_conf');
    }

    public function introduction(){}
    public function queryAreaDataComparison(){}
    public function queryAreaCongestion(){}
    public function queryAreaAlarm(){}
    public function queryAreaQuotaData(){}
    public function queryQuotaRank(){}

}