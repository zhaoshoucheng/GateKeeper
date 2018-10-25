<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/25
 * Time: 下午8:14
 */

class RealtimeAlarm_model extends CI_Model
{
    private $tb = 'real_time_alarm';

    /**
     * @var CI_DB_query_builder
     */
    private $db;

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
    }

    public function countJunctionByType($cityId, $date, $type, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb)
            ->where('date', $date)
            ->where('city_id', $cityId)
            ->where('type', $type)
            ->get()->row_array();
    }

    public function getJunctionByDate($cityId, $dates, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where_in('date', $dates)
            ->group_by('logic_junction_id, date')
            ->get()->result_array();
    }

    public function getRealtimeAlarmList($cityId, $date, $lastTime, $cycleTime)
    {
        return $this->db->select('type, logic_junction_id, logic_flow_id, start_time, last_time')
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('date', $date)
            ->where('last_time >=', $lastTime)
            ->where('last_time <=', $cycleTime)
            ->order_by('type asc, (last_time - start_time) desc')
            ->get()->result_array();
    }
}