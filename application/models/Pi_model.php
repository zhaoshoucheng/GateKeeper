<?php
/**
 * sts_index数据库,查询pi使用
 * User: didi
 * Date: 2019/10/28
 * Time: 上午11:47
 */

class Pi_model extends CI_Model{

    private $tb = 'junction_duration_v6_';

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('sts_index', true);

    }

    public function getJunctionsPi($dates,$junctionIDs,$cityId,$hours){

        $res = $this->db
            ->from($this->tb.$cityId)
            ->where_in('date', $dates)
            ->where_in('hour',$hours)
            ->where_in('logic_junction_id', $junctionIDs)
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    public function getGroupJuncPiWithDatesHours($city_id,$logic_junction_ids,$dates,$hours){
        $res = $this->db
            ->select('SUM(pi*traj_count)/SUM(traj_count) as pi,date,hour')
            ->from($this->tb.$city_id)
            ->where_in('logic_junction_id', $logic_junction_ids)
            ->where_in('date', $dates)
            ->where_in('hour', $hours)
            ->group_by('date,hour')
            ->get();
//         var_dump($this->db->last_query());
        return $res->result_array();
    }

    public function getJunctionsPiWithDatesHours($city_id, $logic_junction_ids, $dates, $hours){
        $res = $this->db
            ->select('logic_junction_id, sum(pi * traj_count) / sum(traj_count) as pi')
            ->from($this->tb.$city_id)
            ->where_in('logic_junction_id', $logic_junction_ids)
            ->where_in('date', $dates)
            ->where_in('hour', $hours)
            ->group_by('logic_junction_id')
            ->get();
//         var_dump($this->db->last_query());
        return $res->result_array();
    }

    public function getJunctionsPiByHours($city_id, $logic_junction_ids, $dates){
        $res = $this->db
            ->select('hour, sum(pi * traj_count) / sum(traj_count) as pi')
            ->from($this->tb.$city_id)
            ->where_in('logic_junction_id', $logic_junction_ids)
            ->where_in('date', $dates)
            ->group_by('hour')
            ->get();
//         var_dump($this->db->last_query());
        return $res->result_array();
    }
}