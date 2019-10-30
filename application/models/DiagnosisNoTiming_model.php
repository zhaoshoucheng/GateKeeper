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
        ;
        parent::__construct();
        $this->load->config('disgnosisnotiming_conf');
        $this->load->model('waymap_model');
        $this->load->helper('phase');
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

    public function getSpecialFlowQuota($cityID,$logicFlowIDs,$startTime,$endTime){
        $st = date('Ymd',strtotime($startTime));
        $et = date('Ymd',strtotime($endTime));
        $req = [
            'city_id' => (string)$cityID,
            'logic_flow_list' => $logicFlowIDs,
            'start_time' => (int)$st,
            'end_time' => (int)$et,
        ];

        $url = $this->config->item('data_service_interface');

        $res = httpPOST($url . '/GetSpecialFlowQuotaInfo', $req, 0, 'json');


        if (!empty($res)) {
            $res = json_decode($res, true);
            $result = [];
            if(!empty($res['data']['logic_flow_id']['buckets'])){
                foreach ($res['data']['logic_flow_id']['buckets'] as  $fv){
                    $result[$fv['key']]=[];
                    foreach ($fv['ymd']['buckets'] as $yv){
                        $result[$fv['key']][$yv['key']]=[];
                        $qd = [];
                        foreach ($yv['hour']['buckets'] as $qv){
                            $qd[] = [
                                "hour"=>$qv['key'],
                                "speed"=>$qv['speed']['value']/$qv['total']['value'],
                                "stop_delay"=>$qv['stop_delay']['value']/$qv['total']['value'],
                                "stop_time_cycle"=>$qv['stop_time_cycle']['value']/$qv['total']['value'],
                                "traj_count"=>$qv['total']['value'],
                            ];
                        }
                        //TODO $qd 时间排序
                        $result[$fv['key']][$yv['key']] = $qd;
                    }
                }

            }
            return $result;
        } else {
            return [];
        }
    }

    /**
     * 直接获取相位全部指标明细,暂时只按照日期划分
     * @param $logicJunctionId
     * @param start_time 需要转换为 ymd的int格式
     * @param send_time  需要转换为 ymd的int格式
     * @return mixed
     */
    public function getFlowAllQuotaList($cityID, $logicJunctionID, $startTime, $endTime){
        $st = date('Ymd',strtotime($startTime));
        $et = date('Ymd',strtotime($endTime));
        $req = [
            'city_id' => (string)$cityID,
            'logic_junction_id' => $logicJunctionID,
            'start_time' => (int)$st,
            'end_time' => (int)$et,
        ];
        $url = $this->config->item('data_service_interface');

        $res = httpPOST($url . '/GetFlowAllQuotaInfo', $req, 0, 'json');

        if (!empty($res)) {
            $res = json_decode($res, true);
            $result = [];
            if(!empty($res['data']['logic_flow_id']['buckets'])){
                foreach ($res['data']['logic_flow_id']['buckets'] as  $fv){
                    $result[$fv['key']]=[];
                    foreach ($fv['ymd']['buckets'] as $yv){
                        $result[$fv['key']][$yv['key']]=[];
                        $result[$fv['key']][$yv['key']]=[];
                        $qd = [];
                        foreach ($yv['hour']['buckets'] as $qv){
                            $qd[] = [
                                "hour"=>$qv['key'],
                                "speed"=>$qv['speed']['value']/$qv['total']['value'],
                                "stop_delay"=>$qv['stop_delay']['value']/$qv['total']['value'],
                                "stop_time_cycle"=>$qv['stop_time_cycle']['value']/$qv['total']['value'],
                                "traj_count"=>$qv['total']['value'],
                            ];
                        }
                        //TODO $qd 时间排序
                        $result[$fv['key']][$yv['key']] = $qd;
                    }
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
     * 获取相位报警数据
     * @param $cityID
     * @param $logicJunctionID
     * @param $hour Y 批次号
     * @param $dt   Y 日期
     * @return array
     */
    public function getFlowDiagnosisAlarm($cityID, $logicJunctionID, $hour, $dt)
    {
        $req = [
            'city_id' => (string)$cityID,
            'logic_junction_id' => $logicJunctionID,
            'hour' => $hour,
            'dt' => $dt,
        ]; 
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetFlowDiagnosisAlarm', $req, 0, 'json');
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
        $flow['sort_key'] = phase_sort_key($flow['in_degree'], $flow['out_degree']);
        return $flow;
    }

    private function isSinglePoint($timePoint, $dates){
        if(count($dates)==1 && count($timePoint)==2){
            $time0 = date("Y-m-d")." ".$timePoint[0].":00";
            $time1 = date("Y-m-d")." ".$timePoint[1].":00";
            if((strtotime($time1)-strtotime($time0))==30*60){
                return true;
            }
        }
        return false;
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
        $alarmList = [];
        if($this->isSinglePoint($timePoint,$dates)){
            $alarmList = $this->getFlowDiagnosisAlarm($cityID, $logicJunctionID, current($timePoint), current($dates));
            $alarmList = array_column($alarmList, null, 'logic_flow_id');
        }
        $itemTrajSum = [];
        foreach ($flowList as $item){
            //计算权重
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

        $info32 = $this->waymap_model->getFlowInfo32($logicJunctionID);
        $flowMap = array_column($info32,"phase_name","logic_flow_id");
        //使用flow备注名称统一处理名称
        $keys = [];
        foreach ($flows as $idx => $flow) {
            $keys[$idx] = $flow['sort_key'];
        }
        array_multisort($keys, SORT_NUMERIC, SORT_ASC, $flows);
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
                $movementInfo["confidence"] = $quotaRound["confidence"]['round']($quotaWeightSum[$flowId]["flowWeight"]);


                $movementInfo["sort_key"] = $item["sort_key"];

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

                //追加alarm信息
                $movementInfo["is_empty"] = 0;
                $movementInfo["is_oversaturation"] = 0;
                $movementInfo["is_spillover"] = 0;
                if(isset($alarmList[$flowId])){
                    $movementInfo["is_empty"] = $alarmList[$flowId]["is_empty"];
                    $movementInfo["is_oversaturation"] = $alarmList[$flowId]["is_oversaturation"];
                    $movementInfo["is_spillover"] = $alarmList[$flowId]["is_spillover"];
                }
                $movements[] = $movementInfo;
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
        if ($uniqueDirection) {
            $flowsMovement = $this->waymap_model->getFlowMovement($cityID, $logicJunctionID, "all", 1);
            $flowsMovement = array_map(function ($v) {
                $v = $this->adjustPhase($v);
                return $v;
            }, $flowsMovement);
            $flowPhases = array_column($flowsMovement,"phase_name","logic_flow_id");
        } else {
            $info32 = $this->waymap_model->getFlowInfo32($logicJunctionID);
            $flowPhases = array_column($info32,"phase_name","logic_flow_id");
        }

        // 路网相位信息
        $ret = $this->waymap_model->getJunctionFlowLngLat($newMapVersion, $logicJunctionID, array_keys($flowPhases));
        $uniqueDirections = [];

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


    public function getJunctionAlarmDataByHour($city_id, $dates, $userPerm = []) {
        $req = [
            'city_id' => $city_id,
            'dates' => $dates,
        ];
        if (isset($userPerm['junction_id']) && !empty($userPerm['junction_id'])) {
            $req['junction_ids'] = array_values($userPerm['junction_id']);
        }
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
            $req['junction_ids'] = array_values($userPerm['junction_id']);
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
    
    public function GetLastAlarmDateByCityID($cityID){
        $url = $this->config->item('data_service_interface');
        $res = httpGET($url . '/GetLastAlarmDateByCityID?city_id='.$cityID, [], 0);
        if (!empty($res)) {
            $res = json_decode($res, true);
            if (isset($res['data']['dt'])) {
                return $res['data']['dt'];
            }
        }
        return date("Y-m-d",strtotime("-1 day"));
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

    public function GetBaseQuotaFlowData($params){
        $cityID = $params["city_id"];
        $date = [$params["date"]];
        $logicJunctionID = $params["logic_junction_id"];
        $timePoints = [$params["hour"]];
        $quotaList = $this->getFlowQuotaList($cityID, $logicJunctionID, $timePoints, $date);
        return $quotaList;
    }

    public function GetDiagnosisFlowData($params){
        $cityID = $params["city_id"];
        $date = $params["date"];
        $logicJunctionID = $params["logic_junction_id"];
        $timePoint = $params["hour"];
        $alarmList = $this->getFlowDiagnosisAlarm($cityID, $logicJunctionID, $timePoint, $date); 
        return $alarmList;
    }
}
