<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:19
 */

class Adapt_model extends CI_Model
{
    private $tb = 'adapt_timing_mirror';

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
            throw new \Exception('数据表不存在');
        }
    }

    /**
     * 获取指定路口的自适应配时信息
     *
     * @param        $logicJunctionId
     * @param string $select
     *
     * @return array
     */
    public function getAdaptByJunctionId($logicJunctionId, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb)
            ->where('logic_junction_id', $logicJunctionId)
            ->get()->row_array();
    }

    /**
     * 更新自适应配时信息表
     *
     * @param $logicJunctionId
     * @param $data
     *
     * @return bool
     */
    public function updateAdapt($logicJunctionId, $data)
    {
        return $this->db->where('logic_junction_id', $logicJunctionId)
            ->update('adapt_timing_mirror', $data);
    }
}