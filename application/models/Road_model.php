<?php
/********************************************
 * # desc:    干线据模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-08-21
 ********************************************/

class Road_model extends CI_Model
{
    private $tb = 'road';

    /**
     * @var CI_DB_query_builder
     */
    private $db;

    /**
     * Road_model constructor.
     * @throws Exception
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
     * 根据 城市ID 获取干线列表
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function getRoadsByCityId($cityId, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('is_delete', 0)
            ->order_by('created_at desc')
            ->get()->result_array();
    }

    /**
     * 获取指定干线
     *
     * @param $roadId
     * @param string $select
     * @return array
     */
    public function getRoadByRoadId($roadId, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb)
            ->where('road_id', $roadId)
            ->where('is_delete', 0)
            ->get()->row_array();
    }

    /**
     * 插入新的干线数据
     *
     * @param $data
     * @return mixed
     */
    public function insertRoad($data)
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        $this->db->insert($this->tb, $data);

        return $this->db->insert_id();
    }

    /**
     * 更新干线数据
     *
     * @param $roadId
     * @param $data
     * @return bool
     */
    public function updateRoad($roadId, $data)
    {
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return $this->db->where('road_id', $roadId)
            ->where('is_delete', 0)
            ->update($this->tb, $data);
    }

    /**
     * 删除干线
     *
     * @param $roadId
     * @return bool
     */
    public function deleteRoad($roadId)
    {
        return $this->db->where('road_id', $roadId)
            ->where('is_delete', 0)
            ->set('is_delete', 1)
            ->set('deleted_at', date('Y-m-d H:i:s'))
            ->update($this->tb);
    }

    public function getJunctionByCityId($dates, $hours, $junctionIds, $flowIds, $cityId, $select = '*')
    {
        return $this->db->select($select)
            ->from('flow_duration_v6_' . $cityId)
            ->where_in('date', $dates)
            ->where_in('hour', $hours)
            ->where_in('logic_junction_id', $junctionIds)
            ->where_in('logic_flow_id', $flowIds)
            ->group_by('date, hour')
            ->get()->result_array();
    }
}
