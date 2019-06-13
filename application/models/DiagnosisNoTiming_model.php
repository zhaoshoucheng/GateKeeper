<?php

/**
 * Class DiagnosisNoTiming_model
 * @property Waymap_model $waymap_model
 * @property \Traj_model $traj_model
 */
class DiagnosisNoTiming_model extends CI_Model
{
    public function __construct()
    {
        $this->load->config('disgnosisnotiming_conf');
        parent::__construct();
        $this->load->model('waymap_model');
        $this->load->helper('phase');
        $this->load->model('traj_model');
        $this->load->config("nconf");
    }

    /**
     * 获取单个路口时段粒度指标问题统计
     */
    public function getJunctionQuotaTrend($cityID, $logicJunctionID, $timePoint, $dates)
    {
        $list = $this->getJunctionQuotaPointCountStat($cityID, $logicJunctionID, $timePoint, $dates);
        $confRule = $this->config->item('conf_rule');
        $fThreshold = $confRule['frequency_threshold'];

        $alarmResult = [];
        $juncQuestion = $this->config->item('junction_question');
        foreach ($list as $quota=>$quotaCount){
            foreach ($quotaCount as $hour => $count) {
                //添加报警配置信息
                if(!isset($alarmResult[$quota]['list'][$hour])){
                    $alarmResult[$quota]['info'] = $juncQuestion[$quota];
                }
                //报警规则过滤
                if ($count / count($dates) >= $fThreshold) {
                    $alarmResult[$quota]['list'][$hour] = 1;
                }else{
                    $alarmResult[$quota]['list'][$hour] = 0;
                }
            }
        }

        //数据排序
        foreach ($alarmResult as $k1=>$v1){
            $list = $v1['list'];
            $timeSorter = [];
            foreach ($v1['list'] as $k2=>$v2){
                $timeSorter[$k2] = strtotime(date("Y-m-d")." ".$k2.":00");
            }
            array_multisort($timeSorter, SORT_NUMERIC, SORT_ASC, $list);
            $alarmResult[$k1]['list'] = $list;
        }
        return $alarmResult;
    }

    /**
     * 获取单个路口时段粒度指标问题统计
     *
     * 返回格式:
     * ["spillover_index"]["15:30"] = 1
     * ["spillover_index"]["16:00"] = 1
     * ["spillover_index"]["16:30"] = 2
     *
     */
    public function getJunctionQuotaPointCountStat($cityID, $logicJunctionID, $timePoint, $dates)
    {
        $list = $this->getJunctionQuotaList($cityID, $logicJunctionID, $timePoint, $dates);
        $juncQuestion = $this->config->item('junction_question');
        $quotaCount = [];
        foreach ($list as $item) {
            foreach (array_keys($juncQuestion) as $alarmField) {
                if (!isset($quotaCount[$alarmField][$item["hour"]])) {
                    $quotaCount[$alarmField][$item["hour"]] = 0;
                }
                $quotaCount[$alarmField][$item["hour"]] += $item[$alarmField];
            }
        }
        return $quotaCount;
    }


    /**
     * 获取单个路口全部时段问题统计
     */
    public function getJunctionQuotaCountStat($cityID, $logicJunctionID, $timePoint, $dates)
    {
        $list = $this->getJunctionQuotaList($cityID, $logicJunctionID, $timePoint, $dates);
        //聚合每个问题次数
        $juncQuestion = $this->config->item('junction_question');
        $quotaCount = [];
        foreach ($list as $item) {
            foreach (array_keys($juncQuestion) as $alarmField) {
                if (!isset($quotaCount[$alarmField])) {
                    $quotaCount[$alarmField] = 0;
                }
                $quotaCount[$alarmField] += $item[$alarmField];
            }
        }
        return $quotaCount;
    }

