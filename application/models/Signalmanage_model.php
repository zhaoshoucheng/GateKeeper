<?php
/********************************************
# desc:    信号机数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-11-19
********************************************/

class Signalmanage_model extends CI_Model
{
    private $tb = 'junction_manage';
    private $db = '';

    public function __construct()
    {
        parent::__construct();

        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            throw new \Exception('数据表不存在！', ERR_DATABASE);
        }
    }

    /**
     * 查询
     * @param $select   select column
     * @param $where    where
     * @param $whereIn  where in
     * @param $orderby  order by
     * @param $page     偏移量
     * @param $pagesize 个数
     * @return mixd
     */
    public function search($where = '', $wehreIn = [], $orderby = '', $page = 0, $pagesize = 0, $select = '*')
    {
        $this->db->select($select);
        $this->db->from($this->tb);

        if (!empty($where)) {
            $this->db->where($where);
        }

        if (!empty($whereIn)) {
            $this->db->where_in('junction_id', $wehreIn);
        }

        if (!empty($orderby)) {
            $this->db->order_by($orderby);
        }

        if ($pagesize >= 1) {
            $this->db->limit($pagesize, $page);
        }

        return $this->db->get()->result_array();
    }

    /**
     * 新增
     * @param $data
     * @return mixd
     */
    public function add($data)
    {
        if (empty($data)) {
            return false;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->db->insert($this->tb, $data);

        return $this->db->insert_id();
    }

    /**
     * 更新
     * @param $id
     * @param $data
     * @return mixd
     */
    public function edit($id, $data)
    {
        if (empty($data)) {
            return false;
        }

        return $this->db->where('id', $id)->update($this->tb, $data);
    }

    /**
     * 删除
     * @param $id
     * @return mixd
     */
    public function del($id)
    {
        if ($id < 1) {
            return false;
        }

        return $this->db->where('id', $id)->delete($this->tb);
    }
}
