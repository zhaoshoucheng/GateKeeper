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

    public function getQuotaEvaluateCompare($cityId, $logicJunctionId, $logicFlowId, $dates, $groupBy, $select = '*')
    {
        $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('logic_junction_id', $logicJunctionId);

        if($logicFlowId) {
            $this->db->where('logic_flow_id', $logicFlowId);
        }

        if($dates) {
            $this->db->where_in('date', $dates);
        }

        if($groupBy) {
            $this->db->group_by($groupBy);
        }

        return $this->db->get()->result_array();
    }
}