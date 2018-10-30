<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/30
 * Time: 下午3:36
 */

class TimeAlarmRemarks_model extends CI_Model
{
    /**
     * @var \CI_DB_query_builder
     */
    protected $db;
    private $tb = 'time_alarm_remarks';

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
     *
     * 获取报警人工校验信息
     *
     * @param $cityId
     * @param $areaId
     * @param $junctionIds
     * @param $flowIds
     *
     * @return array
     */
    public function getAlarmRemarks($cityId, $areaId, $junctionIds, $flowIds)
    {
        if (empty($junctionIds) || empty($flowIds)) {
            return [];
        }

        $res = $this->db->select('logic_flow_id, type')
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('area_id', $areaId)
            ->where_in('logic_junction_id', $junctionIds)
            ->where_in('logic_flow_id', $flowIds)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function insertAlarmRemark($data)
    {
        return $this->db->insert($this->tb, $data);
    }
}