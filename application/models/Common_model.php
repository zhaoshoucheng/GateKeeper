<?php
/********************************************
# desc:    公共方法模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-23
********************************************/

class Common_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
    }

    /**
     * 查询
     * @param $table   table name
     * @param $select  select colum
     * @param $where   where
     * @param $groupby group by
     * @param $limit   limit
     * @return mixed
     */
    public function search($table, $select = '*', $where = '1', $groupby = '', $limit = '')
    {
        $isExisted = $this->db->table_exists($table);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }

        $this->db->select($select);
        $this->db->from($table);
        if ($where != '1' && !empty($where)) {
            $this->db->where($where);
        }

        if (!empty($groupby)) {
            $this->db->group_by($groupby);
        }

        if (!empty($limit)) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result_array();
    }
}
