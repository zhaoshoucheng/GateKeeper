<?php
/********************************************
# desc:    路口数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-03-05
********************************************/

class Junction_model extends CI_Model
{
    private $tb = 'junction_index';
    private $db = '';
    private $email_to = 'ningxiangbing@didichuxing.com';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            // 添加日志
            return [];
        }

        $this->load->config('nconf');
        $this->load->model('waymap_model');
        $this->load->model('timing_model');
    }

    /**
    * 获取全城路口信息
    * @param data['task_id']    interger 任务ID
    * @param data['type']       interger 计算指数类型 1：统合 0：时间点
    * @param data['city_id']    interger 城市ID
    * @param data['time_point'] string   评估时间点 指标计算类型为1时非空
    * @param data['confidence'] interger 置信度
    * @param data['quota_key']  string   指标key
    * @return array
    */
    public function getAllCityJunctionInfo($data)
    {
        $quotaKey = $data['quota_key'];

        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($data['city_id']);
        if (count($allCityJunctions) < 1 || !$allCityJunctions) {
            return [];
        }

        $selectstr = empty($this->selectColumns($quotaKey)) ? '' : ',' . $this->selectColumns($quotaKey);
        if (empty($selectstr)) {
            return [];
        }

        $select = '';
        if ($data['type'] == 1) { // 综合
            $select = "id, junction_id, max({$quotaKey}) as {$quotaKey}";
        } else {
            $select = 'id, junction_id' . $selectstr;
        }

        $where = 'task_id = ' . $data['task_id'];
        if ($data['type'] == 0) {
            $where .= " and type = {$data['type']} and time_point = '{$data['time_point']}'";
        }

        $confidenceConf = $this->config->item('confidence');
        if (isset($data['confidence'])
            && (int)$data['confidence'] >= 1
            && array_key_exists($data['confidence'], $confidenceConf))
        {
            $where .= ' and ' . $confidenceConf[$data['confidence']]['sql_where']($quotaKey . '_confidence');
        }

        $this->db->select($select);
        $this->db->from($this->tb);
        $this->db->where($where);
        if ($data['type'] == 1) {
            $this->db->group_by('junction_id');
        }

        $res = $this->db->get()->result_array();

        $quotaKeyConf = $this->config->item('junction_quota_key');
        $tempQuotaData = [];
        foreach ($res as &$v) {
            // 指标状态 1：高 2：中 3：低
            $v['quota_status'] = $quotaKeyConf[$quotaKey]['status_formula']($v[$quotaKey]);

            $v[$quotaKey] = $quotaKeyConf[$quotaKey]['round']($v[$quotaKey]);
            $tempQuotaData[$v['junction_id']]['list'][$quotaKey] = $v[$quotaKey];
            $tempQuotaData[$v['junction_id']]['list']['quota_status'] = $v['quota_status'];
        }

        $resultData = $this->mergeAllJunctions($allCityJunctions, $tempQuotaData, 'quota_detail');

        return $resultData;
    }

    /**
    * 获取路口指标详情
    * @param $data['task_id']         interger 任务ID
    * @param $data['junction_id']     string   逻辑路口ID
    * @param $data['dates']           array    评估/诊断日期
    * @param $data['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
    * @param $data['time_point']      string   时间点 当search_type = 0 时 必传
    * @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
    * @param $data['type']            interger 详情类型 1：指标详情页 2：诊断详情页
    * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
    * @param $data['timingType']      interger 配时来源 1：人工 2：反推
    * @return array
    */
    public function getFlowQuotas($data)
    {
        if (!isset($data['type']) || empty($data) || !in_array((int)$data['type'], [1, 2], true)) {
            return [];
        }

        if ((int)$data['type'] == 2) { // 诊断详情页
            $res = $this->getDiagnoseJunctionDetail($data);
        } else { // 指标详情页
            $res = $this->getQuotaJunctionDetail($data);
        }

        if (!$res || empty($res)) {
            return [];
        }

        return $res;
    }

    /**
    * 获取全城路口诊断问题列表
    * @param data['task_id']      interger 任务ID
    * @param data['city_id']      interger 城市ID
    * @param data['time_point']   string   时间点
    * @param data['type']         interger 计算类型
    * @param data['confidence']   interger 置信度
    * @param data['diagnose_key'] array    诊断问题KEY
    * @return array
    */
    public function getJunctionsDiagnoseList($data)
    {
        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($data['city_id']);
        if (count($allCityJunctions) < 1 || !$allCityJunctions) {
            return [];
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
    * @param data['task_id']      interger 任务ID
    * @param data['city_id']      interger 城市ID
    * @param data['time_point']   string   时间点
    * @param data['type']         interger 计算类型
    * @param data['confidence']   interger 置信度
    * @param data['diagnose_key'] array    诊断问题KEY
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
    * @param data['task_id']      interger 任务ID
    * @param data['time_point']   string   时间点
    * @param data['confidence']   interger 置信度
    * @param data['diagnose_key'] array    诊断问题KEY
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
        $select = 'id, junction_id, stop_delay, avg_speed' . $selectstr;

        $where = "task_id = " . $data['task_id'] . " and type = 0 and time_point = '{$data['time_point']}'";

        // 诊断问题总数
        $diagnoseKeyCount = count($data['diagnose_key']);

        $confidenceWhere = '';
        foreach ($data['diagnose_key'] as $v) {
            $confidenceWhere .= empty($confidenceWhere) ? $v . '_confidence' : '+' . $v . '_confidence';
        }
        $confidenceThreshold = $this->config->item('diagnose_confidence_threshold');

        $confidenceExpression[1] = '(' . $confidenceWhere . ') / ' . $diagnoseKeyCount . '>=' . $confidenceThreshold;
        $confidenceExpression[2] = '(' . $confidenceWhere . ') / ' . $diagnoseKeyCount . '<' . $confidenceThreshold;

        $confidenceConf = $this->config->item('confidence');
        if ($data['confidence'] >= 1 && array_key_exists($data['confidence'], $confidenceConf)) {
            $where .= ' and ' . $confidenceExpression[$data['confidence']];
        }
        $res = [];
        $res = $this->db->select($select)
                        ->from($this->tb)
                        ->where($where)
                        ->get()
                        ->result_array();
        if (!$res) {
            return [];
        }

        return $res;
    }

    /**
    * 获取问题趋势
    * @param $data['task_id']    interger Y 任务ID
    * @param $data['confidence'] interger Y 置信度
    * @return array
    */
    public function getQuestionTrend($data)
    {
        if (empty($data)) {
            return [];
        }

        $diagnoseKeyConf = $this->config->item('diagnose_key');

        $where = 'task_id = ' . $data['task_id'] . ' and type = 0';

        // 获取此任务路口总数
        $junctionTotal = 0;
        $allJunction = $this->db->select('count(DISTINCT junction_id) as count')
                                    ->from($this->tb)
                                    ->where($where)
                                    ->get()
                                    ->row_array();
        $junctionTotal = $allJunction['count'];

        // 置信度
        $confidenceThreshold = $this->config->item('confidence');

        // 循环获取每种问题各时间点路口总数
        foreach ($diagnoseKeyConf as $k=>$v) {
            /*
             * 因为过饱和问题与空放问题同用一个指标，现定义空放问题的KEY与指标相同
             * 所以当问题是过饱和时，需要进行问题KEY与指标保持一致处理
             */
            $diagnose = $k;
            if ($diagnose == 'over_saturation') {
                $diagnose = 'saturation_index';
            }
            $nWhere = $where . ' and ' . $v['sql_where']();
            if ($data['confidence'] >= 1) {
                $nWhere .= ' and ' . $confidenceThreshold[$data['confidence']]['sql_where']($diagnose . '_confidence');
            }
            $res[$k] = $this->db->select("count(id) as num , time_point as hour")
                                ->from($this->tb)
                                ->where($nWhere)
                                ->group_by('time_point')
                                ->get()
                                ->result_array();
        }

        $result = [];
        // X轴时间0-24点时间点，15分钟为一刻度 设置这个是因为可能会有某个时间是没有问题的，导致时间不连续
        $timeRange = [];
        $start = strtotime('00:00');
        $end = strtotime('24:00');
        for ($i = $start; $i < $end; $i += 15 * 60) {
            $timeRange[] = date('H:i', $i);
        }
        if (empty($res) || !is_array($res)) {
            return [];
        }

        foreach ($res as $k=>$v) {
            foreach ($timeRange as $hour) {
                $result[$k]['name'] = $diagnoseKeyConf[$k]['name'];
                $result[$k]['list'][$hour]['hour'] = $hour;
                $result[$k]['list'][$hour]['num'] = 0;
                $result[$k]['list'][$hour]['percent'] = 0 . '%';
                foreach ($v as $kk=>$vv) {
                    if ($vv['hour'] == $hour) {
                        $result[$k]['list'][$hour]['hour'] = $vv['hour'];
                        $result[$k]['list'][$hour]['num'] = $vv['num'];
                        $result[$k]['list'][$hour]['percent'] = round(($vv['num'] / $junctionTotal) * 100, 2) . '%';
                    }
                }
            }
        }

        return $result;

    }

    /**
    * 诊断-诊断问题排序列表
    * @param data['task_id']      interger 任务ID
    * @param data['time_point']   string   时间点
    * @param data['diagnose_key'] array    诊断问题KEY
    * @param data['confidence']   interger 置信度
    * @param data['orderby']      interger 诊断问题排序 1：按指标值正序 2：按指标值倒序 默认2
    * @return array
    */
    public function getDiagnoseRankList($data)
    {
        if (empty($data['diagnose_key'])) {
            return [];
        }

        // PM规定页面左侧列表与右侧地图数据一致，而且只在概览页有此列表，固使用 根据时间点查询全城路口诊断问题列表 接口获取初始数据
        $res = $this->getJunctionsDiagnoseByTimePoint($data);
        if (!$res || empty($res)) {
            return [];
        }

        $diagnoseKeyConf = $this->config->item('diagnose_key');
        $junctionQuotaKeyConf = $this->config->item('junction_quota_key');

        // 按诊断问题组织数组 且 获取路口ID串
        $result = [];
        // 路口ID串 用逗号隔开
        $logicJunctionIds = '';
        foreach ($res as $k=>$v) {
            foreach ($data['diagnose_key'] as $k1=>$v1) {
                /*
                 * 因为过饱和问题与空放问题同用一个指标，现定义空放问题的KEY与指标相同
                 * 所以当问题是过饱和时，需要进行问题KEY与指标保持一致处理
                 */
                $diagnose = $v1;
                if ($v1 == 'over_saturation') {
                    $diagnose = 'saturation_index';
                }
                // 列表只展示有问题的路口 组织新数据 junction_id=>指标值 因为排序方便
                if ($diagnoseKeyConf[$v1]['junction_diagnose_formula']($v[$diagnose])) {
                    $result[$v1][$v['junction_id']] = $junctionQuotaKeyConf[$diagnose]['round']($v[$diagnose]);
                }
            }
            // 组织路口ID串，用于获取路口名称
            $logicJunctionIds .= empty($logicJunctionIds) ? $v['junction_id'] : ',' . $v['junction_id'];
        }

        if (empty($result)) {
            return [];
        }

        // 排序默认 2 按指标值倒序
        if (!isset($data['orderby']) || !array_key_exists((int)$data['orderby'], $this->config->item('sort_conf'))) {
            $data['orderby'] = 2;
        }

        // 排序
        foreach ($data['diagnose_key'] as $v) {
            if (!empty($result[$v])) {
                if ((int)$data['orderby'] == 1) {
                    asort($result[$v]);
                } else {
                    arsort($result[$v]);
                }
            }
        }

        // 获取路口名称
        $junctionInfo = [];
        if (!empty($logicJunctionIds)) {
            $junctionInfo = $this->waymap_model->getJunctionInfo($logicJunctionIds);
        }

        // 组织 junction_id=>name 数组 用于匹配路口名称
        $junctionIdName = [];
        if (count($junctionInfo) >= 1) {
            $junctionIdName = array_column($junctionInfo, 'name', 'logic_junction_id');
        }

        // 组织最终返回数据结构 ['quota_key'=>['junction_id'=>'xx','junction_label'=>'xxx', 'value'=>0], ......]
        $resultData = [];
        foreach ($result as $k=>$v) {
            foreach ($v as $k1=>$v1) {
                $resultData[$k][$k1]['junction_id'] = $k1;
                $resultData[$k][$k1]['junction_label'] = $junctionIdName[$k1] ?? '';
                $resultData[$k][$k1]['value'] = $v1;
            }

            if (!empty($resultData[$k])) {
                $resultData[$k] = array_values($resultData[$k]);
            }
        }

        return $resultData;
    }

    /**
    * 获取诊断列表页简易路口详情
    * @param $data['task_id']         interger 任务ID
    * @param $data['junction_id']     string   逻辑路口ID
    * @param $data['dates']           array    评估/诊断日期
    * @param $data['time_point']      string   时间点
    * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
    * @param $data['diagnose_key']    array    诊断问题KEY
    * @param $data['timingType']      interger 配时来源 1：人工 2：反推
    * @return array
    */
    public function getDiagnosePageSimpleJunctionDetail($data)
    {
        /*
         * 因为过饱和问题与空放问题共用一个指标，现空放问题的KEY与指标KEY相同
         * 所以可以把过饱和问题的KEY忽略
         */
        $tempDiagnoseKey = [];
        foreach ($data['diagnose_key'] as $k=>$v) {
            $tempDiagnoseKey[$k] = $v;
            if ($v == 'over_saturation') {
                $tempDiagnoseKey[$k] = 'saturation_index';
            }
        }
        array_unique($tempDiagnoseKey);
        $selectStr = $this->selectColumns($tempDiagnoseKey);
        $select = "id, junction_id, {$selectStr}, start_time, end_time, movements";

        // 组织where条件
        $where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

        $select .= ', time_point';
        $where  .= ' and type = 0';
        $where  .= ' and time_point = "' . trim($data['time_point']) . '"';

        $res = $this->db->select($select)
                        ->from($this->tb)
                        ->where($where)
                        ->get();

        if (!$res || empty($res)) {
            return [];
        }

        $result = $res->row_array();
        $result = $this->formatDiagnosePageSimpleJunctionDetailData($result, $data['dates'], $data['timingType']);

        return $result;
    }

    /**
    * 获取路口问题趋势图
    * @param $data['task_id']         interger 任务ID
    * @param $data['junction_id']     string   逻辑路口ID
    * @param $data['time_point']      string   时间点
    * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
    * @param $data['diagnose_key']    array    诊断问题KEY
    * @return array
    */
    public function getJunctionQuestionTrend($data)
    {
        if (!empty($data['diagnose_key'])) {
            /*
             * 因为过饱和问题与空放问题共用一个指标，现空放问题的KEY与指标KEY相同
             * 所以可以把过饱和问题的KEY忽略
             */
            $tempDiagnoseKey = [];
            foreach ($data['diagnose_key'] as $k=>$v) {
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

        // 组织where条件
        $where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

        $where  .= ' and type = 0';

        $res = $this->db->select($select)
                        ->from($this->tb)
                        ->where($where)
                        ->get();

        if (!$res || empty($res)) {
            return [];
        }

        $result = $res->result_array();
        $result = $this->formatJunctionQuestionTrendData($result, $data, $data['diagnose_key']);

        return $result;
    }

    /**
    * 获取诊断详情页数据
    * @param $data['task_id']         interger 任务ID
    * @param $data['junction_id']     string   逻辑路口ID
    * @param $data['dates']           array    评估/诊断日期
    * @param $data['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
    * @param $data['time_point']      string   时间点 当search_type = 0 时 必传
    * @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
    * @param $data['type']            interger 详情类型 1：指标详情页 2：诊断详情页
    * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
    * @param $data['timingType']      interger 配时来源 1：人工 2：反推
    * @return array
    */
    private function getDiagnoseJunctionDetail($data)
    {
        $diagnoseKeyConf = $this->config->item('diagnose_key');

        // 组织select 需要的字段
        $select_str = '';
        foreach ($diagnoseKeyConf as $k=>$v) {
            /*
             * 因为过饱和问题与空放都是根据同一指标计算的，现空放问题的KEY与指标相同
             * 所以只不需要再拼接过饱和问题的select column
             */
            if ($k != 'over_saturation') {
                $select_str .= empty($select_str) ? $k : ',' . $k;
            }
        }
        $select = "id, junction_id, {$select_str}, start_time, end_time, result_comment, movements";

        // 组织where条件
        $where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

        if ((int)$data['search_type'] == 1) { // 按方案查询
            // 综合查询
            $time_range = array_filter(explode('-', $data['time_range']));
            $where  .= ' and type = 1';
            $where  .= ' and start_time = "' . trim($time_range[0]) . '"';
            $where  .= ' and end_time = "' . trim($time_range[1]) . '"';;
        } else { // 按时间点查询
            $select .= ', time_point';
            $where  .= ' and type = 0';
            $where  .= ' and time_point = "' . trim($data['time_point']) . '"';
        }

        $res = $this->db->select($select)
                        ->from($this->tb)
                        ->where($where)
                        ->get();

        if (!$res || empty($res)) {
            return [];
        }

        $result = $res->row_array();
        $result = $this->formatJunctionDetailData($result, $data['dates'], 2, $data['timingType']);

        return $result;
    }

    /**
    * 获取指标详情页数据
    * @param $data['task_id']         interger 任务ID
    * @param $data['junction_id']     string   逻辑路口ID
    * @param $data['dates']           array    评估/诊断日期
    * @param $data['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
    * @param $data['time_point']      string   时间点 当search_type = 0 时 必传
    * @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
    * @param $data['type']            interger 详情类型 1：指标详情页 2：诊断详情页
    * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
    * @param $data['timingType']      interger 配时来源 1：人工 2：反推
    * @return array
    */
    private function getQuotaJunctionDetail($data)
    {
        $select = 'id, junction_id, start_time, end_time, result_comment, movements';

        // 组织where条件
        $where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

        if ((int)$data['search_type'] == 1) { // 按方案查询
            // 综合查询
            $time_range = array_filter(explode('-', $data['time_range']));
            $where  .= ' and type = 1';
            $where  .= ' and start_time = "' . trim($time_range[0]) . '"';
            $where  .= ' and end_time = "' . trim($time_range[1]) . '"';;
        } else { // 按时间点查询
            $select .= ', time_point';
            $where  .= ' and type = 0';
            $where  .= ' and time_point = "' . trim($data['time_point']) . '"';
        }

        $res = $this->db->select($select)
                        ->from($this->tb)
                        ->where($where)
                        ->get();

        if (!$res || empty($res)) {
            return [];
        }

        $result = $res->row_array();
        $result = $this->formatJunctionDetailData($result, $data['dates'], 1, $data['timingType']);

        return $result;

    }

    /**
    * 格式化路口详情数据
    * @param $data        路口详情数据
    * @param $dates       评估/诊断日期
    * @param $resultType 数据返回类型 1：指标详情页 2：诊断详情页
    * @param $timingType  配时数据来源 1：人工 2：反推
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
    * 格式化诊断列表页简易路口详情数据
    * @param $data        路口详情数据
    * @param $dates       评估/诊断日期
    * @param $timingType  配时数据来源 1：人工 2：反推
    */
    private function formatDiagnosePageSimpleJunctionDetailData($data, $dates, $timingType)
    {
        if (empty($data) || empty($dates)) {
            return [];
        }

        $data['movements'] = json_decode($data['movements'], true);
        if (empty($data['movements'])) {
            return [];
        }

        $resultData = [];
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
    * 格式化路口问题趋势数据
    *     PS:当路口无问题属于正常状态时，返回路口级指标平均延误的趋势图
    * @param $data                         array  Y 路口数据
    * @param $whereData['time_point']      string Y 时间点 用于标注问题持续时间段用
    * @param $whereData['task_time_point'] string Y 任务时间段
    * @param $diagnose                     array  N 需要查询的问题 当路口正常状态时，可为空
    * @return array
    */
    private function formatJunctionQuestionTrendData($data, $whereData, $diagnose)
    {
        // 正常路口返回路口级指标平均延误的趋势图
        $normalQuota = 'stop_delay';

        // 任务开始、结束时间
        $taskTimeRange = array_filter(explode('-', $whereData['task_time_range']));
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
                $continuouStart = strtotime($whereData['time_point']);
                // 此问题持续结束时间
                $continuouEnd = strtotime($whereData['time_point']);
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
    * 获取路口地图底图数据
    * @param $data['junction_id']     string   Y 逻辑路口ID
    * @param $data['dates']           string   Y 评估/诊断任务日期 ['20180102','20180103']
    * @param $data['search_type']     interger Y 查询类型 1：按方案查询 0：按时间点查询
    * @param $data['time_point']      string   N 时间点 格式 00:00 PS:当search_type = 0 时 必传
    * @param $data['time_range']      string   N 方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传 时间段
    * @param $data['task_time_range'] string   Y 评估/诊断任务开始结束时间 格式 00:00-24:00
    * @param $data['timingType']      interger Y 配时来源 1：人工 2：反推
    * @return array
    */
    public function getJunctionMapData($data)
    {
        if (empty($data)) {
            return [];
        }

        $junction_id = trim($data['junction_id']);

        $result = [];

        // 获取配时数据 地图底图数据源用配时的
        $timing_data = [
            'junction_id' => $junction_id,
            'dates'       => $data['dates'],
            'timingType'  => $data['timingType']
        ];
        if ((int)$data['search_type'] == 1) { // 按方案查询
            $time_range = array_filter(explode('-', $data['time_range']));
            $timing_data['time_range'] = trim($time_range[0]) . '-' . date("H:i", strtotime($time_range[1]) - 60);
        } else { // 按时间点查询
            $timing_data['time_point'] = trim($data['time_point']);
            $timing_data['time_range'] = trim($data['task_time_range']);
        }

        $timing = $this->timing_model->getTimingDataForJunctionMap($timing_data);
        if (!$timing || empty($timing)) {
            return [];
        }

        /*------------------------------------
        | 获取路网路口各相位经纬度及路口中心经纬度 |
        -------------------------------------*/
        // 获取地图版本
        $map_version = $this->waymap_model->getMapVersion($data['dates']);
        if (empty($map_version)) {
            return [];
        }

        // 获取路网路口各相位坐标
        $waymap_data = [
            'version'           => trim($map_version),
            'logic_junction_id' => $junction_id,
            'logic_flow_ids'    => array_keys($timing['list']),
        ];
        $ret = $this->waymap_model->getJunctionFlowLngLat($waymap_data);
        if (empty($ret['data'])) {
            return [];
        }
        foreach ($ret['data'] as $k=>$v) {
            if (!empty($timing['list'][$v['logic_flow_id']])) {
                $result['dataList'][$k]['logic_flow_id'] = $v['logic_flow_id'];
                $result['dataList'][$k]['flow_label'] = $timing['list'][$v['logic_flow_id']];
                $result['dataList'][$k]['lng'] = $v['flows'][0][0];
                $result['dataList'][$k]['lat'] = $v['flows'][0][1];
            }
        }
        // 获取路口中心坐标
        $result['center'] = '';
        $center_data['logic_id'] = $junction_id;
        $center = $this->waymap_model->getJunctionCenterCoords($center_data);

        $result['center'] = $center;
        $result['map_version'] = $map_version;

        if (!empty($result['dataList'])) {
            $result['dataList'] = array_values($result['dataList']);
        }

        return $result;
    }

    /**
    * 获取路口信息用于轨迹
    * @param $data['task_id']     interger 任务ID
    * @param $data['junction_id'] string   路口ID
    * @param $data['flow_id']     string   flow_id
    * @param $data['search_type'] interger 搜索类型 1：按方案时间段 0：按时间点
    * @param $data['time_point']  string   时间点 当search_type = 0 时有此参数
    * @param $data['time_range']  string   时间段 当search_type = 1 时有此参数
    * @return array
    */
    public function getJunctionInfoForTheTrack($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $select = 'task_id, junction_id, dates, start_time, end_time, clock_shift, movements';
        $where  = "task_id = {$data['task_id']} and junction_id = '{$data['junction_id']}'";
        if ((int)$data['search_type'] == 1) {
            $time_range = explode('-', $data['time_range']);
            $where .= " and type = 1 and start_time = '{$time_range[0]}' and end_time = '{$time_range[1]}'";
        } else {
            $where .= " and type = 0 and time_point = '{$data['time_point']}'";
        }

        $result = $this->db->select($select)
                            ->from($this->tb)
                            ->where($where)
                            ->get();
        if (!$result) {
            $content = "form_data = " . json_encode($data);
            $content .= "<br>sql = " . $this->db->last_query();
            $content .= "<br>result = " . $result;
            sendMail($this->email_to, 'logs: 获取时空/散点图（'.$type.'）->获取路口详情为空', $content);
            return [];
        }

        $result = $result->row_array();
        if (isset($result['movements'])) {
            $result['movements'] = json_decode($result['movements'], true);
            foreach ($result['movements'] as $v) {
                if ($v['movement_id'] == trim($data['flow_id'])) {
                    $result['flow_id'] = $v['movement_id'];
                    $result['af_condition'] = $v['af_condition'] ?? '';
                    $result['bf_condition'] = $v['bf_condition'] ?? '';
                    $result['num'] = $v['num'] ?? 0;
                    unset($result['movements']);
                }
            }
        }

        return $result;
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

    /**
     * 将查询出来的评估/诊断数据合并到全城路口模板中
     * $allData  全城路口
     * $data     任务结果路口
     * $mergeKey 合并KEY
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
    * 比较函数
    */
    public function compare($val1, $val2, $symbol)
    {
        $compare = [
            '>'  => function ($val1, $val2) { return $val1 > $val2; },
            '<'  => function ($val1, $val2) { return $val1 < $val2; },
            '='  => function ($val1, $val2) { return $val1 == $val2;},
            '>=' => function ($val1, $val2) { return $val1 >= $val2;},
            '<=' => function ($val1, $val2) { return $val1 <= $val2;},
        ];
        return $compare[$symbol]($val1, $val2);
    }

    /**
    * 获取任务创建用户 暂时这么做
    */
    public function getTaskUser($taskId)
    {
        $this->db->select('user');
        $this->db->from('task_result');
        $this->db->where('id', $taskId);
        $result = $this->db->get()->row_array();

        return $result['user'];
    }
}
