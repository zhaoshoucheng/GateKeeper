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
            'city_id' => $cityID,
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
            'city_id' => $cityID,
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
     * 通过指标数据获取某天的指标权重
     * @param $flowList
     * @return array
     */
    private function getMovementDtWeightByFlowList($flowList){
        //加权指标计算 == start ==
        //queue_length权重为traj_count*stop_time_cycle
        //其他指标权重为traj_count
        //权重使用说明:
// traj_count| stop_time_cycle | flow_id | dt           | queue_weight        | weight       | speed
//  10       | 4               | 512     | 2019-05-13   | 40/(10*4+20*5+10*2) | 10/(10+20+25)| 5
//  20       | 5               | 512     | 2019-05-14   | 100/(10*4+20*5+10*2)| 20/(10+20+25)| 4
//  10       | 2               | 512     | 2019-05-15   | 20/(10*4+20*5+10*2) | 10/(10+20+25)| 3
//  10       | 2               | 513     | 2019-05-13   | 20/(10*2)           | 10/(10)      | 3
        //求flow_id=512三天的speed平均值: (5+4+3)/3
        //求flow_id=512三天的speed加权平均: 5*(10/55)+4*(10/55)+3*(10/55)

        $flowTrajSum = [];  //相位轨迹统计
        $flowTrajStoptimeSum = [];  //相位停车轨迹统计
        $allTrajSum=[]; //全部轨迹统计
        $allTrajStoptimeSum=[];  //全部停车轨迹统计
        $flowWeight = []; //相位权重
        $flowStoptimeWeight = []; //相位停车权重
        foreach ($flowList as $item) {
            if (!isset($flowTrajSum[$item['logic_flow_id']][$item['dt']])) {
                $flowTrajSum[$item['logic_flow_id']][$item['dt']] = 0;
            }
            if (!isset($flowTrajStoptimeSum[$item['logic_flow_id']][$item['dt']])) {
                $flowTrajStoptimeSum[$item['logic_flow_id']][$item['dt']] = 0;
            }
            if (!isset($allTrajSum[$item['logic_flow_id']])) {
                $allTrajSum[$item['logic_flow_id']] = 0;
            }
            if (!isset($allTrajStoptimeSum[$item['logic_flow_id']])) {
                $allTrajStoptimeSum[$item['logic_flow_id']] = 0;
            }
            $flowTrajSum[$item['logic_flow_id']][$item['dt']] += $item["traj_count"];
            $flowTrajStoptimeSum[$item['logic_flow_id']][$item['dt']] += $item["traj_count"] * $item["stop_time_cycle"];
            $allTrajSum[$item['logic_flow_id']]+=$item["traj_count"];
            $allTrajStoptimeSum[$item['logic_flow_id']]+=$item["traj_count"] * $item["stop_time_cycle"];
        }
        foreach ($flowTrajSum as $flowID=>$flowList){
            foreach ($flowList as $dt=>$item){
                $flowWeight[$flowID][$dt] =
                    $allTrajSum[$flowID]>0
                        ? $flowTrajSum[$flowID][$dt]/$allTrajSum[$flowID] : 0;
                $flowStoptimeWeight[$flowID][$dt] =
                    $allTrajStoptimeSum[$flowID]>0
                        ? $flowTrajStoptimeSum[$flowID][$dt]/$allTrajStoptimeSum[$flowID]:0;
            }
        }
//        print_r($flowStoptimeWeight);exit;
        return [$flowWeight,$flowStoptimeWeight];
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
        $flowWeight = []; //天级别相位权重
        $flowStoptimeWeight = []; //天级别相位停车权重
        list($flowWeight,$flowStoptimeWeight) = $this->getMovementDtWeightByFlowList($flowList);
        $result = [];
        $quotaRound = $this->config->item('flow_quota_round');
        foreach ($flowList as $item) {
            foreach ($item as $quotaKey => $quotaValue) {
                if (in_array($quotaKey, ["logic_flow_id", "city_id", "dt", "logic_junction_id"])) {
                    continue;
                }
                if($quotaValue>0){
                    if ($quotaKey == "queue_length") {
                        $quotaValue = $flowStoptimeWeight[$item["logic_flow_id"]][$item["dt"]] * $quotaValue;
                    } else {
                        $quotaValue = $flowWeight[$item["logic_flow_id"]][$item["dt"]] * $quotaValue;
                    }
                }

                $quotaValue = isset($quotaRound[$quotaKey]['round'])
                    ? $quotaRound[$quotaKey]['round']($quotaValue) : $quotaValue;
                if (empty($result[$item['logic_flow_id']][$quotaKey])) {
                    $result[$item['logic_flow_id']][$quotaKey] = 0;
                }
                $result[$item['logic_flow_id']][$quotaKey] += $quotaValue;
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
        foreach ($flows as $item) {
            $flowId = $item["logic_flow_id"];
            $movementInfo = [
                "confidence" => $flowId,
                "movement_id" => $flowId,
                "comment" => $item["phase_name"],
                "route_length" => $item["in_link_length"],
            ];
            if (isset($result[$flowId])) {
                $movementInfo = array_merge($movementInfo, $result[$flowId]);
                $movementInfo["confidence"] = $quotaRound["confidence"]['round']($result[$flowId]["traj_count"]);
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

    public function getJunctionAlarmDataByJunction($city_id, $dates, $hour) {
        $req = [
            'city_id' => $city_id,
            'dates' => $dates,
            'hour' => $hour,
        ];
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetJunctionAlarmDataByJunction', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            return $res['data'];
        } else {
            return [];
        }
    }

    public function GetJunctionAlarmDataByJunctionAVG($city_id, $dates, $hour) {
        $req = [
            'city_id' => $city_id,
            'dates' => $dates,
            'hour' => $hour,
        ];
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