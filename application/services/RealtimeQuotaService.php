<?php

namespace Services;

/**
 * Class RealtimeQuotaService
 *
 * @property \Realtime_model $realtime_model
 * @property \waymap_model $waymap_model
 * @property \Common_model $common_model
 * @package Services
 */
class RealtimeQuotaService extends BaseService
{
    private $helperService;
    private $overviewService;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('realtime_model');
        $this->load->model('timing_model');
        $this->load->model('waymap_model');
        $this->load->helper('http_helper');
        $this->load->model('common_model');
        $this->load->model('alarmanalysis_model');
        $this->helperService = new HelperService();
        $this->overviewService = new OverviewService();
        $this->load->config("nconf");
    }

    public function realtimeSingleQuotaCurve($params){
        $cityID = $params["city_id"];
        $logicJunctionID = $params["junction_id"];
        $startTime = $params["start_time"];
        $endTime = $params["end_time"];
        $quotaKey = $params["quota_key"];
        if(empty($params["dates"])){
            $params["dates"] = [date("Y-m-d")];
        }
        $dates = $params["dates"];
        $lastHour = $this->helperService->getLastestHour($cityID);
        $resultList = [];
        foreach ($dates as $date) {
            $data = [];
            $data["city_id"] = $cityID;
            $data["date"] = $date;
            $data["junction_id"] = $logicJunctionID;
            $data["quota_key"] = $quotaKey;
            $flowList = $this->realtime_model->getJunctionQuotaCurve($data,true);
            if($date==date("Y-m-d")){
                if(strtotime(date("Y-m-d")." ".$endTime) > strtotime(date("Y-m-d")." ".$lastHour)){
                   $endTime = $lastHour;
                }
            }
            $quotaList = $this->getQuotaValue($quotaKey, $flowList, $startTime, $endTime);
            $resultList[$date] = $quotaList;
            if($date==date("Y-m-d")){
                $resultList["today"] = $quotaList;
            }
        }
        return $resultList;
    }

    private function getQuotaValue($quotaKey,$flowList,$startTime,$endTime){
        $quotaList = [];
        // echo date("Y-m-d")." ".$lastHour;exit;
        foreach ($flowList['result']['quotaResults'] as $value) {
            //数据过滤
            $quotaDate = date("Y-m-d",strtotime($value['quotaMap']["dayTime"]));
            if(strtotime($value['quotaMap']["dayTime"])<strtotime($quotaDate." ".$startTime)){
                // print_r($value['quotaMap']["dayTime"]);
                continue;
            }
            if(strtotime($value['quotaMap']["dayTime"])>strtotime($quotaDate." ".$endTime)){
                // print_r($value['quotaMap']["dayTime"]);
                continue;
            }

            //格式化
            $quotaValue = $value['quotaMap']["weight_avg"];
            if($quotaKey=="avg_speed_up"){
                $quotaValue = $quotaValue*3.6;
            }
            if($quotaKey=="volume_up"){
                $quotaValue = $quotaValue*2;
            }
            $quotaList[] = [
                "hour"=>date("H:i:s",strtotime($value['quotaMap']["dayTime"])),
                "value"=>round($quotaValue,2),
            ];
        }
        $mapList = [];
        foreach ($quotaList as $value) {
            $mapList[$value["hour"]] = $value;
        }
        // print_r($keys);exit
        $pretime = strtotime($quotaList[0]["hour"] ?? '00:00:00');
        $preValue = intval($quotaList[0]["value"]);
        for($i=1;$i<(count($quotaList)-1);$i++){
            $nowTime = strtotime($quotaList[$i]["hour"] ?? '00:00:00');
            $nowValue = intval($quotaList[$i]["value"]);
            if (($nowTime - $pretime) < 2 * 60) {
                unset($mapList[$quotaList[$i]["hour"]]);
                continue;
            }
            $pretime = $nowTime;
        }
        ksort($mapList);
        return array_values($mapList);
    }

    public function junctionRealtimeFlowQuotaList($params){
        $junctionId = $params["junction_id"];
        $cityId = $params["city_id"];
        $with_alarm = $params['with_alarm'];
        $hour = $this->helperService->getLastestHour($cityId);
        if(!empty($params["time"])){
            $hour = $params["time"];
        }
        if(empty($params["dates"])){
            $params["dates"] = [date("Y-m-d")];
        }
        $dates = $params["dates"];
        foreach ($dates as $date) {
            $movementList = [];
            $inputJunctionIds[] = $params["junction_id"];

            //获取相位关联通道ID
            $flowChannel = $this->timing_model->queryFlowChannel(["logic_junction_id"=>$params["junction_id"]]);
            // print_r($flowChannel);exit;
            $flowList = $this->realtime_model->getRealTimeJunctionsQuota($cityId, $date, $hour, $inputJunctionIds, 0);
            // print_r($flowList);exit;
            $result = [];
            $result["flow_quota_all"] = [
                "route_length"=>["name"=>"路段长度","unit"=>"米",],
                "stop_delay_up"=>["name"=>"停车延误","unit"=>"秒",],
                "avg_stop_num_up"=>["name"=>"停车次数","unit"=>"次",],
                "spillover_rate_up"=>["name"=>"溢流比率","unit"=>"",],
                "one_stop_ratio_up"=>["name"=>"停车比率","unit"=>"",],
                "avg_speed_up"=>["name"=>"速度","unit"=>"千米/小时",],
                "volume_up"=>["name"=>"流量","unit"=>"辆/小时",],
                "travel_time_up"=>["name"=>"通过时间","unit"=>"秒",],
                "traffic_jam_index_up"=>["name"=>"拥堵指数","unit"=>"",],
                "saturation_up"=>["name"=>"饱和度","unit"=>"",],
            ];
            $quotaKeys = ["movement_id","stop_delay_up","avg_stop_num_up","spillover_rate_up","one_stop_ratio_up","avg_speed_up","volume_up","travel_time_up","traffic_jam_index_up","saturation_up",];
            $newFlowList = [];
            $flowLengths = $this->getFlowLength($cityId, $junctionId);
            $phaseNames = $this->getFlowFinalPhaseNames($junctionId);
            foreach ($flowList as $key => $value) {
                foreach ($value as $vk => $vv) {
                    $uncamelKey = uncamelize($vk);
                    if (in_array($uncamelKey, $quotaKeys)) {
                        if($uncamelKey=="avg_speed_up"){
                            $vv = $vv*3.6;
                        }
                        if($uncamelKey=="volume_up"){
                            $vv = 2*$vv;
                        }
                        if($uncamelKey!="movement_id"){
                           $vv = round($vv,2);
                        }
                        $newFlowList[$key][$uncamelKey] = (string) $vv;
                    }
                }
                if(isset($newFlowList[$key])){
                    $newFlowList[$key]['comment'] = $phaseNames[$newFlowList[$key]["movement_id"]] ?? "";
                    $newFlowList[$key]['route_length'] = $flowLengths[$newFlowList[$key]["movement_id"]] ?? "";
                }
                $newFlowList[$key]['sg_num'] = $flowChannel[$newFlowList[$key]["movement_id"]]??"";
            }
            //newFlowList排序
            $newFlowList = $this->sortFlowList($cityId,$junctionId,$newFlowList);
            $movementList[$date] = $newFlowList;
            if($date==date("Y-m-d")){
                if ($with_alarm == 1) {
                    $alarm_list = $this->overviewService->realTimeAlarmList(['city_id' => $cityId, 'date' => $date], $this->userPerm);
                    $alarm_list_map = [];
                    foreach ($alarm_list['dataList'] as $alarm) {
                        $alarm_list_map[$alarm['logic_flow_id']] = $alarm;
                    }
                    foreach ($newFlowList as $key => $value) {
                        if (isset($alarm_list_map[$value['movement_id']])) {
                            $newFlowList[$key]['is_alarm'] = 1;
                            $newFlowList[$key]['type'] = $alarm_list_map[$value['movement_id']]['type'];
                            $newFlowList[$key]['junction_type'] = $alarm_list_map[$value['movement_id']]['junction_type'];
                            $newFlowList[$key]['alarm_comment'] = $alarm_list_map[$value['movement_id']]['alarm_comment'];
                        } else {
                            $newFlowList[$key]['is_alarm'] = 0;
                            $newFlowList[$key]['type'] = 0;
                            $newFlowList[$key]['junction_type'] = 0;
                            $newFlowList[$key]['alarm_comment'] = '';
                        }
                    }
                }
                $movementList["today"] = $newFlowList;
            }
        }
        $result["hour"] = $hour;
        $result["movements"] = $movementList;
        return $result;
    }

    private function adjustPhase($flow)
    {
        $phaseId = phase_map($flow['in_degree'], $flow['out_degree']);
        $phaseName = phase_name($phaseId);
        $flow['phase_id'] = $phaseId;
        $flow['phase_name'] = $phaseName;
        $flow['sort_key'] = phase_sort_key($flow['in_degree'], $flow['out_degree']);
        return $flow;
    }

    private function getFlowLength($cityID, $junctionId){
        $flowsMovement = $this->waymap_model->getFlowMovement($cityID, $junctionId, "all", 1);
        if (empty($flowsMovement)) {
            return [];
        }
        $flowLengths = [];
        foreach ($flowsMovement as $key => $value) {
            $flowLengths[$value["logic_flow_id"]] = $value["in_link_length"];
        }
        return $flowLengths;
    }

    private function sortFlowList($cityID,$junctionId,$newFlowList){
        //相位信息
        $flowsMovement = $this->waymap_model->getFlowMovement($cityID, $junctionId, "all", 1);
        $flows = array_map(function ($v) {
            $v = $this->adjustPhase($v);
            return $v;
        }, $flowsMovement);
        $movements = [];
        if (empty($flows)) {
            return $newFlowList;
        }
        $sortKeys = [];
        foreach ($flows as $idx => $flow) {
            $sortKeys[$flow['logic_flow_id']] = $flow['sort_key'];
        }
        usort($newFlowList,function($a,$b)use($sortKeys){
            if(isset($sortKeys[$a["movement_id"]]) && isset($sortKeys[$b["movement_id"]])){
                if($sortKeys[$a["movement_id"]]>$sortKeys[$b["movement_id"]]){
                    return 1;
                }else{
                    return -1;
                }
            }
            return 0;
        });
        return $newFlowList;
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
     * 获取路口明细数据
     * @param $cityId
     * @param array $junctionIds
     * @param array $quotaKeys
     * @param array $userPerm
     * @return array
     */
    public function getFlowQuota($cityId, $inputJunctionIds = [], $quotaKeys = [],$userPerm=[])
    {
        //权限验证
        if(!empty($userPerm)){
            $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            if(in_array($cityId,$cityIds)){
                $junctionIds = [];
            }
            if(!in_array($cityId,$cityIds) && empty($junctionIds)){ //无任何数据权限
                return [];
            }
            foreach ($inputJunctionIds as $jid){
                if(!empty($junctionIds) && !in_array($jid,$junctionIds)){
                    throw new \Exception("you don't have junction:$jid right.");
                }
            }
        }

        $date = date('Y-m-d');
        $hour = $this->helperService->getLastestHour($cityId);
        $flowList = $this->realtime_model->getRealTimeJunctionsQuota($cityId, $date, $hour, $inputJunctionIds);
        $flowInfo = $this->waymap_model->getFlowsInfo(implode(",", $inputJunctionIds));


        $newFlowList = [];
        foreach ($flowList as $key => $value) {
            $phaseName = $flowInfo[$value["junctionId"]][$value["movementId"]];
            foreach ($value as $vk => $vv) {
                $uncamelKey = uncamelize($vk);
                if (in_array($uncamelKey, $quotaKeys)) {
                    $newFlowList[$key][$uncamelKey] = $vv;
                }
            }

            $value["trailNum"] = $value["trailNum"]??0;
            switch ($value["trailNum"]){
                case ($value["trailNum"]>=30):
                    $newFlowList[$key]["confidence"] = "高";
                    break;
                case ($value["trailNum"]<=10):
                    $newFlowList[$key]["confidence"] = "低";
                    break;
                default:
                    $newFlowList[$key]["confidence"] = "中";
            }
            $newFlowList[$key]["phase_name"] = $phaseName;
            $newFlowList[$key]["logic_flow_id"] = $value["movementId"];
        }

        $junctionMovements = [];
        foreach ($inputJunctionIds as $junctionId){
            $junctionMovements[$junctionId] = $this->common_model->getTimingMovementNames($junctionId);
        }

        foreach ($newFlowList as $key => $item) {
            if (!empty($junctionMovements[$junctionId][$item["logic_flow_id"]])) {
                $newFlowList[$key]["movement_name"] = $junctionMovements[$junctionId][$item["logic_flow_id"]];
            }else{
                $newFlowList[$key]["movement_name"] = $newFlowList[$key]["phase_name"];
            }
        }
        return ["list"=>$newFlowList,"batch_time"=>$date." ".$hour];
    }

    public function getRealTimeAlarmsInfo($cityId, $date){
        $res = $this->alarmanalysis_model->getRealTimeAlarmsInfo($cityId, $date);
        return $res;
        // $res = '[{"logic_junction_id":"2017030116_3881747","logic_flow_id":"2017030116_i_48853100_2017030116_o_48853031","start_time":"2019-10-10 00:05:00","last_time":"2019-10-10 00:05:00","type":1,"junction_type":3},{"logic_junction_id":"2017030116_4408929","logic_flow_id":"2017030116_i_48853100_2017030116_o_48853031","start_time":"2019-10-10 00:05:00","last_time":"2019-10-10 00:05:00","type":2,"junction_type":3},{"logic_junction_id":"2017030116_4408929","logic_flow_id":"2017030116_i_48853100_2017030116_o_48853031","start_time":"2019-10-10 00:05:00","last_time":"2019-10-10 00:05:00","type":3,"junction_type":3}]';
        // $res = json_decode($res,true);
        // return $res;
    }
}