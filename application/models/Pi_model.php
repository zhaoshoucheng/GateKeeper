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

    public function getJunctionsPi($dates,$junctionIDs,$cityId){

        $res = $this->db
            ->from($this->tb.$cityId)
            ->where_in('date', $dates)
            ->where_in('logic_junction_id', $junctionIDs)
            ->where('city_id', $cityId)
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

}