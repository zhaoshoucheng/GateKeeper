<?php
/**
 * 报警分析接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-19
 */

namespace Services;

use Didi\Cloud\Collection\Collection;
use Services\CommonService;

class AlarmanalysisService extends BaseService
{
    protected $commonService;
    public function __construct()
    {
        parent::__construct();

        // load model
        $this->load->model('alarmanalysis_model');
        $this->load->model('timing_model');
        $this->config->load('realtime_conf');
        $this->load->model('waymap_model');
        $this->commonService = new CommonService();
    }

    /**
     * 城市/路口报警分析接口
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 开始日期与结束日期一致认为查询当天从0点到当前整点的数据
     * @param $params['alarm_type']           int    N 报警类型
     * @return array
     */
    public function alarmAnalysis($params)
    {
        if (empty($params)) {
            return (object)[];
        }

        if ($params['start_time'] == $params['end_time']) {
            // 获取小时为纬度聚合
            return $this->getDailyAlarmAnalysis($params);
        }else if(isset($params['time_range'])){
            //济南需求,新增过滤逻辑
            return $this->getNewTimeAlarmAnalysis($params);
        } else {
            // 获取天为纬度聚合
            return $this->getTimeAlarmAnalysis($params);
        }
    }


    /**
     * 城市报警分析----多天报警发生时段分布（柱状）
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 开始日期与结束日期一致认为查询当天从0点到当前整点的数据
     * @param $params['alarm_type']        int    N 报警问题 0=全部 1=过饱和 2=溢流 3=失衡，默认全部
     * @return array
     */
    public function manyDayAlarmTimeDistribution($params)
    {
        if (empty($params)) {
            return (object)[];
        }
        $result = $this->getDailyAlarmAnalysis($params);
        return $result;
    }

    // 多天城市数据下载
    public function manyDayCityDataDownload($params)
    {
        if (empty($params)) {
            return (object)[];
        }

        //日期统计
        $downData = [];

        //dateAlarm，日期所有数据不划分时段
        $dataALarmParams = $params;
        $dataALarmParams["start_hour"] = "00:00:00"; //设置为一天  
        $dataALarmParams["end_hour"] = "23:59:59"; //设置为一天
        $hourList = $this->getTimeAlarmAnalysis($dataALarmParams);

        //junctionTop，选中当天的排行
        $juncTopParams = $params;
        $juncTopParams["start_time"] = $juncTopParams["select_time"]; //设置为一天
        $juncTopParams["end_time"] = $juncTopParams["select_time"]; //设置为一天
        $juncTopParams["start_hour"] = "00:00:00";
        $juncTopParams["end_hour"] = "23:59:59";
        if(empty($juncTopParams["top_num"])){
            $juncTopParams["top_num"] = 50;
        }
        $junctionTop = $this->alarmJunctionTop($juncTopParams);

        //cityName
        $cityName = $this->waymap_model->getCityName($params['city_id']);
        $timeRange = date("Y.m.d",strtotime($params["start_time"]))."-".date("Y.m.d",strtotime($params["end_time"]));
        $frequencyType = $this->config->item('frequency_type');
        $alarmType = $this->config->item('junction_alarm_type');
        $frequencyTypeName = $frequencyType[$params['frequency_type']]??"全部";
        $alarmTypeName = $alarmType[$params['alarm_type']]??"全部";

        //小时数据格式
        $hourFormatList = [];
        foreach($hourList as $hour=>$hourAlarmList){
            $fullStartTime = date("Y-m-d")." ".$hour.":00";
            if($params['start_time']==$params['end_time']){
                $hourRange = date("H:i",strtotime($fullStartTime))."-".date("H:i",strtotime($fullStartTime)+60*60);
            }else{
                $hourRange = date("Y.m.d",strtotime($hour));
            }   
            $hourCount = [];
            $hourCount["total"] = $hourAlarmList["count"];
            if(!empty($hourAlarmList["list"])){
                foreach($hourAlarmList["list"] as $alarmStat){
                    $hourCount[$alarmStat["key"]] = $alarmStat["value"];
                }
            }
            if($params['alarm_type']>0){
                $hourFormatList[] = [
                    $hourRange,
                    $hourCount[$params['alarm_type']]??0,
                ];
            }else{
                $hourFormatList[] = [
                    $hourRange,
                    $hourCount["total"]??0,
                    $hourCount["2"]??0,
                    $hourCount["1"]??0,
                    $hourCount["3"]??0,
                ];
            }
        }
        $downData = [
            ["城市：".$cityName],
            ["时间范围：".$timeRange],
            ["常偶发报警：".$frequencyTypeName],
            ["报警类型：".$alarmTypeName],
        ];
        if($params['alarm_type']>0){
            $downData[] = ["日期",$alarmTypeName."次数"];
        }else{
            $downData[] = ["日期","总报警次数","溢流次数","过饱和次数","失衡次数"];
        }
        $downData = array_merge($downData,$hourFormatList);
        
        //top信息格式
        $topTimeRange = date("Y.m.d",strtotime($params["select_time"]));
        $downData[] = ["日期：".$topTimeRange];
        $downData[] = ["报警路口TOP".$params["top_num"]."排名"];
        $downData[] = ["序号","路口名称","报警次数"];
        foreach ($junctionTop as $topIndex => $juncStat) {
            $downData[] = [++$topIndex,$juncStat["junction_name"],$juncStat["alarm_count"]];
        }
        $excelData[] = ["sheet_name"=>"城市报警发生日期统计","data"=>$downData,];


        //时段统计
        $downData = [];

        //时段统计为全天
        $hourALarmParams = $params;
        $hourALarmParams["start_hour"] = "00:00:00"; //设置为一天  
        $hourALarmParams["end_hour"] = "23:59:59"; //设置为一天
        $hourList = $this->getDailyAlarmAnalysis($hourALarmParams);
        // print_r($hourList);exit;
        //junctionTop
        $juncTopParams = $params;
        $juncTopParams["start_hour"] = $juncTopParams["start_hour"].":00";
        $juncTopParams["end_hour"] = $juncTopParams["end_hour"].":00";
        if($juncTopParams["end_hour"]=="24:00:00"){
            $juncTopParams["end_hour"] = "23:59:59";
        }
        if(empty($juncTopParams["top_num"])){
            $juncTopParams["top_num"] = 50;
        }
        $junctionTop = $this->alarmJunctionTop($juncTopParams);

        //cityName
        $cityName = $this->waymap_model->getCityName($params['city_id']);
        $timeRange = date("Y.m.d",strtotime($params["start_time"]))."-".date("Y.m.d",strtotime($params["end_time"]));
        $frequencyType = $this->config->item('frequency_type');
        $alarmType = $this->config->item('junction_alarm_type');
        $frequencyTypeName = $frequencyType[$params['frequency_type']]??"全部";
        $alarmTypeName = $alarmType[$params['alarm_type']]??"全部";

        //小时数据格式
        $hourFormatList = [];
        foreach($hourList as $hour=>$hourAlarmList){
            $fullStartTime = date("Y-m-d")." ".$hour.":00";
            if($params['start_time']==$params['end_time']){
                $hourRange = date("H:i",strtotime($fullStartTime))."-".date("H:i",strtotime($fullStartTime)+60*60);
            }else{
                $hourRange = $hour;
            }   
            $hourCount = [];
            $hourCount["total"] = $hourAlarmList["count"]??0;
            if(!empty($hourAlarmList["list"])){
                foreach($hourAlarmList["list"] as $alarmStat){
                    $hourCount[$alarmStat["key"]] = $alarmStat["value"];
                }
            }
            if($params['alarm_type']>0){
                $hourFormatList[] = [
                    $hourRange,
                    $hourCount[$params['alarm_type']]??0,
                ];
            }else{
                $hourFormatList[] = [
                    $hourRange,
                    $hourCount["total"]??0,
                    $hourCount["2"]??0,
                    $hourCount["1"]??0,
                    $hourCount["3"]??0,
                ];
            }
        }
        $downData = [
            ["城市：".$cityName],
            ["时间范围：".$timeRange],
            ["常偶发报警：".$frequencyTypeName],
            ["报警类型：".$alarmTypeName],
        ];
        if($params['alarm_type']>0){
            $downData[] = ["时刻",$alarmTypeName."次数"];
        }else{
            $downData[] = ["时刻","总报警次数","溢流次数","过饱和次数","失衡次数"];
        }
        $downData = array_merge($downData,$hourFormatList);
        
        //top信息格式
        $topTimeRange = $params["start_hour"]."-".$params["end_hour"];
        $downData[] = ["时刻：".$topTimeRange];
        $downData[] = ["报警路口TOP".$params["top_num"]."排名"];
        $downData[] = ["序号","路口名称","报警次数"];
        foreach ($junctionTop as $topIndex => $juncStat) {
            $downData[] = [++$topIndex,$juncStat["junction_name"],$juncStat["alarm_count"]];
        }
        $excelData[] = ["sheet_name"=>"城市报警发生时段统计","data"=>$downData,];
        
        $this->commonService->excelDownload($excelData);
    }

