<?php

/********************************************
 * # desc:    区域数据模型
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-08-23
 ********************************************/
use Didi\Cloud\Collection\Collection;

class Opttaskresultroad_model extends CI_Model
{
    private $tb = 'opt_task_result_road';

    /**
     * @var \CI_DB_query_builder
     */
    protected $db;

    /**
     * Opttaskresultroad_model constructor.
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
}
