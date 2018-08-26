<?php
/********************************************
# desc:    单点路口优化对比报告模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-23
********************************************/

class Junctioncomparison_model extends CI_Model
{
    private $tb = 'flow_duration_v6_';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $this->load->model('waymap_model');
        $this->load->config('junctioncomparison_conf');
    }

    /**
     * 获取单点路口优化对比
     * @param $data['logic_junction_id']   string   Y 路口ID
     * @param $data['city_id']             interger Y 城市ID
     * @param $data['base_start_date']     string   Y 基准开始日期 格式：yyyy-mm-dd
     * @param $data['base_end_date']       string   Y 基准结束日期 格式：yyyy-mm-dd
     * @param $data['evaluate_start_date'] string   Y 评估开始日期 格式：yyyy-mm-dd
     * @param $data['evaluate_end_date']   string   Y 评估结束日期 格式：yyyy-mm-dd
     * @param $data['week']                array    Y 星期 0-6
     * @param $data['schedule_start']      string   Y 时段开始时间 例：00:00
     * @param $data['schedule_end']        string   Y 时段结束时间 例：00:30
     * @param $data['quota_key']           string   Y 指标key
     * @return array
     */
    public function getQuotaInfo($data)
    {
        if (empty($data)) {
            return (object)[];
        }

        $table = $this->tb . $data['city_id'];
        // 判断数据表是否存在
        if (!$this->isTableExisted($table)) {
            com_log_warning('_itstool_JuctionCompareReport_table_error', 0, '数据表不存在', compact("table"));
            return (object)[];
        }

        // 获取路口名称
        $junctionsInfo = $this->waymap_model->getJunctionInfo($data['logic_junction_id']);
        list($junctionName) = array_column($junctionsInfo, 'name');

        // 获取路口所有相位
        $allFlows = $this->waymap_model->getFlowsInfo($data['logic_junction_id']);
        if (empty($allFlows)) {
            return (object)[];
        }

        // 获取基准、评估日期指标加权平均值所需数据
        $publicData = [
            'logic_junction_id' => $data['logic_junction_id'],
            'quota_key'         => $data['quota_key'],
        ];

        /* 获取基准日期指标加权平均值 计算出需要查的周几具体日期*/
        $dateWeek = $this->getDate($data['base_start_date'], $data['base_end_date'], $data['week']);
        $baseDateArr = $dateWeek['date'];
        $baseWeekDays = $dateWeek['week'];
        $publicData['date'] = $baseDateArr;
        $baseQuotaData = $this->getQuotaInfoByDate($table, $publicData);
        // 相位->日期->时间->值
        $newBaseQuotaData = [];
        foreach ($baseQuotaData as $val) {
            $newBaseQuotaData[$val['logic_flow_id']][$val['date']][$val['hour']] = $val['quota_value'];
        }

        /* 获取评估日期指标加权平均值 计算出需要查的周几具体日期*/
        $dateWeek = $this->getDate($data['evaluate_start_date'], $data['evaluate_end_date'], $data['week']);
        $evaluateDateArr = $dateWeek['date'];
        $evaluateWeekDays = $dateWeek['week'];
        $publicData['date'] = $evaluateDateArr;
        $evaluateQuotaData = $this->getQuotaInfoByDate($table, $publicData);
        // 相位->日期->时间->值
        $newEvaluateQuotaData = [];
        foreach ($evaluateQuotaData as $val) {
            $newEvaluateQuotaData[$val['logic_flow_id']][$val['date']][$val['hour']] = $val['quota_value'];
        }

        if (empty($newBaseQuotaData) && empty($newEvaluateQuotaData)) {
            com_log_warning('_itstool_JuctionCompareReport_data_error', 0, '评估&基准数据都没有', compact("publicData"));
            return (object)[];
        }

        // 处理时段
        $scheduleArr = [];
        $scheduleStart = strtotime($data['schedule_start']);
        $scheduleEnd = strtotime($data['schedule_end']);
        for ($i = $scheduleStart; $i <= $scheduleEnd; $i += 30 * 60) {
            $scheduleArr[] = date('H:i', $i);
        }

        $formatData = [];
        foreach ($allFlows[$data['logic_junction_id']] as $k=>$v) {
            // flow信息
            $formatData[$k]['flow_info'] = [
                'logic_flow_id' => $k,
                'flow_name'     => $v,
            ];

            // 基准
            if (array_key_exists($k, $newBaseQuotaData)) {
                foreach ($baseWeekDays as $day) {
                    foreach ($scheduleArr as $hour) {
                        $formatData[$k]['base_time_list'][$hour][$day] = $newBaseQuotaData[$k][$day][$hour] ?? '';
                    }
                }
            }

            // 评估
            if (array_key_exists($k, $newEvaluateQuotaData)) {
                foreach ($evaluateWeekDays as $day) {
                    foreach ($scheduleArr as $hour) {
                        $formatData[$k]['evaluate_time_list'][$hour][$day] = $newEvaluateQuotaData[$k][$day][$hour] ?? '';
                    }
                }
            }

            if (empty($formatData[$k]['evaluate_time_list']) && empty($formatData[$k]['base_time_list'])) {
                unset($formatData[$k]);
            }
        }

        if (empty($formatData)) {
            return (object)[];
        }

        $infoData = [
            'quotaKey'    => $data['quota_key'],
            'junctionName' => $junctionName,
            'allFlows'     => $allFlows[$data['logic_junction_id']],
        ];
        $result = $this->formatData($formatData, $scheduleArr, $infoData);
        if (empty($result)) {
            return (object)[];
        }

        return $result;
    }

    /**
     * 处理指标数据
     * @param $data                array  数据源
     * @param $schedule            array  时段配置具体时间['07:00', '07:30']
     * @param $info['quotaKey']     string 指标KEY
     * @param $info['junctionName'] string 路口名称
     * @param $info['allFlows']     array  flow信息 [相位ID=>相位名称]
     * @return array
     */
    private function formatData($data, $schedule, $info)
    {
        $result = [];
        $quotaConf = $this->config->item('quotas');

        foreach ($data as $k=>$v) {
            $result['dataList'][$k]['flow_info'] = $v['flow_info'];
            foreach ($v['base_time_list'] as $hour=>$val) {
                $value = array_sum($val) / count($val);
                $result['dataList'][$k]['base_list'][$hour] = $value;
                $result['dataList'][$k]['base'][] = [$value, $hour];
            }

            foreach ($v['evaluate_time_list'] as $hour=>$val) {
                $value = array_sum($val) / count($val);
                $result['dataList'][$k]['evaluate_list'][$hour] = $value;
                $result['dataList'][$k]['evaluate'][] = [$value, $hour];
            }
        }
        if (empty($result)) {
            return [];
        }

        // 获取基准、评估需要高亮的相位及计算高亮相位所需数据
        $baseHighLightPhaseAndMaxFlowArr = $this->getHighLightPhaseAndMaxFlowArr($result['dataList']
                                                                                , 'base'
                                                                                , $quotaConf[$info['quotaKey']]['formula']);
        $evaluateHighLightPhaseAndMaxFlowArr = $this->getHighLightPhaseAndMaxFlowArr($result['dataList']
                                                                                , 'evaluate'
                                                                                , $quotaConf[$info['quotaKey']]['formula']);

        /* 统计基准高亮相位连续时段 */
        $baseMaxFlowArr = $baseHighLightPhaseAndMaxFlowArr['maxFlowArr'];
        $baseHighLightPhase = $baseHighLightPhaseAndMaxFlowArr['highLightPhase'];
        $baseContinueTime = $this->getHighLightPhaseContinueTime($baseMaxFlowArr[$baseHighLightPhase]
                                                                , $quotaConf[$info['quotaKey']]['formula']);
        $baseFlowName = $info['allFlows'][$baseHighLightPhase];
        $baseContinue = '';
        foreach ($baseContinueTime as $k=>$v) {
            $baseContinue .= empty($baseContinue) ? $v['start'] . '-' . $v['end'] : ',' . $v['start'] . '-' . $v['end'];
        }

        /* 统计评估高亮相位连续时段 */
        $evaluateMaxFlowArr = $evaluateHighLightPhaseAndMaxFlowArr['maxFlowArr'];
        $evaluateHighLightPhase = $evaluateHighLightPhaseAndMaxFlowArr['highLightPhase'];
        $evaluateContinueTime = $this->getHighLightPhaseContinueTime($evaluateMaxFlowArr[$evaluateHighLightPhase]
                                                                    , $quotaConf[$info['quotaKey']]['formula']);
        $evaluateFlowName = $info['allFlows'][$evaluateHighLightPhase];
        $evaluateContinue = '';
        foreach ($evaluateContinueTime as $k=>$v) {
            $evaluateContinue .= empty($evaluateContinue) ? $v['start'] . '-' . $v['end'] : ',' . $v['start'] . '-' . $v['end'];
        }

        $result['continue_time'] = [
            'base'     => $baseContinueTime,
            'evaluate' => $evaluateContinueTime,
        ];

        foreach ($result['dataList'] as $flow=>$v) {
            if ($flow == $baseHighLightPhase) {
                $result['dataList'][$flow]['flow_info']['base_highlight'] = 1;
            } else {
                $result['dataList'][$flow]['flow_info']['base_highlight'] = 0;
            }

            if ($flow == $evaluateHighLightPhase) {
                $result['dataList'][$flow]['flow_info']['evaluate_highlight'] = 1;
            } else {
                $result['dataList'][$flow]['flow_info']['evaluate_highlight'] = 0;
            }
        }

        // 差距最大方向时间点
        $tempHourVal = [];
        foreach ($result['dataList'] as $flow=>$val) {
            foreach ($schedule as $hour) {
                $diffVal = $val['base_list'][$hour] - $val['evaluate_list'][$hour] < 0
                            ? ($val['base_list'][$hour] - $val['evaluate_list'][$hour]) * -1
                            : $val['base_list'][$hour] - $val['evaluate_list'][$hour];
                $tempHourVal[$flow . '-' . $hour] = $diffVal;
            }
        }
        list($diffMaxFlow, $diffMaxHour) = explode('-', array_search(max($tempHourVal), $tempHourVal));
        $baseDiffValue = $result['dataList'][$diffMaxFlow]['base_list'][$diffMaxHour];
        $evaluateDiffValue = $result['dataList'][$diffMaxFlow]['evaluate_list'][$diffMaxHour];
        $result['diff_info'] = [
            'flow_id' => $diffMaxFlow,
            'base' => [
                $baseDiffValue,
                $diffMaxHour,
            ],
            'evaluate' => [
                $evaluateDiffValue,
                $diffMaxHour,
            ],
        ];
        $flowName = $info['allFlows'][$diffMaxFlow];
        $descHour = $diffMaxHour;
        if ($baseDiffValue >= $evaluateDiffValue) {
            $maxValue = $baseDiffValue;
            $minValue = $evaluateDiffValue;
        } else {
            $maxValue = $evaluateDiffValue;
            $minValue = $baseDiffValue;
        }

        $describe = [
            $info['junctionName'],
            $baseFlowName,
            $baseContinue,
            $evaluateFlowName,
            $evaluateContinue,
            $flowName,
            $descHour,
            $maxValue,
            $minValue,
        ];

        foreach ($result['dataList'] as $k=>$v) {
            unset($result['dataList'][$k]['base_list']);
            unset($result['dataList'][$k]['evaluate_list']);
        }

        $result['dataList'] = array_values($result['dataList']);
        $result['quota_info'] = [
            'name' => $quotaConf[$info['quotaKey']]['name'],
            'desc' => $quotaConf[$info['quotaKey']]['desc'],
        ];

        $result['describe_info'] = $quotaConf[$info['quotaKey']]['describe']($describe);
        $result['summary_info'] = $quotaConf[$info['quotaKey']]['name'] . '由' . $maxValue . '变化为' . $minValue;

        return $result;
    }

    /**
     * 获取基准、评估需要高亮的相位及计算高亮相位所需数据
     * @param $data    数据源
     * @param $type    需要计算的类型 base|evaluate
     * @param $formula 计算规则 max | min
     * @return array
     */
    private function getHighLightPhaseAndMaxFlowArr($data, $type, $formula)
    {
        // 每个时间点指标值最大的相位统计容器
        $maxValueCount = [];
        // 临时数组 放置每个时间点每个相位的指标平均值
        $tempData = [];
        foreach ($data as $direc => $val) {
            foreach ($val['' . $type . '_list'] as $hour=>$v) {
                $tempData[$hour][$direc] = $v;
                $maxValueCount[$direc] = 0;
            }
        }
        // 统计指标最大值的相位出现的次数
        $maxFlowArr = [];
        foreach ($tempData as $k=>$v) {
            if ($formula == 'max') {
                $maxFlow = array_search(max($v), $v);
            } else {
                $maxFlow = array_search(min($v), $v);
            }
            $maxValueCount[$maxFlow] += 1;
            $maxFlowArr[$maxFlow][strtotime($k)] = $v[$maxFlow];
        }

        // 需要高亮的相位
        $avgPhase = [];
        $highLightPhaseArr = array_keys($maxValueCount, max($maxValueCount));
        if (count($highLightPhaseArr) >= 1) { // 有相同次数的相位，取平均值最大的
            foreach ($highLightPhaseArr as $k=>$v) {
                $avgPhase[$v] = array_sum(array_column($tempData, $v)) / $maxValueCount[$v];
            }
        }
        if ($formula == 'max') {
            $highLightPhase = array_search(max($avgPhase), $avgPhase);
        } else {
            $highLightPhase = array_search(min($avgPhase), $avgPhase);
        }

        return [
            'highLightPhase' => $highLightPhase,
            'maxFlowArr'     => $maxFlowArr,
        ];
    }

    /**
     * 获取高亮相位连续时间段 用于前端画框
     * 规则：该相位连续次数最多的时间段；若持续时间相同，取该持续时间内平均指标最大(有的指标取最小)的时间段,如果还相同的情况下，则都显示。
     * @param $data    高亮相伴每个时间点的指标值 [时间点=>指标值] 例：['00:00'=>1, ......]
     * @param $formula 计算规则 max | min
     * @return array [['start'=>xx, 'end'=>xx]]
     */
    private function getHighLightPhaseContinueTime($data, $formula)
    {
        $minTime = min(array_keys($data));
        $maxTime = max(array_keys($data));
        $startTime = $endTime = '';
        $countArr = [];

        while ($minTime <= $maxTime) {
            if ($startTime == '') {
                $startTime = $minTime;
            }
            if (array_key_exists($startTime, $data)) {
                if (!array_key_exists($minTime + 30 * 60, $data)) {
                    $endTime = $minTime;
                    $count = $endTime - $startTime == 0 ? 1 : ($endTime - $startTime) / 60 / 30 + 1;
                    $countArr[$startTime . '-' . $endTime] = $count;
                    $startTime = '';
                } else {
                    $endTime = $minTime + 30 * 60;
                }
            } else {
                $startTime = $endTime = $minTime + 30 * 60;
            }

            $minTime += (30 * 60);
        }

        $maxCountArr = array_keys($countArr, max($countArr));

        if (count($maxCountArr) >= 1) {
            foreach ($maxCountArr as $k=>$v) {
                list($start, $end) = explode('-', $v);
                $totalVal = 0;
                for ($i = $start; $i <= $end; $i += 30 * 60) {
                    $totalVal += $data[$i];
                }
                $avgVal[$v] = $totalVal / $countArr[$v];
            }
        }

        if ($formula == 'max') {
            $continueTime = array_keys($avgVal, max($avgVal));
        } else {
            $continueTime = array_keys($avgVal, min($avgVal));
        }

        $continueTime = array_map(function($val){
            list($start, $end) = explode('-', $val);
            return [
                'start' => date('H:i', $start),
                'end' => date('H:i', $end),
            ];
        }, $continueTime);

        return $continueTime;
    }

    /**
     * 根据首尾日期及周几获取全部具体日期
     * @param $startDate string 首日期 Y-m-d
     * @param $endDate   string 尾日期 Y-m-d
     * @param $week      array  周几 0-6
     * @return array
     */
    private function getDate($startDate, $endDate, $week)
    {
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        $dateArr = [];
        $weekDays = [];
        for ($i = $startDate; $i <= $endDate; $i += 24 * 3600) {
            $dateArr[] = date('Y-m-d', $i);
            foreach ($week as $k=>$v) {
                if (date('w', $i) == $v) {
                    $weekDays[$i] = date('Y-m-d', $i);
                }
            }
        }
        return [
            'date' => $dateArr,
            'week' => $weekDays,
        ];
    }

    /**
     * 获取单点路口优化对比
     * @param $table                     string   Y 数据表
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @param $data['date']              array    Y 所需要查询的日期
     * @param $data['quota_key']         string   Y 指标key
     * @return array
     */
    private function getQuotaInfoByDate($table, $data)
    {
        $quotaFormula = 'sum(`' . $data['quota_key'] . '` * `traj_count`) / sum(`traj_count`)';
        $this->db->select("logic_flow_id, hour,date,  {$quotaFormula} as quota_value");
        $this->db->from($table);
        $where = 'logic_junction_id = "' . $data['logic_junction_id'] . '"';
        $where .= ' and traj_count >= 10';
        $this->db->where($where);
        $this->db->where_in('date', $data['date']);
        $this->db->group_by('date, hour');
        $res = $this->db->get()->result_array();
        if (!$res) {
            return (object)[];
        }

        return $res;
    }

    /**
     * 校验数据表是否存在
     */
    private function isTableExisted($table)
    {
        $isExisted = $this->db->table_exists($table);
        return $isExisted;
    }
}