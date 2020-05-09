<?php

/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:19
 */

class Sjgt_model extends CI_Model
{
    private $tb = 'datain_SJGT_tp_platform_area_db';

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
     * 获取指定路口的自适应配时信息
     *
     * @param string $logicJunctionId
     * @param string $select
     *
     * @return array
     */
    public function getTransfor($logicJunctionId, $select = '*')
    {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->select("area_name", "area_geometry")
            ->where('logic_junction_id', $logicJunctionId)
            ->order_by("id", "desc")
            ->get();
        $list = $res instanceof CI_DB_result ? $res->row_array() : $res;
        print_r($list);
    }
}
