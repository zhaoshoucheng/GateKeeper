<?php
/********************************************
# desc:    评估数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Evaluate_model extends CI_Model
{
    private $offlintb = 'offline_';
    private $realtimetb = 'real_time_';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('realtime_conf.php');
        $this->load->model('waymap_model');
    }

    /**
     * 获取路口指标排序列表
     * @param $data['city_id']    Y 城市ID
     * @param $data['quota_key']  Y 指标KEY
     * @param $data['date']       N 日期 默认当前日期
     * @param $data['time_point'] N 时间 默认当前时间
     * @return array
     */
    public function getJunctionQuotaSortList($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $table = $this->realtimetb . $data['city_id'];
        $where = 'hour = (select hour from ' . $table;
        $where .= ' where day(`updated_at`) = day("' . $data['date'] . '") order by hour desc limit 1)';

        $this->db->select("`logic_junction_id`, SUM({$data['quota_key']}) / count(logic_flow_id) as quota_value");
        $this->db->from($table);
        $this->db->where($where);
        $this->db->group_by('logic_junction_id');
        $this->db->order_by('(SUM(' . $data['quota_key'] . ') / count(logic_flow_id)) DESC');
        $this->db->limit(100);
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatJunctionQuotaSortListData($res, $data['quota_key']);

        return $result;
    }

    /**
     * 格式化路口指标排序列表数据
     * @param $data     列表数据
     * @param $quotaKey 查询的指标KEY
     * @return array
     */
    private function formatJunctionQuotaSortListData($data, $quotaKey)
    {
        $result = [];

        // 所需查询路口名称的路口ID串
        $junctionIds = implode(',', array_unique(array_column($data, 'logic_junction_id')));

        // 获取路口信息
        $junctionsInfo = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');

        $result['dataList'] = array_map(function($val) use($junctionIdName) {
            return [
                'logic_junction_id' => $val['logic_junction_id'],
                'junction_name'     => $junctionIdName[$val['logic_junction_id']] ?? '',
                'quota_value'       => $val['quota_value'],
            ];
        }, $data);

        // 返回数据：指标信息
        $result['quota_info'] = [
            'name' => $quotaConf[$quotaKey]['name'],
            'key'  => $quotaKey,
            'unit' => $quotaConf[$quotaKey]['unit'],
        ];

        return $result;
    }

    /**
     * 获取指标趋势
     * @param $data['city_id']     interger Y 城市ID
     * @param $data['junction_id'] string   Y 路口ID
     * @param $data['quota_key']   string   Y 指标KEY
     * @param $data['flow_id']     string   Y 相位ID
     * @param $data['date']        string   Y 日期 格式：Y-m-d 默认当前日期
     * @param $data['time_point']  string   Y 时间 格式：H:i:s 默认当前时间
     * @return array
     */
    public function getQuotaTrend($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $table = $this->realtimetb . $data['city_id'];
        $where = 'logic_junction_id = "' . $data['junction_id'] . '"';
        $where .= ' and logic_flow_id = "' . $data['flow_id'] . '"';
        $where .= ' and day(`updated_at`) = day("' . $data['date'] . '")';
        $this->db->select("hour, {$data['quota_key']}");
        $this->db->from($table);
        $this->db->where($where);
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatQuotaTrendData($res, $data['quota_key']);

        return $result;
    }

    /**
     * 格式化指标趋势数据
     * @param $data     指标趋势数据
     * @param $quotaKey 查询的指标KEY
     * @return array
     */
    public function formatQuotaTrendData($data, $quotaKey)
    {
        $result = [];

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');

        $result['dataList'] = array_map(function($val) use($quotaKey) {
            return [
                // 指标值 Y轴
                $val[$quotaKey],
                // 时间点 X轴
                $val['hour']
            ];
        }, $data);


        // 返回数据：指标信息
        $result['quota_info'] = [
            'name' => $quotaConf[$quotaKey]['name'],
            'key'  => $quotaKey,
            'unit' => $quotaConf[$quotaKey]['unit'],
        ];

        return $result;
    }

    /**
     * 获取路口地图数据
     * @param $data['city_id']     interger Y 城市ID
     * @param $data['junction_id'] string   Y 路口ID
     * @return array
     */
    public function getJunctionMapData($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        // 获取最新路网版本 在全部路网版本中取最新的
        $mapVersions = $this->waymap_model->getAllMapVersion();
        // 最新路网版本
        $newMapVersion = max($mapVersions);

        // 获取路口所有相位
        $allFlows = $this->waymap_model->getFlowsInfo($data['junction_id']);

        // 获取路网路口各相位坐标
        $waymap_data = [
            'version'           => $newMapVersion,
            'logic_junction_id' => $data['junction_id'],
            'logic_flow_ids'    => array_keys($allFlows[$data['junction_id']]),
        ];
        $ret = $this->waymap_model->getJunctionFlowLngLat($waymap_data);
        if (empty($ret['data'])) {
            return [];
        }
        foreach ($ret['data'] as $k=>$v) {
            if (!empty($allFlows[$data['junction_id']][$v['logic_flow_id']])) {
                $result['dataList'][$k]['logic_flow_id'] = $v['logic_flow_id'];
                $result['dataList'][$k]['flow_label'] = $allFlows[$data['junction_id']][$v['logic_flow_id']];
                $result['dataList'][$k]['lng'] = $v['flows'][0][0];
                $result['dataList'][$k]['lat'] = $v['flows'][0][1];
            }
        }
        // 获取路口中心坐标
        $result['center'] = '';
        $centerData['logic_id'] = $data['junction_id'];
        $center = $this->waymap_model->getJunctionCenterCoords($centerData);

        $result['center'] = $center;
        $result['map_version'] = $newMapVersion;

        if (!empty($result['dataList'])) {
            $result['dataList'] = array_values($result['dataList']);
        }

        return $result;
    }

    /**
     * 指标评估对比
     * @param $data['city_id']         interger Y 城市ID
     * @param $data['junction_id']     string   Y 路口ID
     * @param $data['quota_key']       string   Y 指标KEY
     * @param $data['flow_id']         string   Y 相位ID
     * @param $data['base_time']       array    Y 基准时间 [1532880000, 1532966400, 1533052800] 日期时间戳
     * @param $data['evaluate_time']   array    Y 评估时间 有可能会有多个评估时间段
     * $data['evaluate_time'] = [
     *     [
     *         1532880000,
     *         1532880000,
     *     ],
     * ]
     * @param $data['base_time_start_end']       array Y 基准时间 开始、结束时间 用于返回数据
     * @param $data['evaluate_time_start_end']   array Y 评估时间 开始、结束时间 用于返回数据
     * @return array
     */
    public function quotaEvaluateCompare($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $table = $this->offlintb . $data['city_id'];

        $groupBy = '';
        $where = "logic_junction_id = '{$data['junction_id']}'";

        $seelctColumn = "logic_junction_id, logic_flow_id, created_at, hour, {$data['quota_key']} as quota_value";
        // 取路口所有方向
        if ($data['flow_id'] == 9999) {
            $seelctColumn = 'logic_junction_id, hour,';
            $seelctColumn .= " sum({$data['quota_key']}) / count(logic_flow_id) as quota_value";
            $groupBy = 'logic_junction_id';
        } else {
            $where .= " and logic_flow_id = '{$data['flow_id']}'";
        }

        $this->db->select($seelctColumn);
        $this->db->from($table);

        // 合并所有需要查询的日期
        $evaluateAllDates = [];
        foreach ($data['evaluate_time'] as $k=>$v) {
            foreach ($v as $vv) {
                $evaluateAllDates[$vv] = $vv;
            }
        }

        $allDates = array_unique(array_merge($data['base_time'], $evaluateAllDates));

        $whereIn = '';
        foreach ($allDates as $val) {
            $tempDate = date('Y-m-d', $val);

            $whereIn .= empty($whereIn)
                    ? ' and day(created_at) IN (day("' . $tempDate . '")'
                    : ', day("' . $tempDate . '")';
        }
        // 闭合 IN
        $whereIn .= !empty($whereIn) ? ')' : '';

        $where .= $whereIn;
        $this->db->where($where);
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatQuotaEvaluateCompareData($res, $data);

        return $result;
    }

    /**
     * 格式化指标评估对比数据
     * @param $data   指标数据
     * @param $params 参数
     * @return array
     */
    private function formatQuotaEvaluateCompareData($data, $params)
    {
        $result = [];

        // 基准日期
        $baseDate = array_map(function($val) {
            return date('Y-m-d', $val);
        }, $params['base_time']);

        // 评估日期
        $evaluateDate = [];
        foreach ($params['evaluate_time'] as $k=>$v) {
            $evaluateDate[$k] = array_map(function($val) {
                return date('Y-m-d', $val);
            }, $v);
        }

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');

        // 平均对比数组
        $avgArr = [];
        $result['base'] = [];
        $result['evaluate'] = [];
        $result['average'] = [];

        foreach ($data as $k=>$v) {
            $date = date('Y-m-d', strtotime($v['created_at']));

            // 组织基准时间数据
            if (in_array($date, $baseDate, true)) {
                $result['base'][$date][strtotime($v['hour'])] = [
                    // 指标值
                    $quotaConf[$params['quota_key']]['round']($v['quota_value']),
                    // 时间
                    $v['hour'],
                ];

                $avgArr['average']['base'][strtotime($v['hour'])][$date] = [
                    'hour'  => date('H:i:s', $v['hour']),
                    'value' => $v['quota_value'],
                ];
            }

            // 组织评估时间数据
            foreach ($evaluateDate as $kk=>$vv) {
                if (in_array($date, $vv, true)) {
                    $result['evaluate'][$kk + 1][$date][strtotime($v['hour'])] = [
                        // 指标值
                        $quotaConf[$params['quota_key']]['round']($v['quota_value']),
                        // 时间
                        $v['hour'],
                    ];
                    $avgArr['average']['evaluate'][$kk][strtotime($v['hour'])][$date] = [
                        'hour'  => date('H:i:s', $v['hour']),
                        'value' => $v['quota_value'],
                    ];
                }
            }
        }

        // 处理基准平均值
        if (!empty($avgArr['average']['base'])) {
            $result['average']['base'] = array_map(function($val) {
                $tempData = array_column($val, 'value');
                $tempSum = array_sum($tempData);
                $tempCount = count($val);
                return [
                    // 指标平均值
                    $quotaConf[$params['quota_key']]['round']($tempSum / $tempCount),
                    // 时间
                    $val['hour'],
                ];
            }, $avgArr['average']['base']);
        }
        // 处理评估平均值
        if (!empty($avgArr['average']['evaluate'])) {
            foreach ($avgArr['average']['evaluate'] as $k=>$v) {
                $result['average']['evaluate'][$k] = array_map(function($val) {
                    $tempData = array_column($val, 'value');
                    $tempSum = array_sum($tempData);
                    $tempCount = count($val);
                    return [
                        // 指标平均值
                        $quotaConf[$params['quota_key']]['round']($tempSum / $tempCount),
                        // 时间
                        $val['hour'],
                    ];
                }, $v);
            }
        }

        // 排序、去除key
        if (!empty($result['base'])) {
            foreach ($result['base'] as $k=>$v) {
                ksort($result['base'][$k]);
                $result['base'][$k] = array_values($result['base'][$k]);
            }
        }

        if (!empty($result['evaluate'])) {
            foreach ($result['evaluate'] as $k=>$v) {
                foreach ($v as $kk=>$vv) {
                    ksort($result['evaluate'][$k][$kk]);
                    $result['evaluate'][$k][$kk] = array_values($result['evaluate'][$k][$kk]);
                }
            }
        }

        // 获取路口信息
        $junctionsInfo = $this->waymap_model->getJunctionInfo($params['junction_id']);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');

        // 获取路口相位信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($junctionIds);
        // 将所有方向放入路口相位信息中
        $flowsInfo[$params['junction_id']]['9999'] = '所有方向';

        // 基本信息
        $result['info'] = [
            'junction_name' => $junctionIdName[$params['junction_id']] ?? '',
            'quota_name'    => $quotaConf[$params['quota_key']]['name'],
            'quota_unit'    => $quotaConf[$params['quota_key']]['unit'],
            'base_time'     => $params['base_time_start_end'],
            'evaluate_time' => $params['evaluate_time_start_end'],
            'direction'     => $flowsInfo[$params['junction_id']][$params['flow_id']] ?? '',
        ];

        return $result;
    }
}
