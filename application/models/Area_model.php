<?php

/********************************************
 * # desc:    区域数据模型
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-08-23
 ********************************************/
use Didi\Cloud\Collection\Collection;

class Area_model extends CI_Model
{
    private $tb = 'area';

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
     * 根据城市ID获取区域列表
     *
     * @param $cityId
     * @param string $select
     * @return array
     */
    public function getAreasByCityId($cityId, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('delete_at', "1970-01-01 00:00:00")
            ->order_by('id', 'DESC')
            ->get()->result_array();
    }

    /**
     * 根据区域ID获取区域信息
     *
     * @param $areaId
     * @param string $select
     * @return mixed
     */
    public function getAreaByAreaId($areaId, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb)
            ->where('id', $areaId)
            ->where('delete_at', '1970-01-01')
            ->get()->row_array();
    }

    /**
     * 根据城市ID和区域名称获取区域信息
     *
     * @param $cityId
     * @param $areaName
     * @param string $select
     * @return mixed
     */
    public function getAreaByAreaName($cityId, $areaName, $select = '*')
    {
        return $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('area_name', $areaName)
            ->where('delete_at', '1970-01-01 00:00:00')
            ->get()->row_array();
    }

    /**
     * 根据区域ID获取路口列表
     *
     * @param $areaId
     * @param string $select
     * @return array
     */
    public function getJunctionsByAreaId($areaId, $select = '*')
    {
        return $this->db->select($select)
            ->from('area_junction_relation')
            ->where('area_id', $areaId)
            ->where('delete_at', '1970-01-01 00:00:00')
            ->get()->result_array();
    }

    /**
     * 根据日期，路口ID，时间，城市ID获取路口的分组信息
     *
     * @param $dates
     * @param $junctionIds
     * @param $hours
     * @param $cityId
     * @param $select
     * @return Collection|null
     */
    public function getJunctionByCityId($dates, $junctionIds, $hours, $cityId, $select)
    {
        return $this->db->select($select)
            ->from('junction_hour_report')
            ->where_in('date', $dates)
            ->where_in('logic_junction_id', $junctionIds)
            ->where_in('hour', $hours)
            ->where('city_id', $cityId)
            ->group_by('date, hour')
            ->get()->result_array();
    }

    /**
     * 创建区域
     *
     * @param $data
     * @return mixed 插入的ID
     */
    public function insertArea($data)
    {
        $data['create_at'] = $data['create_at'] ?? date('Y-m-d H:i:s');
        $data['update_at'] = $data['update_at'] ?? date('Y-m-d H:i:s');

        $this->db->insert($this->tb, $data);

        return $this->db->insert_id();
    }

    /**
     * 创建区域路口关联
     *
     * @param $areaId
     * @param $junctionIds
     * @return mixed
     */
    public function insertAreaJunctions($areaId, $junctionIds)
    {
        $data = array_map(function ($junctionId) use ($areaId) {
            return [
                'junction_id' => $junctionId,
                'area_id' => $areaId
            ];
        }, $junctionIds);

        return $this->insertBatchAreaJunctions($data);
    }

    /**
     * 批量插入区域路口关联
     *
     * @param $data
     * @return mixed 操作的记录条数
     */
    public function insertBatchAreaJunctions($data)
    {
        if(empty($data)) {
            return null;
        }

        $data = array_map(function ($item) {
            $item['create_at'] = $item['create_at'] ?? date('Y-m-d H:i:s');
            $item['update_at'] = $item['update_at'] ?? date('Y-m-d H:i:s');
            return $item;
        }, $data);

        return $this->db->insert_batch('area_junction_relation', $data);
    }

    /**
     * 更新区域
     *
     * @param $areaId
     * @param $data
     * @return bool 更新的结果
     */
    public function updateArea($areaId, $data)
    {
        $data['update_at'] = $data['update_at'] ?? date('Y-m-d H:i:s');

        return $this->db->where('id', $areaId)
            ->where('delete_at', '1970-01-01 00:00:00')
            ->update($this->tb, $data);
    }

    /**
     * 获取区域路口关联
     *
     * @param $areaId
     * @param string $select
     * @return mixed
     */
    public function getAreaJunctions($areaId, $select = '*')
    {
        return $this->db->select($select)
            ->from('area_junction_relation')
            ->where('area_id', $areaId)
            ->where('delete_at', '1970-01-01 00:00:00')
            ->get()->result_array();
    }

    /**
     * 删除区域
     *
     * @param $areaId
     * @return bool
     */
    public function deleteArea($areaId)
    {
        return $this->db->where('id', $areaId)
            ->set('delete_at', date('Y-m-d H:i:s'))
            ->update($this->tb);
    }

    /**
     * 删除区域路口关联
     *
     * @param $areaId
     * @param $junctionIds
     * @return null
     */
    public function deleteAreaJunctions($areaId, $junctionIds)
    {
        return $this->db->where('area_id', $areaId)
            ->where_in('junction_id', $junctionIds)
            ->where('delete_at', '1970-01-01 00:00:00')
            ->set('delete_at', date('Y-m-d H:i:s'))
            ->update('area_junction_relation');
    }

    /**
     * 根据区域ID集合获取
     *
     * @param $areaIds
     * @param string $select
     * @return array
     */
    public function getAreaJunctionsByAreaIds($areaIds, $select = '*')
    {
        return $this->db->select($select)
            ->from('area_junction_relation')
            ->where_in('area_id', $areaIds)
            ->where('delete_at', '1970-01-01 00:00:00')
            ->get()->result_array();
    }

    public function areaNameIsUnique($areaName, $cityId, $areaId = null)
    {
        $this->db->limit(1)
            ->from($this->tb)
            ->where('area_name', $areaName)
            ->where('city_id', $cityId)
            ->where('delete_at != ', '1970-01-01 00:00:00');

        if(!is_null($areaId)) {
            $this->db->where('area_id != ', $areaId);
        }

        return $this->db->get()->num_rows() === 0;
    }
}
