<?php
class OpenCity_model extends CI_Model
{
    private $tb = 'open_city';

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

        $isExisted = $this->db->table_exists($this->tb);

        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    public function getCities()
    {
        $res = $this->db->select("city_id")->from($this->tb)->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    // 获取所有的城市列表
    public function getCityInfos()
    {
        $res = $this->db->select('*')->from($this->tb)->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    // 获取一个城市信息
    public function getCityInfo($city_id) {
        $res = $this->db->select('*')
            ->from($this->tb)
            ->where('city_id', $city_id)
            ->get()
            ->first_row('array');
        return $res;
    }
}