    /**
     * 路口报警分析 - 数据详情下载
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd
     * @param $params['start_hour']        string Y 选中的开始时间，全天传 0:00
     * @param $params['end_hour']          string Y 选中的结束时间，全天传 24:00
     * @param $params['alarm_type']        int    Y 报警问题 0=全部 1=过饱和 2=溢流 3=失衡，默认全部
     * @param $params['top_num']        int    N 报警数top，不传则默认50
     * @return binary
     */
    public function cityDataDownload($params)
    {
        if (empty($params)) {
            return (object)[];
        }

        if($params['start_time']!=$params['end_time']){
            return $this->manyDayCityDataDownload($params);
        }

        //时段统计
        //hourList，不需要时段查询
        $hourList = $this->getDailyAlarmAnalysis($params);
        //junctionTop，需要时段查询
        $juncTopParams = $params;
        $juncTopParams["start_hour"] = $juncTopParams["start_hour"].":00";
        $juncTopParams["end_hour"] = $juncTopParams["end_hour"].":00";
        if($juncTopParams["end_hour"]=="24:00:00"){
            $juncTopParams["end_hour"] = "23:59:59";
        }
        if(empty($juncTopParams["top_num"])){
            $juncTopParams["top_num"] = 50;
        }
        $junctionTop = $this->alarmJunctionTop($juncTopParams);

        //cityName
        $cityName = $this->waymap_model->getCityName($params['city_id']);
        $timeRange = date("Y.m.d",strtotime($params["start_time"]))."-".date("Y.m.d",strtotime($params["end_time"]));
        $frequencyType = $this->config->item('frequency_type');
        $alarmType = $this->config->item('junction_alarm_type');
        $frequencyTypeName = $frequencyType[$params['frequency_type']]??"全部";
        $alarmTypeName = $alarmType[$params['alarm_type']]??"全部";

        //小时数据格式
        $hourFormatList = [];
        foreach($hourList as $hour=>$hourAlarmList){
            $fullStartTime = date("Y-m-d")." ".$hour.":00";
            if($params['start_time']==$params['end_time']){
                $hourRange = date("H:i",strtotime($fullStartTime))."-".date("H:i",strtotime($fullStartTime)+60*60);
            }else{
                $hourRange = $hour;
            }   
            $hourCount = [];
            $hourCount["total"] = $hourAlarmList["count"];
            if(!empty($hourAlarmList["list"])){
                foreach($hourAlarmList["list"] as $alarmStat){
                    $hourCount[$alarmStat["key"]] = $alarmStat["value"];
                }
            }
            if($params['alarm_type']>0){
                $hourFormatList[] = [
                    $hourRange,
                    $hourCount[$params['alarm_type']]??0,
                ];
            }else{
                $hourFormatList[] = [
                    $hourRange,
                    $hourCount["total"]??0,
                    $hourCount["2"]??0,
                    $hourCount["1"]??0,
                    $hourCount["3"]??0,
                ];
            }
        }
        $downData = [
            ["城市：".$cityName],
            ["时间范围：".$timeRange],
            ["常偶发报警：".$frequencyTypeName],
            ["报警类型：".$alarmTypeName],
        ];
        if($params['alarm_type']>0){
            $downData[] = ["时刻",$alarmTypeName."次数"];
        }else{
            $downData[] = ["时刻","总报警次数","溢流次数","过饱和次数","失衡次数"];
        }
        $downData = array_merge($downData,$hourFormatList);
        
        //top信息格式
        $topTimeRange = $params["start_hour"]."-".$params["end_hour"];
        $downData[] = ["时刻：".$topTimeRange];
        $downData[] = ["报警路口TOP".$params["top_num"]."排名"];
        $downData[] = ["序号","路口名称","报警次数"];
        foreach ($junctionTop as $topIndex => $juncStat) {
            $downData[] = [++$topIndex,$juncStat["junction_name"],$juncStat["alarm_count"]];
        }

        $this->commonService->excelDownload([
            ["sheet_name"=>"路口报警发生时段统计","data"=>$downData,],
        ]);
    }

