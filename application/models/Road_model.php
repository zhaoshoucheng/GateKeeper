<?php
/********************************************
# desc:    干线据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-21
********************************************/

class Evaluate_model extends CI_Model
{
    private $tb = 'road';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        // 判断数据表是否存在
        if ($this->isTableExisted($this->tb)) {
            return [];
        }

        $this->load->model('waymap_model');
    }

    /**
     * 查询干线列表
     * @param $cityId interger Y 城市ID
     * @return array
     */
    public function queryRoadList($cityId)
    {

    }

    /**
     * 新增干线
     * @param $data['city_id']        interger Y 城市ID
     * @param $data['road_name']      string   Y 干线名称
     * @param $data['junction_ids']   string   Y 干线路口ID 用逗号隔开
     * @param $data['road_direction'] interger Y 干线方向 1：东西 2：南北
     * @return array
     */
    public function addRoad($data)
    {
        if (empty($data)) {
            return ['errno' => 0, 'errmsg' => ''];
        }

        // 判断干线名称是否已存在
        if ($this->isRoadNameExisted($data['road_name'])) {
            return ['errno' => -1, 'errmsg' => '干线名称已存在！'];
        }

        $insertData = [
            'city_id'        => intval($data['city_id']),
            'road_name'      => strip_tags(trim($data['road_name'])),
            'junction_ids'   => strip_tags(trim($data['junction_ids'])),
            'road_direction' => intval($data['road_direction']),
            'user_id'        => 0,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        $res = $this->db->insert($this->tb, $insertData);
        if (!$res) {
            return ['errno' => -1, 'errmsg' => '新增干线入库失败！'];
        }

        return ['errno' => 0, 'errmsg' => ''];
    }

    /**
     * 编辑干线
     * @param $data['city_id']        interger Y 城市ID
     * @param $data['road_name']      string   Y 干线名称
     * @param $data['junction_ids']   string   Y 干线路口ID 用逗号隔开
     * @param $data['road_direction'] interger Y 干线方向 1：东西 2：南北
     * @return array
     */
    public function editRoad($data)
    {

    }

    /**
     * 删除干线
     * @param $data['city_id'] interger Y 城市ID
     * @param $data['road_id'] string   Y 干线ID
     * @return array
     */
    public function delete($data)
    {

    }

    /**
     * 查询干线详情
     * @param $data['city_id'] interger Y 城市ID
     * @param $data['road_id'] string   Y 干线ID
     * @return json
     */
    public function getRoadDetail($data)
    {

    }

    /**
     * 校验干线名称是否存在
     */
    private function isRoadNameExisted($name)
    {
        $this->db->select('road_id');
        $this->db->from($this->tb);
        $this->db->where('road_name = "' . $name . '"');
        $res = $this->db->get();

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 校验数据表是否存在
     */
    private function isTableExisted($table)
    {
        $isExisted = $this->dbFlow->table_exists($table);
        return $isExisted;
    }
}
