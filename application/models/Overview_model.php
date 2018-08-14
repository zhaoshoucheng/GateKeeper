<?php
/********************************************
 * # desc:    概览数据模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-07-25
 ********************************************/

class Overview_model extends CI_Model
{
    private $tb = 'real_time_';
    private $db = '';

    public function __construct()
    {
        parent::__construct();

        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $this->config->load('realtime_conf');
        $this->load->model('waymap_model');
        $this->load->model('redis_model');
    }

    /**
     * @param $data['city_id'] Y 城市ID
     * @param $data['date'] N 日期
     * @return array
     */
    public function junctionsList($data)
    {
        $table = $this->tb . $data['city_id'];

        $hour = $this->getLastestHour($data['city_id'], $data['date']);

        $result = $this->db->select('*')
            ->from($table)
            ->where('hour', $hour)
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->get()->result_array();

        $result = $this->getJunctionListResult($data['city_id'], $result);

        return $result;
    }

    /**
     * @param $data['city_id'] Y 城市ID
     * @return array
     */
    public function operationCondition($data)
    {

        $table = 'real_time_' . $data['city_id'];

        $result = $this->redis_model->getData('its_realtime_avg_stop_delay_' . $data['city_id'] . '_' . $data['date']);

        if(!$result) {
            $result = $this->db->select('hour, avg(stop_delay) as avg_stop_delay')
                ->from($table)
                ->where('updated_at >=', $data['date'] . ' 00:00:00')
                ->where('updated_at <=', $data['date'] . ' 23:59:59')
                ->group_by('hour')
                ->get()->result_array();
        }


        $realTimeQuota = $this->config->item('real_time_quota');

        $result       = array_map(function ($v) use ($realTimeQuota) {
            return [
                $realTimeQuota['stop_delay']['round']($v['avg_stop_delay']),
                substr($v['hour'],0, 5)
            ];
        }, $result);
        $allStopDelay = array_column($result, 0);
        $info         = [
            'value' => count($allStopDelay) == 0 ? 0 : $realTimeQuota['stop_delay']['round'](array_sum($allStopDelay) / count($allStopDelay)),
            'unit' => $realTimeQuota['stop_delay']['unit']
        ];

        return [
            'dataList' => $result,
            'info' => $info
        ];
    }

    public function junctionSurvey($data)
    {
        //$data = $this->junctionsList($data);        $data = $data['dataList'] ?? [];
        $table = $this->tb . $data['city_id'];

        $hour = $this->getLastestHour($data['city_id'], $data['date']);

        $result = $this->db->select('*')
            ->from($table)
            ->where('hour', $hour)
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->get()->result_array();
        //数组初步处理，去除无用数据
        $result = array_map(function ($item) {
            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'quota' => $this->getRawQuotaInfo($item),
                'alarm_info' => $this->getRawAlarmInfo($item, []),
            ];
        }, $result);

        //数组按照 logic_junction_id 进行合并
        $temp = [];
        foreach($result as $item) {
            $temp[$item['logic_junction_id']] = isset($temp[$item['logic_junction_id']]) ?
                $this->mergeFlowInfo($temp[$item['logic_junction_id']], $item) :
                $item;
        };

        //处理数据内容格式
        $temp = array_map(function ($item) {
            return [
                'jid' => $item['logic_junction_id'],
                'quota' => ($quota = $this->getFinalQuotaInfo($item)),
                'alarm' => $this->getFinalAlarmInfo($item),
                'status' => $this->getJunctionStatus($quota),
            ];
        }, $temp);

        $data = $temp;

        $result = [];

        $result['junction_total']   = count($data);
        $result['alarm_total']      = 0;
        $result['congestion_total'] = 0;

        foreach ($data as $datum) {
            $result['alarm_total']      += $datum['alarm']['is'];
            $result['congestion_total'] += (int)($datum['status']['key'] == 3);
        }

