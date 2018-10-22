<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午4:22
 */

class Realtime_model extends CI_Model
{
    private $tb = 'real_time_';

    /**
     * @var \CI_DB_query_builder
     */
    protected $db;

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
    }

    /**
     * 获得指定城市实时表的最新 hour
     *
     * @param $cityId
     *
     * @return array
     */
    public function getLastestHour($cityId)
    {
        return $this->db->select('hour')
            ->from($this->tb . $cityId)
            ->order_by('updated_at', 'DESC')
            ->order_by('hour', 'DESC')
            ->limit(1)
            ->get()->row_array();
    }

    /**
     * 根据 flow id 集合获取相应数据
     *
     * @param        $cityId
     * @param        $hour
     * @param        $logicJunctionId
     * @param        $logicFlowId
     * @param string $select
     *
     * @return array
     */
    public function getFlowsInFlowIds($cityId, $hour, $logicJunctionId, $logicFlowId, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('hour', $hour)
            ->where('logic_junction_id', $logicJunctionId)
            ->where('updated_at > ', date('Y-m-d', strtotime('-10  minutes')))
            ->where_in('logic_flow_id', $logicFlowId)
            ->get()->result_array();
    }

    public function getQuotasByHour($cityId, $hour, $quotaKey, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('traj_count >= 10')
            ->where('hour', $hour)
            ->group_by('logic_junction_id')
            ->order_by($quotaKey, 'DESC')
            ->limit(100)
            ->get()->result_array();
    }

    public function getQuotaByFlowId($cityId, $logicJunctionId, $logicFlowId, $upTime, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('logic_junction_id', $logicJunctionId)
            ->where('logic_flow_id', $logicFlowId)
            ->where('updated_at >', $upTime)
            ->get()->result_array();
    }

    public function getQuotaEvaluateCompare($cityId, $logicJunctionId, $logicFlowId, $dates, $groupBy, $select = '*')
    {
        $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('logic_junction_id', $logicJunctionId);

        if(!$logicFlowId) {
            $this->db->where('logic_flow_id', $logicFlowId);
        }

        if(!$dates) {
            $this->db->where_in('date', $dates);
        }

        if(!$groupBy) {
            $this->db->group_by($groupBy);
        }

        return $this->db->get()->result_array();
    }
}