<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:19
 */

class Businesscooperationinfo_model extends CI_Model
{
    private $tb = 'business_cooperation_info';

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

    /**
     * 写入
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function insert($params)
    {
        $data = [
            'create_time' => date("Y-m-d H:i:s"),
        ];
        $data = array_merge($params,$data);
        return $this->db->insert('business_cooperation_info', $data);
    }
}