    /**
     * 路口报警分析 - 数据详情下载
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string Y 逻辑路口ID
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 开始日期与结束日期一致认为查询当天从0点到当前整点的数据
     * @param $params['alarm_type']           int    N 报警类型
     * @return array
     */
    public function junctionDataDownload($params)
    {
        if (empty($params)) {
            return (object)[];
        }
        $result = $this->getjunctionDownloadData($params);
        $excelData[] = ["sheet_name"=>"路口报警发生时段统计","data"=>$result,];
        $this->commonService->excelDownload($excelData);
        // $fp=fopen('php://memory','w+');
        // $convertedRow=array();
        // foreach($result as $row){
        //     $convertedRow=array();
        //     foreach($row as $val){
        //         $convertedRow[]=$val."\t";
        //     }
        //     fputcsv($fp,$convertedRow);
        // }
        // rewind($fp); 
        // $csvFile=stream_get_contents($fp);
        // fclose($fp);
        // ob_clean();
        // $fileName = date('YmdHis').'.csv';
        // header('Content-Type: text/csv; charset=utf-8');
        // header("Content-Transfer-Encoding: binary ");
        // header("Content-Type: application/force-download");
        // header('Content-Length: '.strlen($csvFile));
        // header('Content-Disposition: attachment; filename="'.$fileName.'"');
        // echo "\xEF\xBB\xBF";
        // echo ($csvFile);exit;
    }

    /**
     * 获取路口报警分析
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['alarm_type']        int    Y  路口报警问题
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd
     * @return array
     */
    private function getjunctionDownloadData($params)
    {
        $size = 10000;
        // 组织DSL所需json
        $json = '{"from":0,"size":'.$size.',"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';
        
        // where alarm_type
        if(!empty($params['alarm_type'])){
            $json .= ',{"match":{"type":{"query":' . (int)$params['alarm_type'] . ',"type":"phrase"}}}';
        }

        // where date >= start_time
        $json .= ',{"range":{"date":{"from":"' . trim($params['start_time']) . '","to":null,"include_lower":true,"include_upper":true}}}';

        // where date <= end_time
        $json .= ',{"range":{"date":{"from":null,"to":"' . trim($params['end_time']) . '","include_lower":true,"include_upper":true}}}';

        // 当按路口报警分析查询时
        if (!empty($params['logic_junction_id'])) {
            // where logic_junction_id
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. (int)$params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"excludes":[]},"fields":["date","type","frequency_type","type"],"sort":[{"created_at":{"order":"asc"}}]}';
        $result = $this->alarmanalysis_model->search($json,0,1);

        //准备相位信息
        $tempRes = [];
        $junctionAlarmType = $this->config->item('junction_alarm_type');
        $waymapPhase = $this->waymap_model->getFlowInfo32($params['logic_junction_id']);
        if(empty($waymapPhase)){
            return [];
        }
        if(!empty($waymapPhase)){
            $flowPhases = array_column($waymapPhase,"phase_name","logic_flow_id");
        }
        $detailList = [];
        $frequencyType = $this->config->item('frequency_type');
        $alarmType = $this->config->item('junction_alarm_type');
        foreach($result["hits"]["hits"] as $source){
            $source["_source"]["phase_name"] = $flowPhases[$source["_source"]["logic_flow_id"]]??"";
            $source["_source"]["start_hour"] = date("H:i",strtotime($source["_source"]["start_time"]));
            $durationTime = (strtotime($source["_source"]['last_time']) - strtotime($source["_source"]['start_time'])) / 60;
            if ($durationTime == 0) {
                $durationTime = 1;
            }
            $source["_source"]["duration_time"] = round($durationTime)."min";
            $source["_source"]["frequency_type_name"] = $frequencyType[$source["_source"]["frequency_type"]]??"";
            $source["_source"]["type_name"] = $alarmType[$source["_source"]["type"]]??"";
            $detailList[] = $source["_source"];
        }