    /**
     * 获取单路口指标明细 mock
     * @param $logicJunctionId
     * @param $timePoint
     * @param $dates
     * @return mixed
     */
    public function getJunctionQuotaList($cityID, $logicJunctionID, $timePoint, $dates)
    {
        if(count($timePoint)==2){
            unset($timePoint[1]);
        }
        $req = [
            'city_id' => (string)$cityID,
            'logic_junction_id' => $logicJunctionID,
            'time_points' => $timePoint,
            'dates' => $dates,
        ];
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetJunctionQuotaList', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            $result = [];
            if (!empty($res['data']['hits'])) {
                foreach ($res['data']['hits'] as $item) {
                    $result[] = $item['_source']??[];
                }
            }
            return $result;
        } else {
            return [];
        }
    }

    /**
     * 获取相位指标明细
     * @param $logicJunctionId
     * @param $timePoint
     * @param $dates
     * @return mixed
     */
    public function getFlowQuotaList($cityID, $logicJunctionID, $timePoint, $dates)
    {
        if(count($timePoint)==2){
            unset($timePoint[1]);
        }
        $req = [
            'city_id' => (string)$cityID,
            'logic_junction_id' => $logicJunctionID,
            'time_points' => $timePoint,
            'dates' => $dates,
        ];
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetFlowQuotaList', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            $result = [];
            if (!empty($res['data']['hits'])) {
                foreach ($res['data']['hits'] as $item) {
                    $result[] = $item['_source']??[];
                }
            }
            return $result;
        } else {
            return [];
        }
    }

    /**
     * 修改路口的flow，校准 phase_id 和 phase_name
     *
     * @param $flows
     *
     * @return array
     */
    private function adjustPhase($flow)
    {
        $phaseId = phase_map($flow['in_degree'], $flow['out_degree']);
        $phaseName = phase_name($phaseId);
        $flow['phase_id'] = $phaseId;
        $flow['phase_name'] = $phaseName;
        return $flow;
    }

    /**
     * 获取相位指标数据
     * @param $cityID
     * @param $logicJunctionID
     * @param $timePoint
     * @param $dates
     * @return array
     */
    public function getMovementQuota($cityID, $logicJunctionID, $timePoint, $dates)
    {
        $flowList = $this->getFlowQuotaList($cityID, $logicJunctionID, $timePoint, $dates);
        $itemTrajSum = [];
        foreach ($flowList as $item){
            if(!isset($quotaWeightSum[$item['logic_flow_id']]["flowStopWeight"])){
                $quotaWeightSum[$item['logic_flow_id']]["flowStopWeight"] = 0;
            }
            if(!isset($quotaWeightSum[$item['logic_flow_id']]["flowWeight"])){
                $quotaWeightSum[$item['logic_flow_id']]["flowWeight"] = 0;
            }
            $quotaWeightSum[$item['logic_flow_id']]["flowStopWeight"]+=$item["traj_count"]*$item["stop_time_cycle"];
            $quotaWeightSum[$item['logic_flow_id']]["flowWeight"]+=$item["traj_count"];

            foreach ($item as $quotaKey => $quotaValue) {
                if (in_array($quotaKey, ["logic_flow_id", "city_id", "dt", "hour", "logic_junction_id"])) {
                    continue;
                }
                if(!isset($itemTrajSum[$item['logic_flow_id']][$quotaKey])){
                    $itemTrajSum[$item['logic_flow_id']][$quotaKey] = 0;
                }
                if ($quotaKey == "queue_length") {
                    $itemTrajSum[$item['logic_flow_id']][$quotaKey] += $quotaValue*$item["traj_count"]*$item["stop_time_cycle"];
                }else{
                    $itemTrajSum[$item['logic_flow_id']][$quotaKey] += $quotaValue*$item["traj_count"];
                }
            }
        }

        //计算运算
        $result = [];
        $quotaRound = $this->config->item('flow_quota_round');
        foreach ($itemTrajSum as $flowID=>$itemSum){
            foreach ($itemSum as $quotaKey => $quotaValue){
                if($quotaKey=="queue_length"){
                    $avgValue = $quotaWeightSum[$flowID]["flowStopWeight"]>0?
                    ($quotaValue/$quotaWeightSum[$flowID]["flowStopWeight"]):0;
                }else{
                    $avgValue = $quotaWeightSum[$flowID]["flowWeight"]>0 ?
                    $quotaValue/$quotaWeightSum[$flowID]["flowWeight"] : 0;
                }
                $result[$flowID][$quotaKey] = isset($quotaRound[$quotaKey]['round'])
                    ? $quotaRound[$quotaKey]['round']($avgValue) : $avgValue;
            }
        }

        //相位信息
        $flowsMovement = $this->waymap_model->getFlowMovement($cityID, $logicJunctionID, "all", 1);
        $flows = array_map(function ($v) {
            $v = $this->adjustPhase($v);
            return $v;
        }, $flowsMovement);
        $movements = [];
        if (empty($flows)) {
            return [];
        }
        //使用flow备注名称统一处理名称
        $flowInfos = $this->waymap_model->flowsByJunctionOnline($logicJunctionID);
        $flowMap = [];
        if(!empty($flowInfos)){
            foreach ($flowInfos as $fk=> $fv){
                if($fv["desc"]!=""){
                    $flowMap[$fv['logic_flow_id']] = $fv["desc"];
                }
            }
        }
        foreach ($flows as $item) {
            $flowId = $item["logic_flow_id"];
            $comment = $item["phase_name"];
            if(isset($flowMap[$flowId])){
                $comment=$flowMap[$flowId];
            }
            $movementInfo = [
                "confidence" => $flowId,
                "movement_id" => $flowId,
                "comment" => $comment,
                "route_length" => $item["in_link_length"],
            ];
            if (isset($result[$flowId])) {
                $movementInfo = array_merge($movementInfo, $result[$flowId]);
                $movementInfo["confidence"] = $quotaRound["confidence"]['round']($result[$flowId]["traj_count"]);
                $movements[] = $movementInfo;
            }

            //防御性代码处理
            if(isset($movementInfo["queue_length"])
                && isset($movementInfo["route_length"])
                && isset($movementInfo["spillover_rate"])
                && isset($movementInfo["stop_rate"])
                && isset($movementInfo["free_flow_speed"])
            ){
                if(intval($movementInfo["queue_length"])>intval($movementInfo["route_length"])){
                    $movementInfo["queue_length"] = $movementInfo["route_length"];
                }
                if($movementInfo["spillover_rate"]>1){
                    $movementInfo["spillover_rate"] = 1;
                }
                if($movementInfo["stop_rate"]>1){
                    $movementInfo["stop_rate"] = 1;
                }
                if($movementInfo["free_flow_speed"]>80){
                    $movementInfo["free_flow_speed"] = 80;
                }
            }
        }
        return $movements;
    }

    /**
     * 获取路口所有方向信息(相位、方向、坐标)
     * @param array $data 请求参数
     * @param int   $uniqueDirection 是否唯一方向,只获取一段方向
     * @return array
     */
    public function getJunctionMapData($data,$uniqueDirection)
    {
        $logicJunctionID = $data['junction_id'];
        $cityID = $data['city_id'];
        $result = [];
        $newMapVersion = $this->waymap_model->getLastMapVersion();
        $flowsMovement = $this->waymap_model->getFlowMovement($cityID, $logicJunctionID, "all", 1);
        $flowsMovement = array_map(function ($v) {
            $v = $this->adjustPhase($v);
            return $v;
        }, $flowsMovement);
        $flowPhases = array_column($flowsMovement,"phase_name","logic_flow_id");

        // 路网相位信息
        $uniqueDirections = [];
        $ret = $this->waymap_model->getJunctionFlowLngLat($newMapVersion, $logicJunctionID, array_keys($flowPhases));
        foreach ($ret as $k => $v) {
            if (!empty($flowPhases[$v['logic_flow_id']])) {
                $phaseWord = $flowPhases[$v['logic_flow_id']];
                if($uniqueDirection){
                    $firstWord = mb_substr($flowPhases[$v['logic_flow_id']],0,1);
                    if(in_array($firstWord,["东","西","南","北"])){
                        $phaseWord = $firstWord;
                    }
                    if(mb_strlen($flowPhases[$v['logic_flow_id']])>1){
                        $secondWord = mb_substr($flowPhases[$v['logic_flow_id']],1,1);
                        if(in_array($secondWord,["东","西","南","北"]) && !in_array($secondWord,$uniqueDirections)){
                            $phaseWord = $secondWord;
                        }
                    }
                    if(in_array($phaseWord,$uniqueDirections)){
                        continue;
                    }
                }
                $uniqueDirections[] = $phaseWord;
                $result['dataList'][$k]['logic_flow_id'] = $v['logic_flow_id'];
                $result['dataList'][$k]['flow_label'] = $phaseWord;
                $result['dataList'][$k]['lng'] = $v['flows'][0][0];
                $result['dataList'][$k]['lat'] = $v['flows'][0][1];
            }
        }
        // 获取路口中心坐标
        $result['center']       = '';
        $centerData['logic_id'] = $logicJunctionID;
        $center                 = $this->waymap_model->getJunctionCenterCoords($logicJunctionID);

        $result['center']      = $center;
        $result['map_version'] = $newMapVersion;

        if (!empty($result['dataList'])) {
            $result['dataList'] = array_values($result['dataList']);
        }
        return $result;
    }

    /**
     * 获取某路口对应的报警信息
     *
     * @param string $logicJunctionId
     * @param array $timePoint
     * @param array $dates
     * @return array
     */
    public function getJunctionAlarmList($cityID, $logicJunctionID, $timePoint, $dates)
    {
        $confRule = $this->config->item('conf_rule');
        $juncQuestion = $this->config->item('junction_question');
        $totalCount = (count($timePoint)-1) * count($dates);
        $quotaCount = $this->getJunctionQuotaCountStat($cityID, $logicJunctionID, $timePoint, $dates);
        $alarmResult = [];
        $fThreshold = $confRule['frequency_threshold'];
        foreach ($quotaCount as $quota => $count) {
            if ($totalCount>0 && $count>0 && ($count/$totalCount>=$fThreshold)) {
                $alarmResult[$quota] = $juncQuestion[$quota];
            }
        }
        return $alarmResult;
    }

    /**
     *
     * @param $params
     * @return array
     */
    /*public function getSpaceTimeDiagram($params)
    {
        $result = $this->traj_model->getSpaceTimeDiagram($params);
        return $result;
    }*/


    public function getJunctionAlarmDataByHour($city_id, $dates) {
        $req = [
            'city_id' => $city_id,
            'dates' => $dates,
        ];
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetJunctionAlarmDataByHour', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            return $res['data'];
        } else {
            return [];
        }
    }

    public function getJunctionAlarmDataByJunction($city_id, $dates, $hour, $userPerm = []) {
        $req = [
            'city_id' => $city_id,
            'dates' => $dates,
            'hour' => $hour,
        ];
        if (!empty($userPerm['junction_id'])) {
            $req['junction_ids'] = $userPerm['junction_id'];
        }
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetJunctionAlarmDataByJunction', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            return $res['data'];
        } else {
            return [];
        }
    }

    public function GetJunctionAlarmDataByJunctionAVG($city_id, $dates, $hour, $userPerm = []) {
        $req = [
            'city_id' => $city_id,
            'dates' => $dates,
            'hour' => $hour,
        ];
        if (!empty($userPerm['junction_id'])) {
            $req['junction_ids'] = $userPerm['junction_id'];
        }
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetJunctionAlarmDataByJunctionAVG', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            return $res['data'];
        } else {
            return [];
        }
    }
}