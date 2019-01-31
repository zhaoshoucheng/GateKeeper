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
}


