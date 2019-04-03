<?php
/********************************************
# desc:    公共方法模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-23
********************************************/

class Common_model extends CI_Model
{
    private $db = '';
    private $dmp_db = '';

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
        $this->dmp_db = $this->load->database('dmp_captain', true);
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

    /**
     * dmp查询
     * @param $table    table name
     * @param $select   select colum
     * @param $where    where
     * @param $groupby  group by
     * @param $page     offset
     * @param $pagesize count
     * @return mixed
     */
    public function dmpSearch($table, $select = '*', $where = [], $groupby = '', $page = 0, $pagesize = 0)
    {
        $isExisted = $this->dmp_db->table_exists($table);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }

        $this->dmp_db->select($select);
        $this->dmp_db->from($table);
        if (!empty($where)) {
            $this->dmp_db->where($where);
        }

        if (!empty($groupby)) {
            $this->dmp_db->group_by($groupby);
        }

        if ($pagesize >= 1) {
            $this->dmp_db->limit($pagesize, $page);
        }

        return $this->dmp_db->get()->result_array();
    }

    /**
     * 获取v5开城列表
     * @param $cityId long 城市ID
     * @return mixed
     */
    public function getV5DMPCityID()
    {
        $table = 'dmp_city_config';
        $select = 'city_id, city_name';
        $where = [
            'sys_id'   => "signal_control_pro",
            'extra' => "v5",
            'status' => "1",
        ];

        $res = $this->dmpSearch($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k=>$v) {
            $result[] = (int)$v['city_id'];
        }
        return $result;
    }
}
