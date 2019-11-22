<?php
/********************************************
 * # desc:    干线据模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-08-21
 ********************************************/

/**
 * Class Road_model
 *
 * @property Redis_model $redis_model
 */
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
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     * 根据 城市ID 获取公交干线列表
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function getBusRoadsByCityId($cityId, $select = '*')
    {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('is_delete', 0)
            ->where('type', 1)
            ->order_by('created_at desc')
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
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
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('is_delete', 0)
            ->order_by('created_at desc')
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 根据 md5 获取干线信息
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function getRoadsByJunctionIDs($junctionIDs, $select = '*')
    {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('logic_junction_ids', $junctionIDs)
            ->where('is_delete', 0)
            ->order_by('created_at desc')
            ->get();
        $result = $res instanceof CI_DB_result ? $res->result_array() : $res;
        if(is_array($result) && count($result)>0){
            return $result[0];
        }
        return $result;
    }

    /**
     * 根据 md5 获取干线信息
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function getRoadsByRoadID($roadID, $select = '*')
    {
        //在table字段没有创建前先手动调整
        if($roadID=="8e7f9d4d751720ae3f65ebd2accaddd3"){
            return [
                "forward_in_junctionid"=>"-1103784",
                "forward_out_junctionid"=>"2017030116_1106883",
                "backward_in_junctionid"=>"2017030116_1106883",
                "backward_out_junctionid"=>"-1126362",
            ];
        }
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('road_id', $roadID)
            ->where('is_delete', 0)
            ->order_by('created_at desc')
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 根据 城市ID 获取干线列表
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function getJunctionsByRoadID($roadIDs)
    {
        $res = $this->db->select('logic_junction_ids')
            ->from($this->tb)
            ->where_in('id', $roadIDs)
            ->where('is_delete', 0)
            ->order_by('created_at desc')
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
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
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('road_id', $roadId)
            ->where('is_delete', 0)
            ->get();

        return $res instanceof CI_DB_result ? $res->row_array() : $res;

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

    public function roadNameIsUnique($roadName, $cityId, $roadId = null)
    {
        $this->db->limit(1)
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('road_name', $roadName)
            ->where('is_delete', 0);

        if(!is_null($roadId)) {
            $this->db->where('road_id != ', $roadId);
        }

        return $this->db->get()->num_rows() === 0;
    }

    /**
     * 批量获取干线详情
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function getRoadsByRoadIDs($road_ids, $select = '*')
    {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where_in('road_id', $road_ids)
            ->where('is_delete', 0)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 根据名称模糊搜索
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function searchRoadsByKeyword($city_id, $keyword, $select = '*')
    {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $city_id)
            ->like('road_name', $keyword)
            ->where('is_delete', 0)
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    // 获取一个城市信息
    public function getRoadInfo($road_id) {
        $res = $this->db->select('*')
            ->from($this->tb)
            ->where('road_id', $road_id)
            ->where('is_delete', 0)
            ->get()
            ->first_row('array');
        return $res;
    }
}
