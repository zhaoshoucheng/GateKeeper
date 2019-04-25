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
                if ($count / count($dates) > $fThreshold) {
                    $alarmResult[$quota]['list'][$hour] = 1;
                }else{
                    $alarmResult[$quota]['list'][$hour] = 0;
                }
            }
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
                    $quotaCount[$alarmField][$item["hour"]] = $item[$alarmField];
                } else {
                    $quotaCount[$alarmField][$item["hour"]] += $item[$alarmField];
                }
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
                    $quotaCount[$alarmField] = $item[$alarmField];
                } else {
                    $quotaCount[$alarmField] += $item[$alarmField];
                }
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
        $newDates = [];
        foreach ($dates as $item){
            if(strlen($item)!=8){
                continue;
            }
            $newDates[] = substr($item, 0, 4)."-".substr($item, 4, 2)."-".substr($item, 6, 2);
        }
        $req = [
            'city_id' => $cityID,
            'logic_junction_id' => $logicJunctionID,
            'time_points' => $timePoint,
            'dates' => $newDates,
        ];
        print_r($newDates);exit;
        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/GetJunctionQuotaList', $req, 0, 'json');
        echo $url . '/GetJunctionQuotaList';exit;
        echo json_encode($req);exit;
        print_r($req);exit;
        if (!empty($res)) {
            $res = json_decode($res, true);
            return $res['data'];
        } else {
            return [];
        }

        $qData = file_get_contents("junction_duration_v6.json");
        $list = json_decode($qData, true);
        $result = [];
        if (!empty($list['hits'])) {
            foreach ($list['hits'] as $item) {
                $result[] = $item['_source']??[];
            }
        }
        return $result;
    }

    /**
     * 获取相位指标明细
     * @param $logicJunctionId
     * @param $timePoint
     * @param $dates
     * @return mixed
     */
    public function getFlowQuotaList($logicJunctionId, $timePoint, $dates)
    {
        $qData = file_get_contents("flow_duration_v6.json");
        $list = json_decode($qData, true);
        $result = [];
        if (!empty($list['hits'])) {
            foreach ($list['hits'] as $item) {
                $result[] = $item['_source']??[];
            }
        }
        return $result;
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
        $flowList = $this->getFlowQuotaList($logicJunctionID, $timePoint, $dates);
        //加权指标计算
        //计算权重值数据
        //queue_length权重为traj_count*stop_time_cycle
        //其他指标权重为traj_count
        //权重使用说明:
        // traj_count   |   stop_time_cycle |   flow_id |   hour    |   queue_weight        |   weight
        //  10          |   1               |   512     |   15:00   |   10/(10*1+20*1+25*1) |   10/(10+20+25)
        //  20          |   1               |   512     |   15:30   |   20/(10*1+20*1+25*1) |   20/(10+20+25)
        //  25          |   1               |   512     |   16:00   |   25/(10*1+20*1+25*1) |   25/(10+20+25)
        //  10          |   2               |   555     |   15:00   |   10/(10*2)           |   10/(20)
        $flowTrajSum = [];
        $flowTrajStoptimeSum = [];
        foreach ($flowList as $item) {
            if (!isset($flowTrajSum[$item['logic_flow_id']])) {
                $flowTrajSum[$item['logic_flow_id']] = $item["traj_count"];
            } else {
                $flowTrajSum[$item['logic_flow_id']] += $item["traj_count"];
            }
            if (!isset($flowTrajStoptimeSum[$item['logic_flow_id']])) {
                $flowTrajStoptimeSum[$item['logic_flow_id']] = $item["traj_count"] * $item["stop_time_cycle"];
            } else {
                $flowTrajStoptimeSum[$item['logic_flow_id']] += $item["traj_count"] * $item["stop_time_cycle"];
            }
        }
        $result = [];
        $quotaRound = $this->config->item('flow_quota_round');
        foreach ($flowList as $item) {
            foreach ($item as $quotaKey => $quotaValue) {
                if (in_array($quotaKey, ["logic_flow_id", "city_id", "dt", "logic_junction_id"])) {
                    continue;
                }
                if ($quotaKey == "queue_length") {
                    $quotaValue = (($item["traj_count"] * $item["stop_time_cycle"]) / $flowTrajStoptimeSum[$item["logic_flow_id"]]) * $quotaValue;
                } else {
                    $quotaValue = ($item["traj_count"] / $flowTrajSum[$item["logic_flow_id"]]) * $quotaValue;
                }
                $quotaValue = isset($quotaRound[$quotaKey]['round'])
                    ? $quotaRound[$quotaKey]['round']($quotaValue) : $quotaValue;
                if (!isset($result['logic_flow_id'][$quotaKey])) {
                    $result[$item['logic_flow_id']][$quotaKey] = $quotaValue;
                } else {
                    $result[$item['logic_flow_id']][$quotaKey] += $quotaValue;
                }
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

    public function getJunctionMapData($data)
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
        $ret = $this->waymap_model->getJunctionFlowLngLat($newMapVersion, $logicJunctionID, array_keys($flowPhases));
        foreach ($ret as $k => $v) {
            if (!empty($flowPhases[$v['logic_flow_id']])) {
                $result['dataList'][$k]['logic_flow_id'] = $v['logic_flow_id'];
                $result['dataList'][$k]['flow_label'] = $flowPhases[$v['logic_flow_id']];
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
        $totalCount = count($timePoint) * count($dates);
        $quotaCount = $this->getJunctionQuotaCountStat($cityID, $logicJunctionID, $timePoint, $dates);
        $alarmResult = [];
        $fThreshold = $confRule['frequency_threshold'];
        foreach ($quotaCount as $quota => $count) {
            if ($count / $totalCount > $fThreshold) {
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