        return $result;

    }

    /**
     * 获取指定日期最新的数据时间
     * @param $table
     * @param null $date
     * @return false|string
     */
    private function getLastestHour($cityId, $date = null)
    {
        if(($hour = $this->redis_model->getData("its_realtime_lasthour_$cityId"))) {
            return $hour;
        }

        $date = $date ?? date('Y-m-d');

        $result = $this->db->select('hour')
            ->from($this->tb . $cityId)
            ->where('updated_at >=', $date . ' 00:00:00')
            ->where('updated_at <=', $date . ' 23:59:59')
            ->order_by('hour', 'desc')
            ->limit(1)
            ->get()->first_row();

        if(!$result)
            return date('H:i:s');

        return $result->hour;
    }

    /**
     * 处理从数据库中取出的原始数据并返回
     *
     * @param $result
     * @return array
     */
    private function getJunctionListResult($cityId, $result)
    {
        //获取全部路口 ID
        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        //获取路口信息的自定义返回格式
        $junctionsInfo = $this->waymap_model->getAllCityJunctions($cityId, 0, ['key' => 'logic_junction_id', 'value' => ['name', 'lng', 'lat']]);

        //获取需要报警的全部路口ID
        $ids = implode(',', $this->getAlarmFlowIds($result));

        //获取全部路口的全部方向的信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($ids);

        //数组初步处理，去除无用数据
        $result = array_map(function ($item) use ($flowsInfo) {
            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'quota' => $this->getRawQuotaInfo($item),
                'alarm_info' => $this->getRawAlarmInfo($item, $flowsInfo),
            ];
        }, $result);

        //数组按照 logic_junction_id 进行合并
        $temp = [];
        foreach($result as $item) {
            $temp[$item['logic_junction_id']] = isset($temp[$item['logic_junction_id']]) ?
                $this->mergeFlowInfo($temp[$item['logic_junction_id']], $item) :
                $item;
        };

        //处理数据内容格式
        $temp = array_map(function ($item) use ($junctionsInfo) {
            return [
                'jid' => $item['logic_junction_id'],
                'name' => $junctionsInfo[$item['logic_junction_id']]['name'] ?? '',
                'lng' => $junctionsInfo[$item['logic_junction_id']]['lng'] ?? '',
                'lat' => $junctionsInfo[$item['logic_junction_id']]['lat'] ?? '',
                'quota' => ($quota = $this->getFinalQuotaInfo($item)),
                'alarm' => $this->getFinalAlarmInfo($item),
                'status' => $this->getJunctionStatus($quota),
            ];
        }, $temp);

        $lngs = array_filter(array_column($temp, 'lng'));
        $lats = array_filter(array_column($temp, 'lat'));

        $center['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
        $center['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));

        return [
            'dataList' => array_values($temp),
            'center' => $center
        ];
    }

    /**
     * 获取原始指标信息
     *
     * @param $item
     * @return array
     */
    private function getRawQuotaInfo($item)
    {
        return [
            'stop_delay_weight' => $item['stop_delay'] * $item['traj_count'],
            'stop_time_cycle' => $item['stop_time_cycle'],
            'traj_count' => $item['traj_count']
        ];
    }

    /**
     * 获取最终指标信息
     *
     * @param $item
     * @return array
     */
    private function getFinalQuotaInfo($item)
    {
        //实时指标配置文件
        $realTimeQuota = $this->config->item('real_time_quota');

        return [
            'stop_delay' => [
                'name' => '平均延误',
                'value' => $realTimeQuota['stop_delay']['round']($item['quota']['stop_delay_weight'] / $item['quota']['traj_count']),
                'unit' => $realTimeQuota['stop_delay']['unit'],
            ],
            'stop_time_cycle' => [
                'name' => '最大停车次数',
                'value' => $realTimeQuota['stop_time_cycle']['round']($item['quota']['stop_time_cycle']),
                'unit' => $realTimeQuota['stop_time_cycle']['unit'],
            ]
        ];
    }

    /**
     * 获取全部的报警flow的id
     *
     * @param $result
     * @return array
     */
    private function getAlarmFlowIds($result)
    {
        $alarmFormula = $this->config->item('alarm_formula');

        $result = array_filter($result, function ($value) use ($alarmFormula) {
            return !empty($alarmFormula($value));
        });

        return array_unique(array_column($result, 'logic_junction_id'));
    }

    /**
     * 获取原始报警信息
     *
     * @param $item
     * @param $city_id
     * @param $flowsInfo
     * @return array|string
     */
    private function getRawAlarmInfo($item, $flowsInfo)
    {
        $alarmCategory = $this->config->item('alarm_category');

        $alarmFormula = $this->config->item('alarm_formula');

        $result = $alarmFormula($item);

        $result = array_map(function ($v) use ($item, $flowsInfo, $alarmCategory) {
            return isset($flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']]) ?
                $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] . '-' . $alarmCategory[$v]['name']
                : [];
            }, $result);

        return $result;
    }

    /**
     * 获取最终报警信息
     *
     * @param $item
     * @return array
     */
    private function getFinalAlarmInfo($item)
    {
        return [
            'is' => (int)!empty($item['alarm_info']),
            'comment' => $item['alarm_info']
        ];
    }

    /**
     * 获取当前路口的状态
     *
     * @param $item
     * @return array
     */
    private function getJunctionStatus($quota)
    {
        $junctionStatus = $this->config->item('junction_status');

        $junctionStatusFormula = $this->config->item('junction_status_formula');

        return $junctionStatus[$junctionStatusFormula($quota['stop_delay']['value'])];
    }

    /**
     * 数据处理，多个 flow 记录合并到其对应 junction
     *
     * @param $target
     * @param $item
     * @return mixed
     */
    private function mergeFlowInfo($target, $item)
    {
        //合并属性 停车延误加权求和，停车时间求最大，权值求和
        $target['quota']['stop_delay_weight'] += $item['quota']['stop_delay_weight'];
        $target['quota']['stop_time_cycle']   = max($target['quota']['stop_time_cycle'], $item['quota']['stop_time_cycle']);
        $target['quota']['traj_count']        += $item['quota']['traj_count'];

        //合并报警信息
        $target['alarm_info'] = array_merge($target['alarm_info'], $item['alarm_info']) ?? [];

        return $target;
    }

    /**
     * 获取拥堵概览
     *
     * @param $data['city_id']    interger Y 城市ID
     * @param $data['date']       string   Y 日期 Y-m-d
     * @param $data['time_point'] string   Y 时间 H:i:s
     * @return array
     */
    public function getCongestionInfo($data)
    {
        $result = [];
        $table = $this->tb . $data['city_id'];

        // 获取最近时间
        $lastHour = $this->getLastestHour($data['city_id'], $data['date']);

        /*
         * 获取实时路口停车延误记录
         * 现数据表记录的是每个路口各相位的指标数据
         * 所以路口的停车延误指标计算暂时定为：路口各相位的(停车延误 * 轨迹数量)相加 / 路口各相位轨迹数量之和
         */
        $this->db->select('SUM(`stop_delay` * `traj_count`) / SUM(`traj_count`) as stop_delay,
            logic_junction_id,
            hour,
            updated_at'
        );

        $date = $data['date'] . ' ' . $data['time_point'];
        $where = "updated_at > '" . $data['date'] . " 00:00:00'";
        $where .= " and hour = '{$lastHour}'";
        $this->db->from($table);
        $this->db->where($where);
        $this->db->group_by('hour, logic_junction_id');
        $res = $this->db->get();

        $res = $res->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatCongestionInfoData($res);

        return $result;
    }

    /**
     * 格式化拥堵概览数据
     * @param $data 数据
     * @return array
     */
    private function formatCongestionInfoData($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        // 拥堵数量
        $congestionNum = [];

        // 路口总数
        $junctionTotal = count($data);

        // 路口状态配置
        $junctionStatusConf = $this->config->item('junction_status');
        // 路口状态计算规则
        $junctinStatusFormula = $this->config->item('junction_status_formula');

        foreach ($data as $k=>$v) {
            $congestionNum[$junctinStatusFormula($v['stop_delay'])][$k] = 1;
        }

        $result['count'] = [];
        $result['ratio'] = [];
        foreach ($junctionStatusConf as $k=>$v) {
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num'  => isset($congestionNum[$k]) ? count($congestionNum[$k]) : 0,
            ];

            $result['ratio'][$k] = [
                'cate'  => $v['name'],
                'ratio' => isset($congestionNum[$k])
                            ? round((count($congestionNum[$k]) / $junctionTotal) * 100) . '%'
                            : '0%',
            ];
        }

        $result['count'] = array_values($result['count']);
        $result['ratio'] = array_values($result['ratio']);

        return $result;
    }
}
