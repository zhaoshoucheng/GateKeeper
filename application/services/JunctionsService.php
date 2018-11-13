<?php
/**
 * 路口相关接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-01
 */

namespace Services;

class JunctionsService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('nconf');

        $this->load->model('waymap_model');
        $this->load->model('junction_model');
        $this->load->model('timing_model');
    }

    /**
     * 获取全城路口信息
     * @param $params['task_id']    int      任务ID
     * @param $params['type']       int      计算指数类型 1：统合 0：时间点
     * @param $params['city_id']    int      城市ID
     * @param $params['time_point'] string   评估时间点 指标计算类型为1时非空
     * @param $params['confidence'] int      置信度
     * @param $params['quota_key']  string   指标key
     * @return mixed
     * @throws \Exception
     */
    public function getAllCityJunctionInfo($params)
    {
        // 获取全城路口模板 没有模板就没有lng、lat = 画不了地图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($params['city_id']);
        if (!$allCityJunctions) {
            throw new \Exception('没有获取到全城路口！', ERR_REQUEST_WAYMAP_API);
        }

        // 查询字段定义
        $select = 'id, junction_id';
        // where条件
        $where = 'task_id = ' . $params['task_id'];
        // group_by
        $groupBy = '';
        // limit
        $limit = '';

        // 指标key 指标KEY与数据表字段相同
        $quotaKey = $params['quota_key'];

        // 按查询方式组织select
        if ($params['type'] == 1) { // 综合查询
            $select .= ", max({$quotaKey}) as {$quotaKey}";
        } else {
            $select .= ',' . $quotaKey;
        }

        // where条件组织
        if ($params['type'] == 0) { // 按时间点查询
            $where .= " and type = {$params['type']} and time_point = '{$params['time_point']}'";
        }

        // 是否选择置信度
        if ((int)$params['confidence'] >= 1) { // 选择了置信度条件
            $confidenceConf = $this->config->item('confidence');
            $where .= ' and ' . $confidenceConf[$params['confidence']]['sql_where']($params['quotaKey'] . '_confidence');
        }

        // 判断是否是综合查询
        if ($params['type'] == 1) {
            $groupBy = 'junction_id';
        }


        // 获取数据
        $data = $this->junction_model->searchDB($select, $where, 'result_array', $groupBy, $limit);
        if (empty($data)) {
            return [];
        }

        // 路口指标配置
        $quotaKeyConf = $this->config->item('junction_quota_key');

        $tempQuotaData = [];
        foreach ($data as &$v) {
            // 指标状态 1：高 2：中 3：低
            $v['quota_status'] = $quotaKeyConf[$quotaKey]['status_formula']($v[$quotaKey]);

            $v[$quotaKey] = $quotaKeyConf[$quotaKey]['round']($v[$quotaKey]);
            $tempQuotaData[$v['junction_id']]['list'][$quotaKey] = $v[$quotaKey];
            $tempQuotaData[$v['junction_id']]['list']['quota_status'] = $v['quota_status'];
        }

        // 与全城路口合并
        $resultData = $this->mergeAllJunctions($allCityJunctions, $tempQuotaData, 'quota_detail');

        return $resultData;
    }

    /**
     * 获取路口指标详情
     * @param $params['task_id']         int      任务ID
     * @param $params['junction_id']     string   逻辑路口ID
     * @param $params['dates']           array    评估/诊断日期
     * @param $params['search_type']     int      查询类型 1：按方案查询 0：按时间点查询
     * @param $params['time_point']      string   时间点 当search_type = 0 时 必传
     * @param $params['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
     * @param $params['type']            int      详情类型 1：指标详情页 2：诊断详情页
     * @param $params['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $params['timingType']      int      配时来源 1：人工 2：反推
     * @return array
     */
    public function getFlowQuotas($params)
    {
        if ((int)$params['type'] == 2) { // 诊断详情页
            $res = $this->getDiagnoseJunctionDetail($params);
        } else { // 指标详情页
            $res = $this->getQuotaJunctionDetail($params);
        }

        if (!$res) {
            return [];
        }

        return $res;
    }

    /**
     * 获取诊断列表页简易路口详情
     * @param $params['task_id']         int      任务ID
     * @param $params['junction_id']     string   逻辑路口ID
     * @param $params['dates']           array    评估/诊断日期
     * @param $params['time_point']      string   时间点
     * @param $params['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $params['diagnose_key']    array    诊断问题KEY
     * @param $params['timingType']      int      配时来源 1：人工 2：反推
     * @return array
     */
    public function getDiagnosePageSimpleJunctionDetail($params)
    {
        /*
         * 因为过饱和问题与空放问题共用一个指标，现空放问题的KEY与指标KEY相同
         * 所以可以把过饱和问题的KEY忽略
         */
        $tempDiagnoseKey = [];
        foreach ($params['diagnose_key'] as $k=>$v) {
            $tempDiagnoseKey[$k] = $v;
            if ($v == 'over_saturation') {
                $tempDiagnoseKey[$k] = 'saturation_index';
            }
        }
        array_unique($tempDiagnoseKey);
        $selectStr = $this->selectColumns($tempDiagnoseKey);
        $select = "id, junction_id, {$selectStr}, start_time, end_time, movements, time_point";

        $data = $this->junction_model->getDiagnosePageSimpleJunctionDetail($params['task_id'],
                                                                            $params['junction_id'],
                                                                            $params['time_point'],
                                                                            $select
                                                                        );
        if (!$data) {
            return [];
        }

        $dates = $params['dates'];
        $timingType = $params['timingType'];

        // 将json转为数组
        $data['movements'] = json_decode($data['movements'], true);
        if (empty($data['movements'])) {
            return [];
        }

        $resultData = [];
        // 扩展指标字段
        $resultData['extend_flow_quota']['confidence'] = '置信度';

        // 获取flow_id=>name数组
        $timingData = [
            'junction_id' => trim($data['junction_id']),
            'dates'       => $dates,
            'time_range'  => $data['start_time'] . '-' . date("H:i", strtotime($data['end_time']) - 60),
            'timingType'  => $timingType
        ];
        $flowIdName = $this->timing_model->getFlowIdToName($timingData);

        // 置信度配置
        $confidenceConf = $this->config->item('confidence');

        // flow 所有指标配置
        $flowQuotaKeyConf = $this->config->item('flow_quota_key');
        // 指标集合
        foreach ($flowQuotaKeyConf as $k => $v) {
            $resultData['flow_quota'][$k]['name'] = $flowQuotaKeyConf[$k]['name'];
            $resultData['flow_quota'][$k]['unit'] = $flowQuotaKeyConf[$k]['unit'];
        }

        $tempArr = array_merge($flowQuotaKeyConf, ['movement_id'=>'', 'confidence'=>'', 'comment'=>'']);
        foreach ($data['movements'] as $k=>$v) {
            $v['comment'] = $flowIdName[$v['movement_id']] ?? '';
            $v['confidence'] = $confidenceConf[$v['confidence']]['name'];
            foreach ($flowQuotaKeyConf as $kk=>$vv) {
                if (isset($v[$kk])) {
                    $v[$kk] = $vv['round']($v[$kk]);
                }
            }
            $resultData['notmal_movements'][$k] = array_intersect_key($v, $tempArr);
        }

        // 诊断问题配置
        $diagnoseConf = $this->config->item('diagnose_key');

        /*********************************************
            循环诊断问题配置
            判断此路口有哪个问题
            匹配movement中文名称
            匹配置信度中文名称
            匹配此路口有问题的movement并放入此问题集合中
        *********************************************/
        foreach ($diagnoseConf as $k=>$v) {
            /*
             * 因为过饱和问题与空放问题同用一个指标，现定义空放问题的KEY与指标相同
             * 所以当问题是过饱和时，需要进行问题KEY与指标保持一致处理
             */
            $diagnoseKey = $k;
            if ($k == 'over_saturation') {
                $diagnoseKey = 'saturation_index';
            }
            if (!isset($data[$diagnoseKey])) {
                continue;
            }
            if ($v['junction_diagnose_formula']($data[$diagnoseKey])) {
                // 问题名称
                $resultData['diagnose_detail'][$k]['name'] = $v['name'];

                // 组织有此问题的movement集合
                $resultData['diagnose_detail'][$k]['movements'] = [];

                foreach ($data['movements'] as $kk=>$vv) {
                    // 问题对应的指标
                    $diagnoseQuota = $v['flow_diagnose']['quota'];
                    if ($v['flow_diagnose']['formula']($vv[$diagnoseQuota])) {
                        // movement_id
                        $resultData['diagnose_detail'][$k]['movements'][$kk]['movement_id']
                        = $vv['movement_id'];
                        // movement中文名称-相位名称
                        $resultData['diagnose_detail'][$k]['movements'][$kk]['comment']
                        = $flowIdName[$vv['movement_id']];
                        // 此问题对应指标值
                        $resultData['diagnose_detail'][$k]['movements'][$kk][$diagnoseQuota]
                        = $flowQuotaKeyConf[$diagnoseQuota]['round']($vv[$diagnoseQuota]);
                        // 置信度
                        $resultData['diagnose_detail'][$k]['movements'][$kk]['confidence']
                        = $confidenceConf[$vv['confidence']]['name'];
                    }
                }

                if (!empty($resultData['diagnose_detail'][$k]['movements'])) {
                    $resultData['diagnose_detail'][$k]['movements']
                    = array_values($resultData['diagnose_detail'][$k]['movements']);
                }
            }
        }

        return $resultData;
    }

    /**
     * 获取路口问题趋势图
     * 路口无问题属于正常状态时，返回路口级指标平均延误的趋势图
     * @param $params['task_id']         int      任务ID
     * @param $params['junction_id']     string   逻辑路口ID
     * @param $params['time_point']      string   时间点
     * @param $params['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $params['diagnose_key']    array    诊断问题KEY
     * @return array
     */
    public function getJunctionQuestionTrend($params)
    {
        if (!empty($params['diagnose_key'])) {
            /*
             * 因为过饱和问题与空放问题共用一个指标，现空放问题的KEY与指标KEY相同
             * 所以可以把过饱和问题的KEY忽略
             */
            $tempDiagnoseKey = [];
            foreach ($params['diagnose_key'] as $k=>$v) {
                $tempDiagnoseKey[$k] = $v;
                if ($v == 'over_saturation') {
                    $tempDiagnoseKey[$k] = 'saturation_index';
                }
            }
            array_unique($tempDiagnoseKey);

            $selectStr = $this->selectColumns($tempDiagnoseKey);
            $select = "id, junction_id, {$selectStr}, stop_delay, time_point";
        } else {
            $select = "id, junction_id, stop_delay, time_point";
        }

        $data = $this->junction_model->getJunctionQuestionTrend($params['task_id'], $params['junction_id'], $select);
        if (!$data) {
            return [];
        }

        // 正常路口返回路口级指标平均延误的趋势图
        $normalQuota = 'stop_delay';

        // 任务开始、结束时间
        $taskTimeRange = array_filter(explode('-', $params['task_time_range']));
        $taskStartTime = strtotime($taskTimeRange[0]);
        $taskEndTime = strtotime($taskTimeRange[1]);

        // 使任务时间连续 时间间隔15分钟
        for ($i = $taskStartTime; $i < $taskEndTime; $i += 15 * 60) {
            $tempData[$i]['imbalance_index'] = 0;
            $tempData[$i]['spillover_index'] = 0;
            $tempData[$i]['saturation_index'] = 0;
            $tempData[$i]['stop_delay'] = 0;
            $tempData[$i]['time_point'] = date('H:i', $i);
        }
        foreach ($data as $k=>$v) {
            $tempData[strtotime($v['time_point'])] = $v;
        }
        ksort($tempData);

        // 诊断问题配置
        $diagnoseConf = $this->config->item('diagnose_key');
        // 路口级指标配置
        $junctionQuotaKeyConf = $this->config->item('junction_quota_key');

        $newData = [];

        $diagnose = $params['diagnose_key'];
        if (!empty($diagnose)) { // 有问题的路口
            foreach ($diagnoseConf as $k=>$v) {
                if (!in_array($k, $diagnose, true)) {
                    continue;
                }

                /*
                 * 因为过饱和问题与空放问题同用一个指标，现定义空放问题的KEY与指标相同
                 * 所以当问题是过饱和时，需要进行问题KEY与指标保持一致处理
                 */
                $diagnoseKey = $k;
                if ($k == 'over_saturation') {
                    $diagnoseKey = 'saturation_index';
                }

                $newData[$k]['info']['name'] = $v['name'];
                $newData[$k]['info']['quota_name'] = $junctionQuotaKeyConf[$diagnoseKey]['name'];
                // 此问题持续开始时间
                $continuouStart = strtotime($params['time_point']);
                // 此问题持续结束时间
                $continuouEnd = strtotime($params['time_point']);
                // 时间刻度15分钟
                $scale = 15 * 60;
                $isBeforQuestion = true;
                $isAfterQuestion = true;

                while ($isBeforQuestion) {
                    $beforTime = $continuouStart - $scale;
                    if (empty($tempData[$beforTime])) {
                        $isBeforQuestion = false;
                    } else {
                        if ($v['junction_diagnose_formula']($tempData[$beforTime][$diagnoseKey])) {
                            $continuouStart = $beforTime;
                        } else {
                            $isBeforQuestion = false;
                        }
                    }
                }

                while ($isAfterQuestion) {
                    $afterTime = $continuouEnd + $scale;
                    if (empty($tempData[$afterTime])) {
                        $isAfterQuestion = false;
                    } else {
                        if ($v['junction_diagnose_formula']($tempData[$afterTime][$diagnoseKey])) {
                            $continuouEnd = $afterTime;
                        } else {
                            $isAfterQuestion = false;
                        }
                    }
                }

                $newData[$k]['info']['continuous_start'] = date('H:i',$continuouStart);
                $newData[$k]['info']['continuous_end'] = date('H:i', $continuouEnd);

                foreach ($tempData as $kk=>$vv) {
                    $newData[$k]['list'][$kk]['value'] = $junctionQuotaKeyConf[$diagnoseKey]['round']($vv[$diagnoseKey]);
                    $newData[$k]['list'][$kk]['time'] = $vv['time_point'];
                }
                if (!empty($newData[$k]['list'])) {
                    $newData[$k]['list'] = array_values($newData[$k]['list']);
                }
            }
        } else { // 正常路口，返回路口级指标 平均延误 的趋势图
            $newData[$normalQuota]['info']['name'] = $junctionQuotaKeyConf[$normalQuota]['name'];
            $newData[$normalQuota]['info']['quota_name'] = $junctionQuotaKeyConf[$normalQuota]['name'];
            $newData[$normalQuota]['info']['continuous_start'] = '00:00';
            $newData[$normalQuota]['info']['continuous_end'] = '00:00';
            foreach ($tempData as $k=>$v) {
                $newData[$normalQuota]['list'][$k]['value']
                = $junctionQuotaKeyConf[$normalQuota]['round']($v[$normalQuota]);
                $newData[$normalQuota]['list'][$k]['time'] = $v['time_point'];
            }
            if (!empty($newData[$normalQuota]['list'])) {
                $newData[$normalQuota]['list'] = array_values($newData[$normalQuota]['list']);
            }
        }

        return $newData;
    }

    /**
     * 获取路口配时信息
     * @param $params['junction_id'] string   逻辑路口ID
     * @param $params['dates']       array    评估/诊断日期
     * @param $params['time_range']  string   时间段 00:00-00:30
     * @param $params['timingType']  int      配时数据源 0，全部；1，人工；2，配时反推；3，信号机上报
     * @return array
     */
    public function getJunctionsTimingInfo($params)
    {
        // 获取配时数据
        $data = $this->timing_model->getTimingData($params);
        if (!$data) {
            return [];
        }

        if (empty($data['latest_plan']['time_plan']) || $data['total_plan'] < 1) {
            return [];
        }

        $task_time_range = explode('-', $params['time_range']);
        // 任务开始时间
        $task_start_time = $task_time_range[0];
        // 任务结束时间
        $task_end_time = $task_time_range[1];

        $result = [];
        // 方案总数
        $result['total_plan'] = $data['total_plan'];

        foreach ($data['latest_plan']['time_plan'] as $k=>$v) {
            // 方案列表
            $tod_start_time = $v['tod_start_time'];
            if (strtotime($task_start_time) > strtotime($tod_start_time)) {
                $tod_start_time = date("H:i:s", strtotime($task_start_time));
            }
            $tod_end_time = $v['tod_end_time'];
            if (strtotime($tod_end_time) > strtotime($task_end_time)) {
                $tod_end_time = date("H:i:s", strtotime($task_end_time));
            }
            $result['plan_list'][strtotime($tod_start_time)]['id'] = $v['time_plan_id'];
            $result['plan_list'][strtotime($tod_start_time)]['start_time'] = $tod_start_time;
            $result['plan_list'][strtotime($tod_start_time)]['end_time'] = $tod_end_time;

            // 每个方案对应的详情配时详情
            if (isset($v['plan_detail']['extra_timing']['cycle'])
                && isset($v['plan_detail']['extra_timing']['offset'])
            ) {
                $result['timing_detail'][$v['time_plan_id']]['cycle'] = $v['plan_detail']['extra_timing']['cycle'];
                $result['timing_detail'][$v['time_plan_id']]['offset'] = $v['plan_detail']['extra_timing']['offset'];
            }

            if (!empty($v['plan_detail']['movement_timing'])) {
                foreach ($v['plan_detail']['movement_timing'] as $k1=>$v1) {
                    foreach ($v1 as $key=>$val) {
                        // 信号灯状态 1=绿灯
                        $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['state']
                            = isset($val['state']) ? $val['state'] : 0;
                        // 绿灯开始时间
                        $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['start_time']
                            = isset($val['start_time']) ? $val['start_time'] : 0;
                        // 绿灯持续时间
                        $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['duration']
                            = isset($val['duration']) ? $val['duration'] : 0;
                        // 绿灯结束时间
                        $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['end_time']
                            = $val['start_time'] + $val['duration'];
                        // 逻辑flow id
                        $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['logic_flow_id']
                            = isset($val['flow_logic']['logic_flow_id']) ? $val['flow_logic']['logic_flow_id'] : 0;
                        // flow 描述
                        $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['comment']
                            = isset($val['flow_logic']['comment']) ? $val['flow_logic']['comment'] : '';
                    }
                }
            }

            if (!empty($result['timing_detail'][$v['time_plan_id']]['timing'])) {
                $result['timing_detail'][$v['time_plan_id']]['timing']
                    = array_values($result['timing_detail'][$v['time_plan_id']]['timing']);
            }
        }

        // 对方案按时间正序排序
        if (!empty($result['plan_list'])) {
            ksort($result['plan_list']);
            $result['plan_list'] = array_values($result['plan_list']);
        }

        return $result;
    }

    /**
     * 获取全城路口诊断问题列表
     * @param $data['task_id']      int      任务ID
     * @param $data['city_id']      int      城市ID
     * @param $data['time_point']   string   时间点
     * @param $data['type']         int      计算类型
     * @param $data['confidence']   int      置信度
     * @param $data['diagnose_key'] array    诊断问题KEY
     * @return array
     */
    public function getJunctionsDiagnoseList($data)
    {
        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($data['city_id']);
        if (!$allCityJunctions) {
            throw new \Exception('没有获取到全城路口！', ERR_REQUEST_WAYMAP_API);
        }

        if ($data['type'] == 1) { // 综合
            $res = $this->getJunctionsDiagnoseBySynthesize($data);
        } else { // 时间点
            $res = $this->getJunctionsDiagnoseByTimePoint($data);
        }

        if (empty($res)) {
            return [];
        }

        // 获取此任务路口总数
        $where = 'task_id = ' . $data['task_id'] . ' and type = 0';
        $junctionTotal = 0;
        $allJunction = $this->db->select('count(DISTINCT junction_id) as count')
                                    ->from($this->tb)
                                    ->where($where)
                                    ->get()
                                    ->row_array();
        $junctionTotal = $allJunction['count'];

        $diagnoseKeyConf = $this->config->item('diagnose_key');
        $junctionQuotaKeyConf = $this->config->item('junction_quota_key');
        $tempDiagnoseData = [];

        // 定义平均延误
        $countStopDelay = 0;
        // 定义平均速度
        $countAvgSpeed = 0;
        foreach ($data['diagnose_key'] as $val) {
            /*
             * 因为过饱和问题与空放问题同用一个指标，现定义空放问题的KEY与指标相同
             * 所以当问题是过饱和时，需要进行问题KEY与指标保持一致处理
             */
            $diagnose = $val;
            if ($val == 'over_saturation') {
                $diagnose = 'saturation_index';
            }

            // 统计每种问题的路口总数
            $tempDiagnoseData['count'][$val] = 0;

            // 循环任务结果数据，进行问题判断及统计等
            foreach ($res as $k=>$v) {
                // 统计停车(平均)延误总数
                $countStopDelay += $v['stop_delay'];
                // 统计平均速度总数
                $countAvgSpeed += $v['avg_speed'];

                /*
                 * hover:路口平均延误
                 */
                // 格式化指标数据
                $tempDiagnoseData[$v['junction_id']]['info']['quota']['stop_delay']['value']
                = $junctionQuotaKeyConf['stop_delay']['round']($v['stop_delay']);
                // 指标名称
                $tempDiagnoseData[$v['junction_id']]['info']['quota']['stop_delay']['name']
                = $junctionQuotaKeyConf['stop_delay']['name'];
                // 指标单位
                $tempDiagnoseData[$v['junction_id']]['info']['quota']['stop_delay']['unit']
                = $junctionQuotaKeyConf['stop_delay']['unit'];

                // 对问题对应的指标数据进行格式化
                $tempDiagnoseData[$v['junction_id']]['list'][$val]
                = $junctionQuotaKeyConf[$diagnose]['round']($v[$diagnose]);

                // 路口是否有问题标记
                $isDiagnose = 0;
                if ($diagnoseKeyConf[$val]['junction_diagnose_formula']($v[$diagnose])) {
                    $isDiagnose = 1;
                    // 统计有此问题的路口数
                    $tempDiagnoseData['count'][$val] += 1;
                    // hover:路口存在的问题名称
                    $tempDiagnoseData[$v['junction_id']]['info']['question'][$val]
                    = $diagnoseKeyConf[$val]['name'];
                }

                $tempDiagnoseData[$v['junction_id']]['list'][$val . '_diagnose'] = $isDiagnose;
            }
        }
        // 统计所有问题总数
        $tempDiagnoseData['diagnose_count'] = 0;
        if (!empty($tempDiagnoseData['count'])) {
            foreach ($tempDiagnoseData['count'] as $v) {
                $tempDiagnoseData['diagnose_count'] += $v;
            }
        }
        $tempDiagnoseData['quotaCount']['stop_delay'] = $countStopDelay;
        $tempDiagnoseData['quotaCount']['avg_speed'] = $countAvgSpeed;
        $tempDiagnoseData['junctionTotal'] = $junctionTotal;

        $result_data = $this->mergeAllJunctions($allCityJunctions, $tempDiagnoseData, 'diagnose_detail');

        return $result_data;
    }

    /**
     * 查询综合类型全城路口诊断问题列表
     * @param $data['task_id']      interger 任务ID
     * @param $data['city_id']      interger 城市ID
     * @param $data['time_point']   string   时间点
     * @param $data['type']         interger 计算类型
     * @param $data['confidence']   interger 置信度
     * @param $data['diagnose_key'] array    诊断问题KEY
     * @return array
     */
    private function getJunctionsDiagnoseBySynthesize($data)
    {
        $sql_data = array_map(function($diagnose_key) use ($data) {
            /*
             * 过饱和问题与空放问题都是基于路口级指标：饱和指数（saturation_index）算出来的
             * 所以把过饱和的KEY转为相应的指标KEY进行SQL组合
             */
            if ($diagnose_key == 'over_saturation') { // 当是过饱和问题时
                $diagnose_key = 'saturation_index';
            }

            if ($diagnose_key == 'saturation_index') {
                // 空放问题 因为空放问题是取的最小的
                $selectstr = "id, junction_id, stop_delay, avg_speed,";
                $selectstr .= " min({$diagnose_key}) as {$diagnose_key}, {$diagnose_key}_confidence";
            } else {
                $selectstr = "id, junction_id, stop_delay, avg_speed,";
                $selectstr .= " max({$diagnose_key}) as {$diagnose_key}, {$diagnose_key}_confidence";
            }

            $where = 'task_id = ' . $data['task_id'] . ' and type = 1';
            $temp_data = $this->db->select($selectstr)
                                ->from($this->tb)
                                ->where($where)
                                ->group_by('junction_id')
                                ->get()->result_array();
            $new_data = [];
            if (count($temp_data) >= 1) {
                foreach ($temp_data as $value) {
                    $new_data[$value['junction_id']] = $value;
                }
            }
            return $new_data;
        }, $data['diagnose_key']);

        $count = count($data['diagnose_key']);

        $diagnose_confidence_threshold = $this->config->item('diagnose_confidence_threshold');

        $flag = [];
        if (count($sql_data) >= 1) {
            $flag = $sql_data[0];
            foreach ($flag as $k=>&$v) {
                $v = array_reduce($sql_data, function($carry, $item) use ($k) {
                    return array_merge($carry, $item[$k]);
                }, []);
                if ((int)$data['confidence'] != 0) {
                    $total = 0;
                    foreach ($data['diagnose_key'] as $key) {
                        $total += $v[$key];
                    }

                    if ($data['confidence'] == 1) { // 置信度：高 unset低的
                        if ($total / $count <= $diagnose_confidence_threshold) unset($flag[$k]);
                    } elseif ($data['confidence'] == 2) { // 置信度：低 unset高的
                        if($total / $count > $diagnose_confidence_threshold) unset($flag[$k]);
                    }
                }
            }
        }

        return $flag;
    }

    /**
     * 根据时间点查询全城路口诊断问题列表
     * @param $data['task_id']      interger 任务ID
     * @param $data['time_point']   string   时间点
     * @param $data['confidence']   interger 置信度
     * @param $data['diagnose_key'] array    诊断问题KEY
     * @return array
     */
    private function getJunctionsDiagnoseByTimePoint($data)
    {
        $diagnoseKeyConf = $this->config->item('diagnose_key');
        $selectQuotaKey = [];
        foreach ($diagnoseKeyConf as $k=>$v) {
            // 过饱和与空放问题都是根据指标饱和指数计算
            if ($k != 'over_saturation') {
                $selectQuotaKey[] = $k;
            }
        }

        $selectstr = empty($this->selectColumns($selectQuotaKey)) ? '' : ',' . $this->selectColumns($selectQuotaKey);
        $sql = 'select id, junction_id, stop_delay, avg_speed' . $selectstr . ' from ' . $this->tb;
        $sql .= " where task_id = ? and time_point = ? and type = 0";

        // 诊断问题总数
        $diagnoseKeyCount = count($data['diagnose_key']);

        $confidenceWhere = '';
        foreach ($data['diagnose_key'] as $v) {
            $diagnose = $v;
            if ($v == 'over_saturation') {
                $diagnose = 'saturation_index';
            }
            $confidenceWhere .= empty($confidenceWhere) ? $diagnose . '_confidence' : '+' . $diagnose . '_confidence';
        }
        $confidenceThreshold = $this->config->item('diagnose_confidence_threshold');

        $confidenceExpression[1] = '(' . $confidenceWhere . ') / ' . $diagnoseKeyCount . '>= ?';
        $confidenceExpression[2] = '(' . $confidenceWhere . ') / ' . $diagnoseKeyCount . '< ?';

        $confidenceConf = $this->config->item('confidence');
        $res = [];
        if ($data['confidence'] >= 1 && array_key_exists($data['confidence'], $confidenceConf)) {
            $sql .= ' and ' . $confidenceExpression[$data['confidence']];
            $res = $this->db->query($sql, [$data['task_id'], $data['time_point'], $confidenceThreshold])->result_array();
        } else {
            $res = $this->db->query($sql, [$data['task_id'], $data['time_point']])->result_array();
        }
        if (!$res) {
            return [];
        }

        return $res;
    }

    /**
     * 获取诊断详情页数据
     * @param $data['task_id']         int      任务ID
     * @param $data['junction_id']     string   逻辑路口ID
     * @param $data['dates']           array    评估/诊断日期
     * @param $data['search_type']     int      查询类型 1：按方案查询 0：按时间点查询
     * @param $data['time_point']      string   时间点 当search_type = 0 时 必传
     * @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
     * @param $data['type']            int      详情类型 1：指标详情页 2：诊断详情页
     * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $data['timingType']      int      配时来源 1：人工 2：反推
     * @return array
     */
    private function getDiagnoseJunctionDetail($data)
    {
        // 诊断问题配置
        $diagnoseKeyConf = $this->config->item('diagnose_key');

        // 组织select 所需字段
        $selectQuota = '';
        foreach ($diagnoseKeyConf as $k=>$v) {
            /*
             * 因为过饱和问题与空放都是根据同一指标计算的，现空放问题的KEY与指标相同
             * 所以只不需要再拼接过饱和问题的select column
             */
            if ($k != 'over_saturation') {
                $selectQuota .= empty($selectQuota) ? $k : ',' . $k;
            }
        }
        $select = "id, junction_id, {$selectQuota}, start_time, end_time, result_comment, movements";

        $res = $this->junction_model->getDiagnoseJunctionDetail($data, $select);
        if (!$res) {
            return [];
        }

        $result = $this->formatJunctionDetailData($res, $data['dates'], 2, $data['timingType']);

        return $result;
    }

    /**
     * 获取指标详情页数据
     * @param $data['task_id']         int      任务ID
     * @param $data['junction_id']     string   逻辑路口ID
     * @param $data['dates']           array    评估/诊断日期
     * @param $data['search_type']     int      查询类型 1：按方案查询 0：按时间点查询
     * @param $data['time_point']      string   时间点 当search_type = 0 时 必传
     * @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
     * @param $data['type']            int      详情类型 1：指标详情页 2：诊断详情页
     * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $data['timingType']      int      配时来源 1：人工 2：反推
     * @return array
     */
    private function getQuotaJunctionDetail($data)
    {
        // 组织select colum
        $select = 'id, junction_id, start_time, end_time, result_comment, movements';

        // 组织where条件
        $where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

        if ((int)$data['search_type'] == 0) {
            $select .= ', time_point';
        }

        $res = $this->junction_model->getQuotaJunctionDetail($data, $select);
        if (!$res) {
            return [];
        }

        $result = $this->formatJunctionDetailData($res, $data['dates'], 1, $data['timingType']);

        return $result;

    }

    /**
     * 格式化路口详情数据
     * @param $data        路口详情数据
     * @param $dates       评估/诊断日期
     * @param $resultType  数据返回类型 1：指标详情页 2：诊断详情页
     * @param $timingType  配时数据来源 1：人工 2：反推
     * @return array
     */
    private function formatJunctionDetailData($data, $dates, $resultType, $timingType)
    {
        if (empty($data) || empty($dates) || (int)$resultType < 1) {
            return [];
        }

        // 因为详情页地图下方列表所有相位都有 置信度字段，而置信度不属于指标，固将此放到扩展指标集合中
        $data['extend_flow_quota']['confidence'] = '置信度';

        // movement级指标数据在数据表中以json格式的存储，需要json_decode
        $data['movements'] = json_decode($data['movements'], true);
        if (empty($data['movements'])) {
            return [];
        }

        // 获取flow_id=>name数组
        $timing_data = [
            'junction_id' => trim($data['junction_id']),
            'dates'       => $dates,
            'time_range'  => $data['start_time'] . '-' . date("H:i", strtotime($data['end_time']) - 60),
            'timingType'  => $timingType
        ];
        $flowIdName = $this->timing_model->getFlowIdToName($timing_data);

        // flow级指标配置
        $flowQuotaKeyConf = $this->config->item('flow_quota_key');

        // 需要返回的全部movements所需字段
        $movementsAll = array_merge($flowQuotaKeyConf, ['movement_id'=>'', 'comment'=>'', 'confidence'=>'']);

        // 置信度配置
        $confidenceConf = $this->config->item('confidence');

        // 匹配相位名称 并按 南左、北直、西左、东直、北左、南直、东左、西直 进行排序(NEMA排序)
        $phase = [
            '南左' => 10,
            '北直' => 20,
            '西左' => 30,
            '东直' => 40,
            '北左' => 50,
            '南直' => 60,
            '东左' => 70,
            '西直' => 80
        ];

        $tempMovements = [];
        foreach ($data['movements'] as $k=>&$v) {
            // 相位名称
            $v['comment'] = $flowIdName[$v['movement_id']] ?? '';

            // 加这个判断是旧的任务结果数据中没有此字段
            if (isset($v['confidence'])) {
                $v['confidence'] = $confidenceConf[$v['confidence']]['name'] ?? '';
            } else {
                $v['confidence'] = '';
            }

            // 组织flow级指标对应相位集合及格式化指标数据
            foreach ($flowQuotaKeyConf as $key=>$val) {
                // 指标名称
                $data['flow_quota_all'][$key]['name'] = $val['name'];
                // 指标单位
                $data['flow_quota_all'][$key]['unit'] = $val['unit'];
                $data['flow_quota_all'][$key]['movements'][$k]['id'] = $v['movement_id'];
                if (isset($v[$key])) {
                    $v[$key] = $val['round']($v[$key]);
                    $data['flow_quota_all'][$key]['movements'][$k]['value'] = $val['round']($v[$key]);
                }
            }
            if (array_key_exists(trim($v['comment']), $phase)
                && !array_key_exists($phase[trim($v['comment'])], $tempMovements)
            ) {
                $tempMovements[$phase[trim($v['comment'])]] = array_intersect_key($v, $movementsAll);
            } else {
                $tempMovements[mt_rand(100, 900) + mt_rand(1, 99)] = array_intersect_key($v, $movementsAll);
            }
        }
        // 因为foreach 使用了引用&$v，所以foreach完成后要销毁$v
        unset($v);

        if (!empty($tempMovements)) {
            unset($data['movements']);
            ksort($tempMovements);
            $data['movements'] = array_values($tempMovements);
        }

        if ($resultType == 2) { // 诊断详情页
            // 组织问题集合
            $diagnoseKeyConf = $this->config->item('diagnose_key');
            foreach ($diagnoseKeyConf as $k=>$v) {
                /*
                 * 因为过饱和问题与空放问题同用一个指标进行计算
                 * 所以过饱和问题要单独处理一下
                 */
                $diagnose = $k;
                if ($k == 'over_saturation') {
                    $diagnose = 'saturation_index';
                }

                // 判断路口的问题
                if ($v['junction_diagnose_formula']($data[$diagnose])) {
                    $data['diagnose_detail'][$k]['name'] = $v['name'];
                    $data['diagnose_detail'][$k]['key'] = $k;

                    // 计算性质程度
                    $data['diagnose_detail'][$k]['nature'] = $v['nature_formula']($data[$diagnose]);

                    // 匹配每个问题指标
                    $tempArr = ['movement_id'=>'logic_flow_id', 'comment'=>'name', 'confidence'=>'置信度'];
                    $tempMerge = array_merge($v['flow_quota'], $tempArr);
                    foreach ($data['movements'] as $kk=>$vv) {
                        $data['diagnose_detail'][$k]['movements'][$kk] = array_intersect_key($vv, $tempMerge);
                        foreach ($v['flow_quota'] as $key=>$val) {
                            if (isset($vv[$key])) {
                                $data['diagnose_detail'][$k]['flow_quota'][$key]['name'] = $val['name'];
                                $data['diagnose_detail'][$k]['flow_quota'][$key]['unit'] = $val['unit'];
                                $data['diagnose_detail'][$k]['flow_quota'][$key]['movements'][$kk]['id']
                                = $vv['movement_id'];
                                $data['diagnose_detail'][$k]['flow_quota'][$key]['movements'][$kk]['value']
                                = $flowQuotaKeyConf[$key]['round']($vv[$key]);
                            }
                        }
                    }
                }
            }
        }

        $resultCommentConf = $this->config->item('result_comment');
        $data['result_comment'] = $resultCommentConf[$data['result_comment']] ?? '';
        return $data;
    }

    /**
     * 将查询出来的评估/诊断数据合并到全城路口模板中
     * @param $allData  全城路口
     * @param $data     任务结果路口
     * @param $mergeKey 合并KEY
     * @return array
     */
    private function mergeAllJunctions($allData, $data, $mergeKey = 'detail')
    {
        if (!is_array($allData) || count($allData) < 1 || !is_array($data) || count($data) < 1) {
            return [];
        }

        // 返回数据
        $resultData = [];
        // 经度
        $countLng = 0;
        // 纬度
        $countLat = 0;

        // 循环全城路口
        foreach ($allData as $k=>$v) {
            // 路口存在于任务结果数据中
            if (isset($data[$v['logic_junction_id']])) {
                // 经纬度相加 用于最后计算中心经纬度用
                $countLng += $v['lng'];
                $countLat += $v['lat'];

                // 组织返回结构 路口ID 路口名称 路口经纬度 路口信息
                $resultData['dataList'][$k]['logic_junction_id'] = $v['logic_junction_id'];
                $resultData['dataList'][$k]['name'] = $v['name'];
                $resultData['dataList'][$k]['lng'] = $v['lng'];
                $resultData['dataList'][$k]['lat'] = $v['lat'];
                // 路口问题信息集合
                $resultData['dataList'][$k][$mergeKey] = $data[$v['logic_junction_id']]['list'];

                // 去除quota的key
                if (isset($data[$v['logic_junction_id']]['info'])) {
                    if (isset($data[$v['logic_junction_id']]['info']['quota'])) {
                        $data[$v['logic_junction_id']]['info']['quota']
                            = array_values($data[$v['logic_junction_id']]['info']['quota']);
                    } else {
                        $data[$v['logic_junction_id']]['info']['quota'] = [];
                    }
                    // 去除question的key并设置默认值
                    if (isset($data[$v['logic_junction_id']]['info']['question'])) {
                        $data[$v['logic_junction_id']]['info']['question']
                            = array_values($data[$v['logic_junction_id']]['info']['question']);
                    } else {
                        $data[$v['logic_junction_id']]['info']['question'] = ['无'];
                    }

                    $resultData['dataList'][$k]['info'] = $data[$v['logic_junction_id']]['info'];
                }
            }
        }

        // 任务结果路口总数
        $count = !empty($data['junctionTotal']) ? $data['junctionTotal'] : 0;

        // 全城路口总数
        $qcount = 0;

        if (!empty($resultData['dataList'])) {
            // 统计全城路口总数
            $qcount = count($resultData['dataList']);
            // 去除KEY
            $resultData['dataList'] = array_values($resultData['dataList']);
        }

        if ($count >= 1 || $qcount >= 1) {
            $diagnoseKeyConf = $this->config->item('diagnose_key');
            $junctionQuotaKeyConf = $this->config->item('junction_quota_key');

            // 统计指标（平均延误、平均速度）平均值
            if (isset($data['quotaCount'])) {
                foreach ($data['quotaCount'] as $k=>$v) {
                    $resultData['quotaCount'][$k]['name'] = $junctionQuotaKeyConf[$k]['name'];
                    $resultData['quotaCount'][$k]['value'] = round(($v / $count), 2);
                    $resultData['quotaCount'][$k]['unit'] = $junctionQuotaKeyConf[$k]['unit'];
                }
            }

            // 计算地图中心坐标
            $centerLng = round($countLng / $qcount, 6);
            $centerLat = round($countLat / $qcount, 6);

            // 柱状图
            if (!empty($data['count']) && $count >= 1) {
                foreach ($data['count'] as $k=>$v) {
                    // 此问题的路口个数
                    $resultData['count'][$k]['num'] = $v;
                    // 问题中文名称
                    $resultData['count'][$k]['name'] = $diagnoseKeyConf[$k]['name'];
                    // 此问题占所有问题的百分比
                    $percent = round(($v / $count) * 100, 2);
                    $resultData['count'][$k]['percent'] = $percent . '%';
                    // 对应不占百分比
                    $resultData['count'][$k]['other'] = (100 - $percent) . '%';
                }
            }
        }

        // 去除quotaCount的key
        if (isset($resultData['quotaCount'])) {
            $resultData['quotaCount'] = array_values($resultData['quotaCount']);
        }

        $resultData['junctionTotal'] = intval($count);

        // 中心坐标
        $resultData['center']['lng'] = $centerLng;
        $resultData['center']['lat'] = $centerLat;

        return $resultData;
    }

    /**
     * 组织select 字段
     */
    private function selectColumns($key)
    {
        $select = '';
        if (is_string($key)) { // 评估，单选
            if (array_key_exists($key, $this->config->item('junction_quota_key'))) {
                $select = $key;
            }
        }
        if (is_array($key)) { // 诊断问题， 多选
            $diagnoseKeyConf = $this->config->item('diagnose_key');
            foreach ($key as $v) {
                if (array_key_exists($v, $diagnoseKeyConf)) {
                    $select .= empty($select) ? $v : ',' . $v;
                }
            }
        }

        return $select;
    }
}