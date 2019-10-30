<?php
/**
 * 城市区域分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AreaReportService;

class AreaReport extends MY_Controller
{
    protected $junctionService;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('report_conf');

        $this->areaReportService = new AreaReportService();
    }

    public function introduction() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        $data = $this->areaReportService->introduction($params);
        $this->response($data);
    }

    public function queryAreaDataComparison() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        $data = $this->areaReportService->queryAreaDataComparison($params);
        $this->response($data);
    }
    public function queryAreaCongestion() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        $data = $this->areaReportService->queryAreaCongestion($params);
        $this->response($data);
    }
    public function queryQuotaRank() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        $data = $this->areaReportService->queryQuotaRank($params);
        $this->response($data);
    }
    //区域报警总结
    public function queryAreaAlarm(){
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'morning_rush_time' => 'required|trim|regex_match[/\d{2}:\d{2}~\d{2}:\d{2}/]',
            'evening_rush_time' => 'required|trim|regex_match[/\d{2}:\d{2}~\d{2}:\d{2}/]',
        ],$params);

        $areaInfo = $this->areaReportService->queryAreaAlarm($params['city_id'],$params['area_id'],$params['start_time'],$params['end_time'],$params['morning_rush_time'],$params['evening_rush_time']);

        $this->response($areaInfo);

    }
    //区域运行情况
    public function queryAreaQuotaData(){
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        //查询区域路口的平均指标
        $data  = $this->areaReportService->QueryAreaQuotaInfo($params['city_id'],$params['road_id'],$params['start_time'],$params['end_time']);

        //格式化为前端要求的格式
        $chartDatas = $this->roadReportService->transRoadQuota2Chart($data);

        $mrushTime = $this->roadReportService->getMorningRushHour($chartDatas[1]);
        $erushTime = $this->roadReportService->getEveningRushHour($chartDatas[1]);

        //计算早晚高峰停车次数
        $avgStopCyclem= $this->roadReportService->queryChartAvg($mrushTime['s'],$mrushTime['e'],$chartDatas[0]);
        $avgStopCyclee= $this->roadReportService->queryChartAvg($erushTime['s'],$erushTime['e'],$chartDatas[0]);
        //计算早晚高峰停车延误
        $avgStopDelaym= $this->roadReportService->queryChartAvg($mrushTime['s'],$mrushTime['e'],$chartDatas[1]);
        $avgStopDelaye= $this->roadReportService->queryChartAvg($erushTime['s'],$erushTime['e'],$chartDatas[1]);

        //计算早晚高峰行驶速度
        $avgSpeedm= $this->roadReportService->queryChartAvg($mrushTime['s'],$mrushTime['e'],$chartDatas[2]);
        $avgSpeede= $this->roadReportService->queryChartAvg($erushTime['s'],$erushTime['e'],$chartDatas[2]);

        $desc="下图利用滴滴数据绘制了该区域全天24小时各项运行指标(车均停车次数、车均停车延误、车均行驶速度、PI)。通过数据分析,该区域的早高峰约为".$mrushTime['s']."~".$mrushTime['e'].",晚高峰约为".$erushTime['s']."~".$erushTime['e']."。与平峰相比,早晚高峰的停车次数达到".round(($avgStopCyclem+$avgStopCyclee)/2,2)."次/车/路口,停车延误接近".round(($avgStopDelaym+$avgStopDelaye)/2,2)."秒/车/路口,车均行驶速度也达到".round(($avgSpeedm+$avgSpeede)/2,2)."千米/小时左右";


        $this->response(['info'=>['instructions'=>"报告采用综合评估指数（PI）来分析路口整体及各维度交通运行情况XXXX",'desc'=>$desc,'morning_rush_time'=>$mrushTime['s']."~".$mrushTime['e'],"evening_rush_time"=>$erushTime['s']."~".$erushTime['e']],'charts'=>$chartDatas]);
    }

}