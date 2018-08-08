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
     * @param $data['base_start_time'] string   N 基准开始时间 格式：yyyy-mm-dd hh:ii:ss
     * 例：2018-08-06 00:00:00 默认：上一周工作日开始时间（上周一 yyyy-mm-dd 00:00:00）
     * @param $data['base_end_time']   string   N 基准结束时间 格式：yyyy-mm-dd hh:ii:ss
     * 例：2018-08-07 23:59:59 默认：上一周工作日结束时间（上周五 yyyy-mm-dd 23:59:59）
     * @param $data['evaluate_time']   array    N 评估时间 有可能会有多个评估时间段，固使用json格式的字符串 默认本周工作日
     * $data['evaluate_time'] = [
     *     [
     *         "start_time" => "2018-08-06 00:00:00", // 开始时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-06 00:00:00
     *         "end_time"   => "2018-08-07 23:59:59"  // 结束时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-07 23:59:59
     *     ],
     * ]
     * @return array
     */
    public function quotaEvaluateCompare($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $sdata = [
            [
                'logic_junction_id' => '2017030116_4875896',
                'logic_flow_id' => '2017030116_i_63164690_2017030116_o_691081540',
                'hour' => '',
                'stop_time_cycle' => mt_rand(10, 200),
                'spillover_rate' => 0,
                'queue_length' => mt_rand(20, 300),
                'stop_delay' => mt_rand(1, 30),
                'stop_rate' => 0,
                'twice_stop_rate' => 0,
                'speed' => 50,
                'free_flow_speed' => 50,
                'traj_count' => mt_rand(100, 300),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'logic_junction_id' => '2017030116_4875896',
                'logic_flow_id' => '2017030116_i_63164690_2017030116_o_63410440',
                'hour' => '',
                'stop_time_cycle' => mt_rand(10, 200),
                'spillover_rate' => 0,
                'queue_length' => mt_rand(20, 300),
                'stop_delay' => mt_rand(1, 30),
                'stop_rate' => 0,
                'twice_stop_rate' => 0,
                'speed' => 50,
                'free_flow_speed' => 50,
                'traj_count' => mt_rand(100, 300),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $tem = [
            '2018-07-30',
            '2018-07-31',
            '2018-08-01',
            '2018-08-02',
            '2018-08-03',
            '2018-08-06',
            '2018-08-07',
        ];

        $table = $this->offlintb . '12';
        foreach ($tep as $v) {
            foreach ($sdata as $kk=>$vv) {
                for ($i = 0; $i < 24 * 3600; $i += 30 * 60) {
                    $sdata[$kk]['hour'] = date('H:i:s', $i);
                    $sdata[$kk]['stop_time_cycle'] = mt_rand(10, 200);
                    $sdata[$kk]['queue_length'] = mt_rand(20, 300);
                    $sdata[$kk]['stop_delay'] = mt_rand(1, 30);
                    $sdata[$kk]['traj_count'] = mt_rand(100, 300);
                    $sdata[$kk]['created_at'] = $v . ' ' . date('H:i:s', $i);
                    $sdata[$kk]['updated_at'] = $v . ' ' . date('H:i:s', $i);
                    $this->db->insert($table, $sdata);
                }
            }
        }

        return $result;
    }
}
