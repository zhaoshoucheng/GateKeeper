<?php
/********************************************
# desc:    公共方法模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-23
********************************************/

class Common_model extends CI_Model
{
    private $db = '';
    private $its_db = '';

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
    }

    /**
     * 查询
     * @param $table    table name
     * @param $select   select colum
     * @param $where    where
     * @param $groupby  group by
     * @param $page     offset
     * @param $pagesize count
     * @return mixed
     */
    public function search($table, $select = '*', $where = [], $groupby = '', $page = 0, $pagesize = 0)
    {
        $isExisted = $this->db->table_exists($table);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }

        $this->db->select($select);
        $this->db->from($table);
        if (!empty($where)) {
            $this->db->where($where);
        }

        if (!empty($groupby)) {
            $this->db->group_by($groupby);
        }

        if ($pagesize >= 1) {
            $this->db->limit($pagesize, $page);
        }

        return $this->db->get()->result_array();
    }
}
