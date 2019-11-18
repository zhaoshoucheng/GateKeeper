<?php

/********************************************
 * # desc:    城市行政区划围栏
 * # author:  zhuyewei@didichuxing.com
 * # date:    2019-11-14
 ********************************************/

class CityFence_model extends CI_Model{
    private $tb = 'city_fence';

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        $isExisted = $this->db->table_exists($this->tb);

        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     * 根据城市ID获取围栏列表
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function getCityFence($cityID, $divisionID = 0)
    {
        $res = $this->db->select("*")
            ->from($this->tb)
            ->where('city_id', $cityID)
            ->where('division_id',$divisionID)
            ->get();



        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
}