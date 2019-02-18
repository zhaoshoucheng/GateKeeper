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

    /**
     * @var CI_DB_query_builder
     */
    protected $db;

    public function __construct()
    {
        parent::__construct();

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
        if ($cityId == 12) { // 济南先试行数据服务
            $url = $this->config->item('data_service_interface');
            if ($type == 'detail') {
                $data = [
                    'city_id' => $cityId,
                    'select_column' => $select,
                    'logic_junction_id' => $logicJunctionId,
                    'logic_flow_id' => $logicFlowId,
                    'date' => $dates,
                    'engine' => 'elastic',
                ];
                return httpPOST($url . '/getQuotaEvaluateDetail', $data, 0, 'json');
            } else {
                $data = [
                    'city_id' => $cityId,
                    'select_column' => $select,
                    'quota' => $quotaKey,
                    'logic_junction_id' => $logicJunctionId,
                    'group_by' => 'logic_junction_id, hour, date',
                    'date' => $dates,
                    'engine' => 'elastic',
                ];
                return httpPOST($url . '/getQuotaEvalute', $data, 0, 'json');
            }
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
        if ($cityId == 12) {
            $data = [
                'city_id' => $cityId,
                'select_column' => $select,
                'quota' => $quotaKey,
                'logic_junction_id' => $logicJunctionId,
                'group_by' => 'logic_flow_id, hour',
                'order_by' => 'logic_flow_id, hour',
                'traj_count_value' => 10,
                'date' => $dates,
                'hour' => $hours,
                'engine' => 'elastic',
            ];
            $url = $this->config->item('data_service_interface');
            return httpPOST($url . '/getFlowQuota', $data, 0, 'json');
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

}