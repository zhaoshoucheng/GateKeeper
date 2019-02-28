<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/22
 * Time: 下午6:21
 */

class FlowDurationV6_model extends CI_Model
{
    protected $tb = 'flow_duration_v6_';
    protected $engine = '';

    /**
     * @var CI_DB_query_builder
     */
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->load->config("nconf");

        $this->engine = $this->config->item('data_engine');

        $this->db = $this->load->database('default', true);
    }

    /**
     * @param        $cityId
     * @param        $logicJunctionId
     * @param        $logicFlowId
     * @param        $dates
     * @param        $groupBy
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getQuotaEvaluateCompare($cityId, $logicJunctionId, $logicFlowId, $dates, $groupBy, $quotaKey = '', $select = '*', $type = 'quota')
    {
        if (1 || $cityId == 12) { // 济南先试行数据服务
            $url = $this->config->item('data_service_interface');
            $dates = array_values($dates);
            $res = [];
            if ($type == 'detail') {
                $data = [
                    'city_id'           => (int)$cityId,
                    'select_column'     => $select,
                    'logic_junction_id' => $logicJunctionId,
                    'logic_flow_id'     => $logicFlowId,
                    'date'              => $dates,
                    'engine'            => $this->engine,
                ];
                $res = httpPOST($url . '/getQuotaEvaluateDetail', $data, 0, 'json');
            } else {
                $data = [
                    'city_id'           => (int)$cityId,
                    'select_column'     => $select,
                    'quota'             => $quotaKey,
                    'logic_junction_id' => $logicJunctionId,
                    'group_by'          => 'logic_junction_id, hour, date',
                    'date'              => $dates,
                    'engine'            => $this->engine,
                ];
                $res =  httpPOST($url . '/getQuotaEvalute', $data, 0, 'json');
            }
            if (!$res) {
                return [];
            }

            $res = json_decode($res, true);
            if ($res['errno'] != 0) {
                return [];
            }
            return $res['data'];
        }
        $this->isExisted($cityId);
        if (!empty($quotaKey)) {
            $select .= ', avg(' . $quotaKey . ') as ' . $quotaKey;
        }
        $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('logic_junction_id', $logicJunctionId);

        if ($logicFlowId) {
            $this->db->where('logic_flow_id', $logicFlowId);
        }

        if ($dates) {
            $this->db->where_in('date', $dates);
        }

        if ($groupBy) {
            $this->db->group_by($groupBy);
        }

        return $this->db->get()->result_array();
    }

    /**
     * 判断数据表是否存在
     *
     * @param $cityId
     *
     * @throws Exception
     */
    protected function isExisted($cityId)
    {
        $isExisted = $this->db->table_exists($this->tb . $cityId);

        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     *
     * @param        $cityId
     * @param        $logicJunctionId
     * @param        $dates
     * @param        $hours
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getQuotaByJunction($cityId, $logicJunctionId, $dates, $hours, $quotaKey = '', $select = '*')
    {
        if (1 || $cityId == 12) {
            $data = [
                'city_id'           => (int)$cityId,
                'select_column'     => $select,
                'quota'             => $quotaKey,
                'logic_junction_id' => $logicJunctionId,
                'group_by'          => 'logic_flow_id, hour',
                'order_by'          => 'logic_flow_id, hour',
                'traj_count_value'  => 10,
                'date'              => $dates,
                'hour'              => $hours,
                'engine'            => $this->engine,
            ];
            $url = $this->config->item('data_service_interface');

            $res = httpPOST($url . '/getFlowQuota', $data, 0, 'json');
            if (!$res) {
                return [];
            }

            $res = json_decode($res, true);
            if ($res['errno'] != 0) {
                return [];
            }
            return $res['data'];
        }
        $this->isExisted($cityId);

        $select .= ', sum('.$quotaKey.' * traj_count) / sum(traj_count) as ' . $quotaKey;
        return $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('logic_junction_id', $logicJunctionId)
            ->where_in('date', $dates)
            ->where_in('hour', $hours)
            ->where('traj_count >=', 10)
            ->group_by('logic_flow_id, hour')
            ->order_by('logic_flow_id, hour')
            ->get()->result_array();
    }

    /**
     *
     * @param        $cityId
     * @param        $date
     * @return array
     * @throws Exception
     */
    public function delOldQuotaData($cityId, $date, $offset)
    {
        $this->isExisted($cityId);
        $this->db->where('date <', $date);
        $this->db->limit($offset);
        return $this->db->delete($this->tb . $cityId);
    }

    public function getOldQuotaDataCnt($cityId, $date)
    {
        $this->isExisted($cityId);
        $res = $this->db->select("count(*) as cnt")->from($this->tb . $cityId)->where('date <', $date)->get()->row_array();
        if (!isset($res['cnt'])) {
            return false;
        }
        return $res['cnt'];
    }

    /**
     * 获取单点路口优化对比
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @param $data['city_id']           int      Y 城市ID
     * @param $data['date']              array    Y 所需要查询的日期
     * @param $data['hour']              array    Y 所需要查询的时间
     * @param $data['quota_key']         string   Y 指标key
     * @return array
     */
    public function getQuotaInfoByDate($data)
    {
        if (1 || $data['city_id'] == 12) {
            $data = [
                'city_id'           => (int)$data['city_id'],
                'quota'             => $data['quota_key'],
                'logic_junction_id' => $data['logic_junction_id'],
                'traj_count_value'  => 10,
                'date'              => $data['date'],
                'hour'              => $data['hour'],
                'engine'            => $this->engine,
            ];

            $url = $this->config->item('data_service_interface');
            $res = httpPOST($url . '/getJunctionOptCompare', $data, 0, 'json');
            if (!$res) {
                return [];
            }

            $res = json_decode($res, true);
            if ($res['errno'] != 0) {
                return [];
            }
            return $res['data'];
        }

        $this->isExisted($data['city_id']);

        $quotaFormula = 'sum(`' . $data['quota_key'] . '` * `traj_count`) / sum(`traj_count`)';
        $this->db->select("logic_flow_id, hour,  {$quotaFormula} as quota_value");
        $this->db->from($this->tb . $data['city_id']);
        $where = [
            'logic_junction_id' => $data['logic_junction_id'],
            'traj_count >='     => 10,
        ];
        $this->db->where($where);
        $this->db->where_in('date', $data['date']);
        $this->db->where_in('hour', $data['hour']);
        $this->db->group_by('logic_flow_id, hour');
        $res = $this->db->get()->result_array();
        if (!$res) {
            return [];
        }

        return $res;
    }

    /**
     * 多路口指标计算
     * @param        $dates
     * @param        $hours
     * @param        $junctionIds
     * @param        $flowIds
     * @param        $cityId
     * @param string $select
     *
     * @return array
     */
    public function getJunctionByCityId($dates, $flowIds, $cityId, $quotaKey, $flowLength = [], $select = '*')
    {
        if (1 || $cityId == 12) {
            $data = [
                'city_id'        => intval($cityId),
                'quota'          => $quotaKey,
                'date'           => $dates,
                'logic_flow_id'  => array_values(array_filter($flowIds)),
                'time_case_when' => $flowLength,
                'engine'         => $this->engine
            ];

            $url = $this->config->item('data_service_interface');
            $res = httpPOST($url . '/getJunctionsQuotaEvaluate', $data, 0, 'json');
            if (!$res) {
                return [];
            }

            $res = json_decode($res, true);
            if ($res['errno'] != 0) {
                return [];
            }
            return $res['data'];
        }
        $res = $this->db->select($select)
            ->from('flow_duration_v6_' . $cityId)
            ->where_in('date', $dates)
            ->where_in('logic_flow_id', $flowIds)
            ->group_by('date, hour')
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

}
