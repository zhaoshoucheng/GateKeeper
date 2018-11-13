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

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            throw new \Exception('数据表不存在！');
        }

        $this->load->config('nconf');
        $this->load->model('waymap_model');
        $this->load->model('timing_model');
    }

    /**
     * 查询方法
     * @param $select      select colum  默认 *
     * @param $where       where条件     默认 1
     * @param $resultType  返回方式 row_array|result_array
     * @param $groupBy     group by
     * @param $limit       limit 例：1,100
     */
    public function searchDB($select='*', $where='1', $resultType='result_array', $groupBy='', $limit='')
    {
        $this->db->select($select);
        $this->db->from($this->tb);
        if ($where != '1') {
            $this->db->where($where);
        }
        if ($groupBy != '') {
            $this->db->group_by($groupBy);
        }
        if ($limit != '') {
            $this->db->limit($limit);
        }

        $res = $this->db->get();
        switch ($resultType) {
            case 'row_array':
                return $res instanceof CI_DB_result ? $res->row_array() : $res;
                break;
            default:
                return $res instanceof CI_DB_result ? $res->result_array() : $res;
                break;
        }
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
     * @param $data['task_id']      interger 任务ID
     * @param $data['time_point']   string   时间点
     * @param $data['diagnose_key'] array    诊断问题KEY
     * @param $data['confidence']   interger 置信度
     * @param $data['orderby']      interger 诊断问题排序 1：按指标值正序 2：按指标值倒序 默认2
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
     * @param $taskId     int     任务ID
     * @param $junctionId string  逻辑路口ID
     * @param $timePoint  string  时间点
     * @param $select     string  select colum
     * @return array
     */
    public function getDiagnosePageSimpleJunctionDetail($taskId, $junctionId, $timePoint, $select = '*')
    {
        // 组织where条件
        $where = 'task_id = ' . $taskId . ' and junction_id = "' . $junctionId . '"';
        $where  .= ' and type = 0';
        $where  .= ' and time_point = "' . $timePoint . '"';

        return $this->db->select($select)
                        ->from($this->tb)
                        ->where($where)
                        ->get()->row_array();
    }

    /**
     * 获取路口问题趋势图
     * @param $taskId      int    任务ID
     * @param $junctionId  string 逻辑路口ID
     * @param $select      string select colum
     * @return array
     */
    public function getJunctionQuestionTrend($taskId, $junctionId, $select = '*')
    {
        // 组织where条件
        $where = 'task_id = ' . $taskId . ' and junction_id = "' . $junctionId . '" and type = 0';

        return $this->db->select($select)
                        ->from($this->tb)
                        ->where($where)
                        ->get()->result_array();
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
     * @param $select                  string   select colum
     * @return array
     */
    public function getDiagnoseJunctionDetail($data, $select)
    {
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
        if (!$res) {
            return [];
        }

        return $res->row_array();
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
     * @param $select                  string   select colum
     * @return array
     */
    public function getQuotaJunctionDetail($data, $select)
    {
        // 组织where条件
        $where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

        if ((int)$data['search_type'] == 1) { // 按方案查询
            // 综合查询
            $time_range = array_filter(explode('-', $data['time_range']));
            $where  .= ' and type = 1';
            $where  .= ' and start_time = "' . trim($time_range[0]) . '"';
            $where  .= ' and end_time = "' . trim($time_range[1]) . '"';;
        } else { // 按时间点查询
            $where  .= ' and type = 0';
            $where  .= ' and time_point = "' . trim($data['time_point']) . '"';
        }

        $res = $this->db->select($select)
                        ->from($this->tb)
                        ->where($where)
                        ->get();
        if (!$res) {
            return [];
        }

        return $res->row_array();
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
        $map_version = $this->waymap_model->getMapVersion(implode(',', $data['dates']));
        if (empty($map_version)) {
            return [];
        }

        // 获取路网路口各相位坐标
        $ret = $this->waymap_model->getJunctionFlowLngLat(trim($map_version), $junction_id, array_keys($timing['list']));
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
        $center = $this->waymap_model->getJunctionCenterCoords($junction_id);

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
