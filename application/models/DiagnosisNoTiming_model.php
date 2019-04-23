<?php

/**
 * Class DiagnosisNoTiming_model
 * @property Waymap_model $waymap_model
 */
class DiagnosisNoTiming_model extends CI_Model
{
    public function __construct()
    {
        $this->load->config('disgnosisnotiming_conf');
        parent::__construct();
        $this->load->model('waymap_model');
        $this->load->helper('phase');
    }

    /**
     * 获取单个路口指标统计
     */
    public function getJunctionQuotaCountStat($logicJunctionId, $timePoint, $dates)
    {
        $list = $this->getJunctionQuotaList($logicJunctionId, $timePoint, $dates);
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
    public function getJunctionQuotaList($logicJunctionId, $timePoint, $dates)
    {
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

    public function getMovementQuota($cityID, $logicJunctionID, $timePoint, $dates)
    {
        $flowList = $this->getFlowQuotaList($logicJunctionID, $timePoint, $dates);

        //加权指标计算
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

    /**
     * 获取某路口对应的报警信息
     *
     * @param string $logicJunctionId
     * @param array $timePoint
     * @param array $dates
     * @return array
     */
    public function getJunctionAlarmList($logicJunctionId, $timePoint, $dates)
    {
        $confRule = $this->config->item('conf_rule');
        $juncQuestion = $this->config->item('junction_question');
        $fThreshold = $confRule['frequency_threshold'];
        $totalCount = count($timePoint) * count($dates);
        $quotaCount = $this->getJunctionQuotaCountStat($logicJunctionId, $timePoint, $dates);
        $alarmResult = [];
        foreach ($quotaCount as $quota => $count) {
            if ($count / $totalCount > $fThreshold) {
                $alarmResult[$quota] = $juncQuestion[$quota];
            }
        }
        return $alarmResult;
    }
}