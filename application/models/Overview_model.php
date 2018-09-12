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
        $hour = $this->getLastestHour($data['city_id'], $data['date']);

        $result = $this->getJunctionList($data['city_id'], $data['date'], $hour);

        return $result;
    }

    /**
     * @param $data['city_id'] Y 城市ID
     * @return array
     */
    public function operationCondition($data)
    {

        $table = 'real_time_' . $data['city_id'];
        if (!$this->isTableExisted($table)) {
            return [];
        }

        $result = $this->redis_model->getData('its_realtime_avg_stop_delay_' . $data['city_id'] . '_' . $data['date']);
        $result = json_decode($result, true);
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

        $ext = [];

        array_reduce($result, function ($carry, $item) use (&$ext) {
            $now = strtotime($item[1] ?? '00:00');
            if($now - $carry >= 30 * 60) {
                $ext = array_merge($ext, range($carry + 5 * 60, $now - 5 * 60, 5 * 60));
            }
            return $now;
        }, strtotime('00:00'));

        $result = array_merge($result, array_map(function ($v) {
            return [null, date('H:i', $v)];
        }, $ext));

        $result = array_multisort(array_column($result, 1), SORT_ASC, $result);

        return [
            'dataList' => $result,
            'info' => $info
        ];
    }

    /**
     * 路口概况接口
     * @param $data
     * @return array
     */
    public function junctionSurvey($data)
    {
        $cityId = $data['city_id'];
        $date = $data['date'];
        $hour = $this->getLastestHour($data['city_id'], $data['date']);

        $junctionSurveyKey = "its_realtime_pretreat_junction_survey_{$cityId}_{$date}_{$hour}";

        if(($result = $this->redis_model->getData($junctionSurveyKey))) {
           return json_decode($result, true);
        }

        $data = $this->junctionsList($data);

        $data = $data['dataList'] ?? [];

        $result = [];

        $result['junction_total']   = count($data);
        $result['alarm_total']      = 0;
        $result['congestion_total'] = 0;

        foreach ($data as $datum) {
            $result['alarm_total'] += $datum['alarm']['is'] ?? 0;
            $result['congestion_total'] += (int)(($datum['status']['key'] ?? 0) == 3);
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

        if (!$this->isTableExisted($this->tb . $cityId)) {
            return date('H:i:s');
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
     * 从缓存中取全部路口信息数据
     *
     * @param $cityId
     * @param $date
     * @param $hour
     * @return array
     */
    private function getJunctionList($cityId, $date, $hour)
    {
        $junctionListKey = "its_realtime_pretreat_junction_list_{$cityId}_{$date}_{$hour}";

        if(($junctionList = $this->redis_model->getData($junctionListKey))) {
            return json_decode($junctionList, true);
        }
        return [];
//        if (!$this->isTableExisted($this->tb . $cityId)) {
//            return [];
//        }
//
//        $data = $this->db->select('*')
//            ->from($this->tb . $cityId)
//            ->where('hour', $hour)
//            ->where('traj_count >=', 10)
//            ->where('updated_at >=', $date . ' 00:00:00')
//            ->where('updated_at <=', $date . ' 23:59:59')
//            ->get()->result_array();
//
//        $lngs = array_filter(array_column($data, 'lng'));
//        $lats = array_filter(array_column($data, 'lat'));
//
//        $center['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
//        $center['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));
//
//        return [
//            'dataList' => $data,
//            'center' => $center
//        ];
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
        if (!$this->isTableExisted($table)) {
            return [];
        }

        // 获取最近时间
        $lastHour = $this->getLastestHour($data['city_id'], $data['date']);

        /*
         * 获取实时路口停车延误记录
         * 现数据表记录的是每个路口各相位的指标数据
         * 所以路口的停车延误指标计算暂时定为：路口各相位的(停车延误 * 轨迹数量)相加 / 路口各相位轨迹数量之和
         */
        $sql = '/*{"router":"m"}*/';
        $sql .= 'select SUM(`stop_delay` * `traj_count`) / SUM(`traj_count`) as stop_delay';
        $sql .= ', logic_junction_id, hour, updated_at';
        $sql .= ' from ' . $table;
        $sql .= ' where updated_at >= ?';
        $sql .= ' and updated_at <= ?';
        $sql .= ' and hour = ?';
        $sql .= ' and traj_count >= 10';
        $sql .= ' group by hour, logic_junction_id';
        $res = $this->db->query($sql, [$data['date'] . " 00:00:00", $data['date'] . " 23:59:59", $lastHour])->result_array();

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

    /**
     * 校验数据表是否存在
     */
    private function isTableExisted($table)
    {
        $isExisted = $this->db->table_exists($table);
        return $isExisted;
    }
}