        //format即可
        $waymapJuncInfo = $this->waymap_model->getJunctionInfo($params['logic_junction_id']);
        $juncName = $waymapJuncInfo[0]["name"]??"";
        $timeRange = date("Y.m.d",strtotime($params["start_time"]))."-".date("Y.m.d",strtotime($params["end_time"]));
        $frequencyTypeName = $frequencyType[$params['frequency_type']]??"全部";
        $alarmTypeName = $alarmType[$params['alarm_type']]??"全部";
        $downData = [
            ["路口名称：".$juncName],
            ["时间范围：".$timeRange],
            ["常偶发报警：".$frequencyTypeName],
            ["报警类型：".$alarmTypeName],
            ["日期","报警开始时间","持续时间","报警流向","报警类型","常发／偶发"],
        ];
        foreach ($detailList as $key => $detail) {
            $downData[] = [
                $detail["date"],
                $detail["start_hour"],
                $detail["duration_time"],
                $detail["phase_name"],
                $detail["type_name"],
                $detail["frequency_type_name"]
            ];
        }
        return $downData;
    } 

    /**
     * 获取当天报警分析(不牵扯时段查询)
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd
     * @return array
     */
    private function getDailyAlarmAnalysis($params)
    {
        $size = 0;
        if(!empty($params['logic_junction_id'])){
            $size = 10000;
        }
        // 组织DSL所需json
        $json = '{"from":0,"size":'.$size.',"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';

        // where alarm_type
        if(!empty($params['alarm_type'])){
            $json .= ',{"match":{"type":{"query":' . (int)$params['alarm_type'] . ',"type":"phrase"}}}';
        }

        // where date >= start_time
        $json .= ',{"range":{"date":{"from":"' . trim($params['start_time']) . '","to":null,"include_lower":true,"include_upper":true}}}';

        // where date <= end_time
        $json .= ',{"range":{"date":{"from":null,"to":"' . trim($params['end_time']) . '","include_lower":true,"include_upper":true}}}';

        if (!empty($params['logic_junction_id'])) { // 单路口报警分析查询
            // where logic_junction_id
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":' . (int)$params['frequency_type'] . ',"type":"phrase"}}}';
        }

        /*新的数据处理逻辑--开始*/
        if(!empty($params['logic_junction_id'])){
            $json .= ']}}}},"_source":{"excludes":[]},"fields":["hour","type","frequency_type"]}';
            // print_r($json);exit;
            $result = $this->alarmanalysis_model->search($json);
            if (empty($result["hits"]["hits"])) {
                return (object)[];
            }
            // print_r($result);exit;
            $transResult = [];
            foreach($result["hits"]["hits"] as $hitSource){
                $sHour = $hitSource["_source"]["hour"];
                $sType = $hitSource["_source"]["type"];
                $sFlowID = $hitSource["_source"]["logic_flow_id"];
                $transResult[$sHour][$sType][$sFlowID][] = $hitSource["_source"];
            }
            // print_r($transResult);exit;

            $tempRes = [];
            $junctionAlarmType = $this->config->item('junction_alarm_type');
            $waymapPhase = $this->waymap_model->getFlowInfo32($params['logic_junction_id']);
            if(empty($waymapPhase)){
                return [];
            }
            if(!empty($waymapPhase)){
                $flowPhases = array_column($waymapPhase,"phase_name","logic_flow_id");
            }
            // print_r($transResult);exit;
            foreach ($transResult as $hourKey => $hourValue) {
                foreach($hourValue as $typeKey=>$typeValue){
                    $typeDocCount = 0;
                    $phaseList = [];
                    $phaseAgg = [];
                    // print_r($typeValue);exit;
                    foreach($typeValue as $logicFlowID=>$hitSources){
                        $typeDocCount+=count($hitSources);
                        foreach($hitSources as $hitSource){
                            $phaseAgg[$logicFlowID][] = $hitSource;
                        }
                    }
                    foreach($phaseAgg as $logicFlowID=>$hisList){
                        $phaseList[] = [
                            "phase_name"=>$flowPhases[$logicFlowID],
                            "count"=>count($hisList),
                        ];
                    }
                    $tempRes[0][$hourKey.":00"]["list"][] = [
                        "name"=> $junctionAlarmType[$typeKey] ?? "",
                        "value"=> $typeDocCount,
                        "key"=> $typeKey,
                        "phase_list"=> $phaseList,
                    ];
                }
            }
            /*新的数据处理逻辑--结束*/
            // print_r($tempData);exit;
            // echo $json;exit;
        }else{
            //通过明细查询，转换为原来的格式？？？
            //老的数据处理逻辑
            $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":["hour","type","frequency_type"],"aggregations":{"hour":{"terms":{"field":"hour","size":200},"aggregations":{"type":{"terms":{"field":"type","size":0},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}}}';
            // echo $json;exit;

            $result = $this->alarmanalysis_model->search($json);
            // print_r($result);exit;
            if (!$result) {
                return (object)[];
            }
            
            $tempRes = [];
            if (empty($result['aggregations']['hour']['buckets'])) {
                return (object)[];
            }

            // 路口报警类型配置
            $junctionAlarmType = $this->config->item('junction_alarm_type');
            $tempRes = array_map(function($item) use ($junctionAlarmType) {
                if (!empty($item['type']['buckets'])) {
                    $tempData[$item['key'] . ':00']['list'] = array_map(function($typeData) use ($junctionAlarmType) {
                        //这里需要根据hour和type获取的相位数据统计
                        return [
                            'name'  => $junctionAlarmType[$typeData['key']] ?? "",
                            'value' => $typeData['num']['value'],
                            'key'   => $typeData['key'],
                        ];
                    }, $item['type']['buckets']);
                    return $tempData;
                } else {
                    return [];
                }
            }, $result['aggregations']['hour']['buckets']);
            //print_r($tempRes);exit;
        }
        //0-23整点小时保持连续 原因：数据表中可以会有某个整点没有报警，这样会导致前端画表时出现异常 
        // 当前整点
        //FIXME 只有查询当天的时候使用date('H')
        if(date("Y-m-d") == date("Y-m-d",strtotime($params['start_time'])) && date("Y-m-d") == date("Y-m-d",strtotime($params['end_time']))){
            $nowHour = date("H");
        }else{
            $nowHour = 24;
        }
        for ($i = 0; $i < $nowHour; $i++) {
            $continuousHour[$i . ':00'] = [];
        }

        // 平铺数组
        $temp = Collection::make($tempRes)->collapse()->get();
        foreach ($temp as $k=>$v) {
            // 各种报警条数总数
            $temp[$k]['count'] = array_sum(array_column($v['list'], 'value'));
        }

        // 合并数组
        if(is_array($continuousHour)){
            $resultData = array_merge($continuousHour, $temp);
        }else{
            $resultData = $temp;
        }
        //时间过滤
        if(isset($params['time_range'])){
            $timeRanges = explode("-",$params['time_range']);
            $st = $timeRanges[0];
            $et = $timeRanges[1];
            if($et=="24:00"){
                $et = "23:59";
            }
            foreach ($resultData as $rk => $rv){
                if(count($rk) == 4){
                    $rk = "0".$rk;
                }
                if(strtotime($rk) < strtotime($st)   ||  strtotime($rk) > strtotime($et)){
                   unset($resultData[$rk]);
                }
            }
        }
        return $resultData;
    }

    private function filterTime($timeRange,$dateType,$timeStamp){
        $timeRanges = explode("-",$timeRange);
        $st = $timeRanges[0];
        $et = $timeRanges[1];
        if($et =='24:00'){
            $et = "23:30";
        }
        $ddt = date('Y-m-d',$timeStamp);
        $dt = date('Y-m-d H:i:s',$timeStamp);

        if($dateType == 1 && in_array(date("w",strtotime($dt)),[0,6])){
            return true;
        }else if($dateType == 2 && in_array(date("w",strtotime($dt)),[1,2,3,4,5])){
            return true;
        }

        if(strtotime($dt) < strtotime($ddt." ".$st.":00")   ||  strtotime($dt) > strtotime($ddt." ".$et.":00")){
            return true;
        }

        return false;


    }

    /**
     * 按时间段获取报警分析,济南特殊需求
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd
     * @param $params['time_range']          string Y 查询时间段 00:00-24:00
     * @param $params['date_type']          string Y 查询日期类型 0全部,1工作日,2非工作日
     * @return array
     */
    private function getNewTimeAlarmAnalysis($params)
    {
        $timeRange = $params['time_range'];
        $dateType = isset($params['date_type'])? $params['date_type']:0;
        // 组织DSL所需json
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';

        // where date >= start_time
        $json .= ',{"range":{"date":{"from":"' . trim($params['start_time']) . '","to":null,"include_lower":true,"include_upper":true}}}';

        // where date <= end_time
        $json .= ',{"range":{"date":{"from":null,"to":"' . trim($params['end_time']) . '","include_lower":true,"include_upper":true}}}';

        // 当按路口报警分析查询时
        if (!empty($params['logic_junction_id'])) {
            // where logic_junction_id
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. (int)$params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","date"],"excludes":[]},"fields":"date","aggregations":{"date":{"terms":{"field":"date","size":200},"aggregations":{"type":{"terms":{"field":"type","size":0},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }
        if (empty($result['aggregations']['date']['buckets'])) {
            return (object)[];
        }
        // 路口报警类型配置
        $junctionAlarmType = $this->config->item('junction_alarm_type');
        $tempRes = array_map(function($item) use ($junctionAlarmType,$timeRange,$dateType) {
            if (!empty($item['type']['buckets'])) {
                // $item['key'] / 1000 es返回的是毫秒级的时间戳，固除以1000
                $timeStamp =  $item['key'] / 1000;
                $key = date('Y-m-d',$timeStamp);
                $tempData[$key]['list'] = array_map(function($typeData) use ($junctionAlarmType,$timeRange,$dateType,$timeStamp) {
                    //日期与时间段过滤
                    if($this->filterTime($timeRange,$dateType,$timeStamp)){
                        return [
                            'name'  => $junctionAlarmType[$typeData['key']],
                            'value' => 0,
                            'key'   => $typeData['key'],
                        ];
                    }else{
                        return [
                            'name'  => $junctionAlarmType[$typeData['key']],
                            'value' => $typeData['num']['value'],
                            'key'   => $typeData['key'],
                        ];
                    }

                }, $item['type']['buckets']);
                return $tempData;
            } else {
                return [];
            }
        }, $result['aggregations']['date']['buckets']);

        /* 使日期连续 因为表中可能某个日期是没有的，就会出现断裂*/
        $startTime = strtotime($params['start_time']);
        $endTime = strtotime($params['end_time']);
        for ($i = $startTime; $i <= $endTime; $i += 24 * 3600) {
            $continuousTime[date('Y-m-d', $i)] = [];
        }


        // 平铺数组
        $temp = Collection::make($tempRes)->collapse()->get();
        foreach ($temp as $k=>$v) {
            // 各种报警条数总数
            $temp[$k]['count'] = array_sum(array_column($v['list'], 'value'));
        }

        // 合并数组
        $resultData = array_merge($continuousTime, $temp);

        //日期过滤
        foreach ($resultData as $rk=>$rv){
            if($dateType == 1 && in_array(date("w",strtotime($rk)),[0,6])){
               unset($resultData[$rk]);
            }else if($dateType == 2 && in_array(date("w",strtotime($rk)),[1,2,3,4,5])){
                unset($resultData[$rk]);
            }
        }
        return $resultData;


    }

    /**
     * 按时间段获取报警分析
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd
     * @return array
     */
    private function getTimeAlarmAnalysis($params)
    {
        $size = 0;
        if(!empty($params['logic_junction_id'])){
            $size = 10000;
        }
        // 组织DSL所需json
        $json = '{"from":0,"size":'.$size.',"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';
        
        // where alarm_type
        if(!empty($params['alarm_type'])){
            $json .= ',{"match":{"type":{"query":' . (int)$params['alarm_type'] . ',"type":"phrase"}}}';
        }

        // where date >= start_time
        $json .= ',{"range":{"date":{"from":"' . trim($params['start_time']) . '","to":null,"include_lower":true,"include_upper":true}}}';

        // where date <= end_time
        $json .= ',{"range":{"date":{"from":null,"to":"' . trim($params['end_time']) . '","include_lower":true,"include_upper":true}}}';

        // 当按路口报警分析查询时
        if (!empty($params['logic_junction_id'])) {
            // where logic_junction_id
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. (int)$params['frequency_type'] .',"type":"phrase"}}}';
        }

        /*新的数据处理逻辑--开始*/
        //新逻辑思路是基于明细数据聚合相位级别数据
        if(!empty($params['logic_junction_id'])){
            $json .= ']}}}},"_source":{"excludes":[]},"fields":["date","type","frequency_type","type"]}';
            // echo $json;exit;
            $result = $this->alarmanalysis_model->search($json);
            if (empty($result["hits"]["hits"])) {
                return (object)[];
            }
            // print_r($result);exit;
            $transResult = [];
            foreach($result["hits"]["hits"] as $hitSource){
                $sDate = $hitSource["_source"]["date"];
                $sType = $hitSource["_source"]["type"];
                $sFlowID = $hitSource["_source"]["logic_flow_id"];
                $transResult[$sDate][$sType][$sFlowID][] = $hitSource["_source"];
            }
            // print_r($transResult);exit;
            $tempRes = [];
            $junctionAlarmType = $this->config->item('junction_alarm_type');
            $waymapPhase = $this->waymap_model->getFlowInfo32($params['logic_junction_id']);
            if(empty($waymapPhase)){
                return [];
            }
            if(!empty($waymapPhase)){
                $flowPhases = array_column($waymapPhase,"phase_name","logic_flow_id");
            }
            // print_r($transResult);exit;
            foreach ($transResult as $dateKey => $dateValue) {
                foreach($dateValue as $typeKey=>$typeValue){
                    $typeDocCount = 0;
                    $phaseList = [];
                    $phaseAgg = [];
                    // print_r($typeValue);exit;
                    foreach($typeValue as $logicFlowID=>$hitSources){
                        $typeDocCount+=count($hitSources);
                        foreach($hitSources as $hitSource){
                            $phaseAgg[$logicFlowID][] = $hitSource;
                        }
                    }
                    foreach($phaseAgg as $logicFlowID=>$hisList){
                        $phaseList[] = [
                            "phase_name"=>$flowPhases[$logicFlowID],
                            "count"=>count($hisList),
                        ];
                    }
                    $tempRes[0][$dateKey]["list"][] = [
                        "name"=> $junctionAlarmType[$typeKey] ?? "",
                        "value"=> $typeDocCount,
                        "key"=> $typeKey,
                        "phase_list"=> $phaseList,
                    ];
                }
            }
            /*新的数据处理逻辑--结束*/
            // print_r($tempData);exit;
            // echo $json;exit;
        }else{
            $json .= ']}}}},"_source":{"includes":["COUNT","date"],"excludes":[]},"fields":"date","aggregations":{"date":{"terms":{"field":"date","size":200},"aggregations":{"type":{"terms":{"field":"type","size":0},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}}}';
            // echo $json;exit;
            $result = $this->alarmanalysis_model->search($json);
            if (!$result) {
                return (object)[];
            }

            /* 处理数据 */
            $tempRes = [];

            if (empty($result['aggregations']['date']['buckets'])) {
                return (object)[];
            }
            // 路口报警类型配置
            $junctionAlarmType = $this->config->item('junction_alarm_type');

            $tempRes = array_map(function($item) use ($junctionAlarmType) {
                if (!empty($item['type']['buckets'])) {
                    // $item['key'] / 1000 es返回的是毫秒级的时间戳，固除以1000
                    $key = date('Y-m-d', $item['key'] / 1000);
                    $tempData[$key]['list'] = array_map(function($typeData) use ($junctionAlarmType) {
                        return [
                            'name'  => $junctionAlarmType[$typeData['key']],
                            'value' => $typeData['num']['value'],
                            'key'   => $typeData['key'],
                        ];
                    }, $item['type']['buckets']);
                    return $tempData;
                } else {
                    return [];
                }
            }, $result['aggregations']['date']['buckets']);

        }


        /* 使日期连续 因为表中可能某个日期是没有的，就会出现断裂*/
        $startTime = strtotime($params['start_time']);
        $endTime = strtotime($params['end_time']);
        for ($i = $startTime; $i <= $endTime; $i += 24 * 3600) {
            $continuousTime[date('Y-m-d', $i)] = [];
        }


        // 平铺数组
        $temp = Collection::make($tempRes)->collapse()->get();
        foreach ($temp as $k=>$v) {
            // 各种报警条数总数
            $temp[$k]['count'] = array_sum(array_column($v['list'], 'value'));
        }

        // 合并数组
        $resultData = array_merge($continuousTime, $temp);

        return $resultData;
    }

    /**
     * 城市报警分析----报警路口TOP50排名
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 
     * @param $params['start_hour']        string Y 开始时间，全天开始传 00:00:00
     * @param $params['end_hour']          string Y 结束时间，全天结束时传 24:00:00
     * @param $params['alarm_type']        int    N 报警问题 0=全部 1=过饱和 2=溢流 3=失衡，默认全部
     * @param $params['top_num']        int    N 报警数top，不传则默认50
     * @return array
     */
    public function alarmJunctionTop($params)
    {

        // $sql='select count(id) as cnt from online_its_alarm_movement_month_202002 where start_time>="2020-02-01 00:00:00" and start_time<="2020-02-08 00:00:00" and type=1 and frequency_type=1 and city_id=1 group by logic_junction_id order by cnt desc limit 50';
        $startTime = $params['start_time']." ".$params['start_hour'];
        $endTime = $params['end_time']." ".$params['end_hour'];
        $alarmType = "";
        if(!empty($params["alarm_type"])){
            $alarmType = " and type=".$params["alarm_type"];
        }
        $frequencyType = "";
        if(!empty($params["frequency_type"])){
            $frequencyType = " and frequency_type=".$params["frequency_type"];
        }

        $sql='select count(id) as cnt from online_its_alarm_movement_month_* where city_id=%s and start_time>="%s" and start_time<="%s" %s %s group by logic_junction_id order by cnt desc limit %s';
        $sql = sprintf($sql,$params["city_id"],$startTime,$endTime,$alarmType,$frequencyType,$params["top_num"]);
        $result = $this->alarmanalysis_model->search($sql,1);
        if (empty($result["aggregations"]["logic_junction_id"]["buckets"])) {
            return (object)[];
        }

        //format
        $resultData = [];
        foreach($result["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
            $resultData[] = [
                "junction_id"=>$bucket["key"],
                "alarm_count"=>$bucket["cnt"]["value"],
            ];
        }
        $wayMapJunctions=$this->waymap_model->getJunctionInfo(implode(",",array_column($resultData,"junction_id")));
        $junctionID2Name = array_column($wayMapJunctions,"name","logic_junction_id");
        foreach ($resultData as $key => $value) {
            $resultData[$key]["junction_name"] = $junctionID2Name[$value["junction_id"]]??"";
        }
        // print_r($resultData);
        return $resultData;
    }

    /**
     * 城市/路口报警时段分布接口
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 开始日期与结束日期一致认为查询当天从0点到当前整点的数据
     * @param $params['alarm_type']           int    N 报警问题 0=全部 1=过饱和 2=溢流 3=失衡
     * @return array
     */
    public function alarmTimeDistribution($params)
    {
        if (empty($params)) {
            return (object)[];
        }

        // 组织DSL所需json
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';

        // where type
        if(!empty($params['alarm_type'])){
            $json .= ',{"match":{"type":{"query":' . (int)$params['alarm_type'] . ',"type":"phrase"}}}';
        }
        if ($params['start_time'] == $params['end_time']) { // 当天
            // where date
            $json .= ',{"match":{"date":{"query":"' . trim($params['start_time']) . '","type":"phrase"}}}';
        } else { // 多天
            // where date
            $json .= ',{"range":{"date":{"from":"' . trim($params['start_time']) . '","to":null,"include_lower":true,"include_upper":true}}}';
            $json .= ',{"range":{"date":{"from":null,"to":"' . trim($params['end_time']) . '","include_lower":true,"include_upper":true}}}';
        }

        // 按路口查询
        if (!empty($params['logic_junction_id'])) {
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. (int)$params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":"hour","sort":[{"hour":{"order":"asc"}}],"aggregations":{"hour":{"terms":{"field":"hour","size":200,"order":{"_term":"asc"}},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];

        if (empty($result['aggregations']['hour']['buckets'])) {
            return (object)[];
        }
        // 路口报警类型配置
        $junctionAlarmType = $this->config->item('junction_alarm_type');

        $tempRes = array_map(function($item) use ($junctionAlarmType) {
            return [
                'hour'  => $item['key'],
                'value' => $item['num']['value'],
            ];
        }, $result['aggregations']['hour']['buckets']);

        // 当前整点
        //FIXME 只有查询当天的时候使用date('H')
        if(date("Y-m-d") == date("Y-m-d",strtotime($params['start_time'])) && date("Y-m-d") == date("Y-m-d",strtotime($params['end_time']))){
            $nowHour = date("H");
        }else{
            $nowHour = 24;
        }
        // 将tempRes数据重新置为 ['hour'=>value] 数组
        $tempResData = array_column($tempRes, 'value', 'hour');
        /* 0-23整点小时保持连续 原因：数据表中可以会有某个整点没有报警，这样会导致前端画表时出现异常 */
        for ($i = 0; $i < $nowHour; $i++) {
            if (!array_key_exists($i, $tempResData)) {
                $tempResData[$i] = 0;
                $tempRes[] = [
                    'hour'  => $i,
                    'value' => 0,
                ];
            }
        }

        /* 找出连续三小时报警最的大的TOP2 */
        $countData = [];
        for ($i = 0; $i < $nowHour; $i++) {
            $value0 = $tempResData[$i] ?? 0;
            $value1 = $tempResData[($i+1)] ?? 0;
            $value2 = $tempResData[($i+2)] ?? 0;
            $countData[$i . '-' . ($i+1) . '-' . ($i+2)] = ($value0 + $value1 + $value2);
        }

        // 排序
        arsort($countData);
        // 去重
        array_unique($countData);
        // 取top2
        $topData = array_slice($countData, 0, 2);
        /* 判断两个连续3小时的开始时间差是否满足4小时及以上 */
        list($top1key, $top2key) = array_keys($topData);
        // top1、top2开始时间
        list($top1start) = explode('-', $top1key);
        list($top2start) = explode('-', $top2key);
        if (abs($top1start - $top2start) < 4) {
            // 小于4小时只取最大的一个
            unset($topData[$top2key]);
        }

        array_multisort($tempRes, $tempResData);
        $resultData['dataList'] = $tempRes;
        // 组织top信息
        foreach($topData as $hour=>$value) {
            $resultData['topInfo'][] = explode('-', $hour);
        }

        return $resultData;
    }

    public function junctionAlarmDealList($params){
        //设置参数
        $cityID = $params["city_id"];
        $junctionID = $params["junction_id"];
        if(empty($dates)){
            for($i=0;$i<15;$i++){
                $dates[] = date("Y-m-d",strtotime("-$i day"));
            }
        }else{
            $dates = $params["dates"];
        }
        $alarmList  = $this->getJunctionAlarmListByDates($cityID, $dates, $junctionID);
        $timingPostList = [];

        //排序再格式化
        $mergeList = array_merge($alarmList,$timingPostList);
        $sorterCreateTime = [];
        foreach ($mergeList as $key=>$value) {
            $sorterCreateTime[] = $value["create_time"];
        }
        array_multisort($sorterCreateTime,SORT_DESC,$mergeList);
        return $mergeList;
    }

    private function getJunctionAlarmListByDates($cityID, $dates, $junctionID){
        $phaseNames = $this->getFlowFinalPhaseNames($junctionID);
        $alarmEsList = $this->alarmanalysis_model->getJunctionAlarmByDates($cityID, $dates, $junctionID);
        $alarmCate = $this->config->item('flow_alarm_category');
        $alarmList = [];
        foreach ($alarmEsList as $val) {
            $durationTime = round((strtotime($val['last_time']) - strtotime($val['start_time'])) / 60, 2);
            if ($durationTime < 1) {
                $durationTime = 1;
            }

            if(!isset($phaseNames[$val["logic_flow_id"]])){
                continue;
            }
            $phaseName = $phaseNames[$val["logic_flow_id"]];
            $cateName = $alarmCate[$val["type"]]["name"];
            $alarmItem["create_time"] = $val["start_time"];
            $alarmItem["type"] = $val["type"];
            $alarmItem["comments"] = "【".$phaseName."】- 【".$cateName."】，持续".$durationTime."分钟";
            $alarmList[] = $alarmItem;
        }

        //这里读取配时下发数据
        $startTime = end($dates)." 00:00:00";
        $endTime = date("Y-m-d H:i:s");
        $timingHis = $this->timing_model->getJuncTimingHistory($junctionID,$startTime,$endTime);
        // print_r($timingHis);exit;
        foreach ($timingHis as $val) {
            $alarmItem["create_time"] = $val["time_point"];
            $alarmItem["type"] = 10;
            $alarmItem["comments"] = "自适应方案 - 【下发】，执行成功";
            $alarmList[] = $alarmItem;
        }

        //这里读取配时变更数据
        $relsHis = $this->timing_model->getJuncReleaseHistory($junctionID,$startTime,$endTime);
        foreach ($relsHis as $val) {
            if(empty($val["comment"])){
                $alarmItem["comments"] = "配时发布";
            }else{
                $alarmItem["comments"] = "配时发布，".$val["comment"];
            }
            $alarmItem["create_time"] = $val["time_point"];
            $alarmItem["type"] = 20;
            $alarmList[] = $alarmItem;
        }

        $createSlice = [];
        foreach($alarmList as $key=>$val){
            $createSlice[$key] = strtotime($val["create_time"]);
        }

        //排序数据
        array_multisort($createSlice, SORT_NUMERIC, SORT_DESC, $alarmList);
        return $alarmList;
    }

    private function getFlowFinalPhaseNames($junctionId){
        $flowInfo = $this->waymap_model->getFlowsInfo($junctionId);
        $flowWaymapNames =  $flowInfo[$junctionId] ?? [];
        $flowTimingNames = $this->common_model->getTimingMovementNames($junctionId);
        foreach ($flowWaymapNames as $key => $value) {
            if(isset($flowTimingNames[$key])){
                $flowWaymapNames[$key] = $flowTimingNames[$key];
            }
        }
        return $flowWaymapNames;
    }

    /**
     * 7日报警均值
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['alarm_type']    int    N 报警问题 0=全部 1=过饱和 2=溢流 3=失衡
     * @return json
     */
    public function sevenDayAlarmMeanValue($params)
    {
        if (empty($params)) {
            return (object)[];
        }

        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';

        // where type
        if(!empty($params['alarm_type'])){
            $json .= ',{"match":{"type":{"query":' . (int)$params['alarm_type'] . ',"type":"phrase"}}}';
        }

        // 前七日开始时间
        $startTime = date('Y-m-d', strtotime('-7 days'));
        // 前七日结束时间
        $endTime = date('Y-m-d', strtotime('-1 days'));

        // where date
        $json .= ',{"range":{"date":{"from":"' . $startTime . '","to":null,"include_lower":true,"include_upper":true}}}';
        $json .= ',{"range":{"date":{"from":null,"to":"' . $endTime . '","include_lower":true,"include_upper":true}}}';

        // 按路口查询
        if (!empty($params['logic_junction_id'])) {
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. (int)$params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":"hour","sort":[{"hour":{"order":"asc"}}],"aggregations":{"hour":{"terms":{"field":"hour","size":200,"order":{"_term":"asc"}},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}';
        // echo $json;exit;
        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];

        if (empty($result['aggregations']['hour']['buckets'])) {
            return (object)[];
        }

        $tempRes = array_map(function($item) {
            return [
                'hour'  => $item['key'] . ':00', // 10:00
                'value' => round($item['num']['value'] / 7 , 2),
            ];
        }, $result['aggregations']['hour']['buckets']);

        /* 0-23整点小时保持连续 原因：数据表中可以会有某个整点没有报警，这样会导致前端画表时出现异常 */
        for ($i = 0; $i < 24; $i++) {
            $continuousHour[$i . ':00'] = 0;
        }
        foreach(array_merge($continuousHour, array_column($tempRes, 'value', 'hour')) as $k=>$v) {
            $resultData['dataList'][] = [
                'hour'  => $k,
                'value' => $v,
            ];
        }

        return $resultData;
    }
}
