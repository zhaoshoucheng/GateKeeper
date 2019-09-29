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

    public function __construct()
    {
        parent::__construct();
        $this->load->model('realtime_model');
        $this->load->model('waymap_model');
        $this->load->helper('http_helper');
        $this->load->model('common_model');
        $this->helperService = new HelperService();
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
        $resultList = [];
        foreach ($dates as $date) {
            $data = [];
            $data["city_id"] = $cityID;
            $data["date"] = $date;
            $data["junction_id"] = $logicJunctionID;
            $data["quota_key"] = $quotaKey;
            $flowList = $this->realtime_model->getJunctionQuotaCurve($data,true);
            $quotaList = $this->getQuotaValue($quotaKey, $flowList);
            $resultList[$date] = $quotaList;
            if($date==date("Y-m-d")){
                $resultList["today"] = $quotaList;
            }
        }
        return $resultList;
    }

    private function getQuotaValue($quotaKey,$flowList){
        $quotaList = [];
        foreach ($flowList['result']['quotaResults'] as $value) {
            $quotaValue = $value['quotaMap']["weight_avg"];
            if($quotaKey=="avg_speed_up"){
                $quotaValue = $quotaValue*3.6;
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
        $pretime = strtotime($quotaList[0]["hour"] ?? '00:00:00');
        $preValue = intval($quotaList[0]["value"]);
        for($i=1;$i<count($quotaList);$i++){
            $nowTime = strtotime($quotaList[$i]["hour"] ?? '00:00:00');
            $nowValue = intval($quotaList[$i]["value"]);
            if (($nowTime - $pretime) < 5 * 60) {
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
            $flowList = $this->realtime_model->getRealTimeJunctionsQuota($cityId, $date, $hour, $inputJunctionIds);
            $result = [];
            $result["flow_quota_all"] = [
                "route_length"=>["name"=>"路段长度","unit"=>"米",],
                "stop_delay_up"=>["name"=>"停车延误","unit"=>"秒",],
                "avg_stop_num_up"=>["name"=>"停车次数","unit"=>"",],
                "spillover_rate_down"=>["name"=>"溢流比率","unit"=>"",],
                "one_stop_ratio_up"=>["name"=>"停车比率","unit"=>"",],
                "avg_speed_up"=>["name"=>"速度","unit"=>"km/h",],
                "volume_up"=>["name"=>"流量","unit"=>"pcu/分",],
                "travel_time_up"=>["name"=>"通过时间","unit"=>"秒",],
                "traffic_jam_index_up"=>["name"=>"拥堵指数 TTI","unit"=>"",],
                "saturation_up"=>["name"=>"饱和度","unit"=>"",],
            ];
            $quotaKeys = ["movement_id","stop_delay_up","avg_stop_num_up","spillover_rate_down","one_stop_ratio_up","avg_speed_up","volume_up","travel_time_up","traffic_jam_index_up","saturation_up",];
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
            }
            $movementList[$date] = $newFlowList;
            if($date==date("Y-m-d")){
                $movementList["today"] = $newFlowList;
            }
        }
        $result["hour"] = $hour;
        $result["movements"] = $movementList;
        return $result;
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
}