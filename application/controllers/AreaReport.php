<?php
/**
 * 城市区域分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AreaReportService;
use Services\RoadReportService;
use Services\AreaService;

class AreaReport extends MY_Controller
{
    protected $junctionService;

    public function __construct()
    {
        parent::__construct();
        $this->config->load('report_conf');

        $this->areaReportService = new AreaReportService();
        $this->roadReportService = new RoadReportService();
        $this->areaService = new AreaService();
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
    public function queryTopPI() {
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $params['start_date'] = $params['start_time'];
        $params['end_date'] = $params['end_time'];

        $data = $this->areaReportService->queryTopPI($params);
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
//            'morning_rush_time' => 'required|trim|regex_match[/\d{2}:\d{2}~\d{2}:\d{2}/]',
//            'evening_rush_time' => 'required|trim|regex_match[/\d{2}:\d{2}~\d{2}:\d{2}/]',
        ],$params);

        //FIXME 后端计算早晚高峰
        //查询区域路口的平均指标
        $data  = $this->areaReportService->QueryAreaQuotaInfo($params['city_id'],$params['area_id'],$params['start_time'],$params['end_time']);

        //格式化为前端要求的格式
        $chartDatas = $this->roadReportService->transRoadQuota2Chart($data);

        $mrushTime = $this->roadReportService->getMorningRushHour($chartDatas[1]);
        $erushTime = $this->roadReportService->getEveningRushHour($chartDatas[1]);

        $morningTime = [$mrushTime['s'],$mrushTime['e']];
        $eveningTime = [$erushTime['s'],$erushTime['e']];

        $areaInfo = $this->areaReportService->queryAreaAlarm($params['city_id'],$params['area_id'],$params['start_time'],$params['end_time'],implode("~",$morningTime),implode("~",$eveningTime));

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
        $data  = $this->areaReportService->QueryAreaQuotaInfo($params['city_id'],$params['area_id'],$params['start_time'],$params['end_time']);
//        $data  = $this->areaReportService->getJunctionsAllQuotaEs($params['city_id'],$params['area_id'],$params['start_time'],$params['end_time']);

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

    //查询区域轨迹热力图
    public function queryAreaThermograph(){
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);

        //查询早高峰
        //查询区域路口的平均指标
        $data  = $this->areaReportService->QueryAreaQuotaInfo($params['city_id'],$params['area_id'],$params['start_time'],$params['end_time']);

        //格式化为前端要求的格式
        $chartDatas = $this->roadReportService->transRoadQuota2Chart($data);

        $mrushTime = $this->roadReportService->getMorningRushHour($chartDatas[1]);

        //查询taskID
        $taskID = $this->areaReportService->queryThermographTaskID($params['city_id'],$params['area_id'],$params['start_time'],$params['end_time'],1);

        $url = "http://100.90.164.31:8036/figure-service/gift-urls";

        $ret = $this->areaReportService->queryThermograph($url,$taskID['task_id'],$mrushTime);

        //查询视频
        $taskID = $this->areaReportService->queryThermographTaskID($params['city_id'],$params['area_id'],$params['start_time'],$params['end_time'],2);

        $url = "http://100.90.164.31:8036/video-service/gift-urls";

        $map4ret = $this->areaReportService->queryThermograph($url,$taskID['task_id'],$mrushTime);

        $this->response([
            'png'=>$ret,
            'mp4'=>$map4ret,
            'info'=>[
                'png_info'=>"下图展示了分析区域".$taskID['date']."早高峰的轨迹热力演变图,图中路段的不同颜色代表了滴滴车辆的平均运行速度,轨迹回放视频与轨迹热力演变图清楚的展示分析区域早高峰交通运行情况的演变过程",
                'mp4_info'=>""
            ],
        ]);

    }


    //更新区域轨迹热力图状态
    public function updateAreaThermographStatus(){
        //查询全部status为0的任务
        $tasks = $this->areaReportService->queryUnreadyTask();
        $giftUrl = "http://100.90.164.31:8036/figure-service/task-status";
        $videoUrl = "http://100.90.164.31:8036/video-service/task-status";
        foreach ($tasks as $t){
            $rg = httpGET($giftUrl."?taskId=".$t);
            $rg = json_decode($rg,true);
            if(isset($rg['errorCode']) && $rg['errorCode']==0){
                $this->areaReportService->updateUnreadyTasks($t,$rg['data']['taskStatus']);
                continue;
            }
            $rv = httpGET($videoUrl."?taskId=".$t);
            $rv = json_decode($rv,true);

            if(isset($rv['errorCode']) && $rv['errorCode']==0){
                $this->areaReportService->updateUnreadyTasks($t,$rv['data']['taskStatus']);
            }
        }

        $this->response($tasks);

    }

    //创建区域轨迹热力图
    public function createAreaThermograph(){
        $params = $this->input->get(null, true);
        $this->get_validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|min_length[1]',
            'date'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ],$params);
        $areaInfo = $this->areaService->getAreaDetail([
            'city_id' => $params['city_id'],
            'area_id' => $params['area_id'],
        ]);

        //计算区域边框
        $maxlng=0;
        $maxlat=0;
        $minlng=9999999;
        $minlat=9999999;

        //限制区域大小
        foreach ($areaInfo['junction_list'] as  $j ){
            if($j['lng'] > $maxlng){
                $maxlng = $j['lng'];
            }
            if($j['lat'] > $maxlat){
                $maxlat = $j['lat'];
            }
            if($j['lat'] < $minlat){
                $minlat = $j['lat'];
            }
            if($j['lng'] < $minlng){
                $minlng = $j['lng'];
            }
        }
        if(($maxlng-$minlng)>0.15 || ($maxlat-$minlat)>0.2){
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = "区域过大";
            return;
        }
        //暂时写死
        $hour = ["07:00:00","10:00:00"];
        $params['hour'] = implode(",", $hour);


        $reqdata = [
            "cityId"=>$params['city_id'],
            "consumer"=>"report",
            "dateString"=>$params['date'],
            "deadlineString"=>$params['date'],
            "hourSpan"=>$hour,
            "isPublic"=>"true",
            "isShowColorbar"=>"false",
            "polygon"=>$maxlng.",".$maxlat.";".$maxlng.",".$minlat.";".$minlng.",".$minlat.";".$minlng.",".$maxlat,
            "figureTitle"=>$areaInfo['area_name'],
            "autoStart"=>"true"
        ];
        //创建热力轨迹图
        $url = "http://100.90.164.31:8036/figure-service/create-task";
//        $giftres = httpPOST($url,$reqdata,0,'json');
        $giftres="";
//        $ret = $this->areaReportService->saveThermograph($params,$giftres);
        //创建热力视频

        $mp4url = "http://100.90.164.31:8036/video-service/create-task";
        $reqdata['videoTitle'] = $areaInfo['area_name'];
        $mp4res = httpPOST($mp4url,$reqdata,0,'json');
//        var_export($mp4res);
        $ret = $this->areaReportService->saveThermograph($params,$mp4res);

        $this->response(['gift'=>$giftres,'mp4'=>$mp4res]);

    }

}