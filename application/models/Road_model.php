<?php
/********************************************
# desc:    干线据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-21
********************************************/

class Road_model extends CI_Model
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
        if (intval($cityId) < 1) {
            return [];
        }

        $where = 'city_id = ' . $cityId . ' and is_delete = 0';
        $this->db->select('road_id, road_name');
        $this->db->from($this->tb);
        $this->db->where($where);
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        return $res;

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
            'city_id'            => intval($data['city_id']),
            'road_id'            => md5($data['junction_ids'] . $data['road_name']),
            'road_name'          => strip_tags(trim($data['road_name'])),
            'logic_junction_ids' => strip_tags(trim($data['junction_ids'])),
            'road_direction'     => intval($data['road_direction']),
            'user_id'            => 0,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
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
     * @param $data['road_id']        string   Y 干线ID
     * @param $data['road_name']      string   Y 干线名称
     * @param $data['junction_ids']   string   Y 干线路口ID 用逗号隔开
     * @param $data['road_direction'] interger Y 干线方向 1：东西 2：南北
     * @return array
     */
    public function editRoad($data)
    {
        if (empty($data)) {
            return ['errno' => 0, 'errmsg' => ''];
        }

        // 判断干线名称是否已存在
        if ($this->isRoadNameExisted($data['road_name'], $data['road_id'])) {
            return ['errno' => -1, 'errmsg' => '干线名称已存在！'];
        }

        $where = 'road_id = "' . strip_tags(trim($data['road_id'])) . '"';
        $where .= ' and city_id = ' . intval($data['city_id']);
        $this->db->where($where);

        $updateData = [
            'road_name'          => strip_tags(trim($data['road_name'])),
            'logic_junction_ids' => strip_tags(trim($data['junction_ids'])),
            'road_direction'     => intval($data['road_direction']),
            'updated_at'         => date('Y-m-d H:i:s'),
        ];
        $this->db->update($this->tb, $updateData);
        if ($this->db->affected_rows() < 1) {
            return ['errno' => -1, 'errmsg' => '干线更新失败！'];
        }

        return ['errno' => 0, 'errmsg' => ''];
    }

    /**
     * 删除干线
     * @param $data['city_id'] interger Y 城市ID
     * @param $data['road_id'] string   Y 干线ID
     * @return array
     */
    public function delete($data)
    {
        if (empty($data)) {
            return ['errno' => 0, 'errmsg' => ''];
        }

        $where = 'road_id = "' . strip_tags(trim($data['road_id'])) . '"';
        $where .= ' and city_id = ' . intval($data['city_id']);
        $this->db->where($where);
        $updateData = [
            'is_delete'  => 1,
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->update($this->tb, $updateData);
        if ($this->db->affected_rows() < 1) {
            return ['errno' => -1, 'errmsg' => '干线更新失败！'];
        }

        return ['errno' => 0, 'errmsg' => ''];
    }

    /**
     * 查询干线详情
     * @param $data['city_id'] interger Y 城市ID
     * @param $data['road_id'] string   Y 干线ID
     * @return json
     */
    public function getRoadDetail($data)
    {
        if (empty($data)) {
            return ['errno' => 0, 'errmsg' => ''];
        }

        $result = [];

        // 获取详情
        $where = 'city_id = ' . intval($data['city_id']);
        $where .= ' and road_id = "' . strip_tags(trim($data['road_id'])) . '"';
        $where .= ' and is_delete = 0';

        $this->db->select('logic_junction_ids');
        $this->db->from($this->tb);
        $this->db->where($where);

        $res = $this->db->get()->row_array();
        if (!$res || empty($res['logic_junction_ids'])) {
            return [];
        }

        $result = $this->formatRoadDetailData($data['city_id'], $res['logic_junction_ids']);

        return $result;
    }

    /**
     * 格式化干线详情数据
     * @param $ids string 路口ID串
     * @return array
     */
    private function formatRoadDetailData($cityId, $ids)
    {
        $junctionIds = explode(',', preg_replace("/(\n)|(\s)|(\t)|(\')|(')|(，)/" ,',' ,$ids));

        // 最新路网版本
        $allMapVersions = $this->waymap_model->getAllMapVersion();
        $newMapVersion = max($allMapVersions);

        $res = $this->waymap_model->getConnectPath($cityId, $newMapVersion, $junctionIds);
        print_r($res);exit;

    }

    /**
     * 校验干线名称是否存在
     */
    private function isRoadNameExisted($name, $roadId = '')
    {
        $where = 'road_name = "' . $name . '"';
        if (!empty($roadId)) {
            $where .= ' and road_id != "' . $roadId . '"';
        }

        $this->db->select('road_id');
        $this->db->from($this->tb);
        $this->db->where($where);
        $res = $this->db->get()->result_array();

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
        $isExisted = $this->db->table_exists($table);
        return $isExisted;
    }
}
