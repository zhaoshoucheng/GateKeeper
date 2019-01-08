<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午4:22
 */

class Realtime_model extends CI_Model
{
    /**
     * @var \CI_DB_query_builder
     */
    protected $db;
    private $tb = 'real_time_';

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
    }

    /**
     * 获得指定城市实时表的最新 hour
     *
     * @param $cityId
     *
     * @return array
     * @throws Exception
     */
    public function getLastestHour($cityId)
    {
        $this->isExisted($cityId);

        $res = $this->db->select('hour')
            ->from($this->tb . $cityId)
            ->order_by('updated_at', 'DESC')
            ->order_by('hour', 'DESC')
            ->limit(1)
            ->get();

        return $res instanceof CI_DB_result ? $res->row_array() : $res;
    }

    /**
     * 判断数据表是否存在
     *
     * @param $cityId
     *
     * @throws Exception
     */
    protected function isExisted($cityId)
    {
        $isExisted = $this->db->table_exists($this->tb . $cityId);

        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     * 根据 flow id 集合获取相应数据
     *
     * @param        $cityId
     * @param        $hour
     * @param        $logicJunctionId
     * @param        $logicFlowId
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getFlowsInFlowIds($cityId, $hour, $logicJunctionId, $logicFlowId, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('hour', $hour)
            ->where('logic_junction_id', $logicJunctionId)
            ->where('updated_at > ', date('Y-m-d', strtotime('-10  minutes')))
            ->where_in('logic_flow_id', $logicFlowId)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param        $cityId
     * @param        $hour
     * @param        $quotaKey
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getQuotasByHour($cityId, $hour, $quotaKey, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('traj_count >= 10')
            ->where('hour', $hour)
            ->group_by('logic_junction_id')
            ->order_by($quotaKey, 'DESC')
            ->limit(100)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param        $cityId
     * @param        $logicJunctionId
     * @param        $logicFlowId
     * @param        $upTime
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getQuotaByFlowId($cityId, $logicJunctionId, $logicFlowId, $upTime, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('logic_junction_id', $logicJunctionId)
            ->where('logic_flow_id', $logicFlowId)
            ->where('updated_at >', $upTime)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param        $cityId
     * @param        $date
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getAvgQuotaByCityId($cityId, $date, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('updated_at >=', $date . ' 00:00:00')
            ->where('updated_at <=', $date . ' 23:59:59')
            ->group_by('hour')
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param        $cityId
     * @param        $hour
     * @param        $date
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getAvgQuotaByJunction($cityId, $hour, $date, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('updated_at >=', $date . ' 00:00:00')
            ->where('updated_at <=', $date . ' 23:59:59')
            ->where('hour', $hour)
            ->where('traj_count >=', 10)
            ->group_by('hour, logic_junction_id')
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param        $cityId
     * @param        $date
     * @param        $hour
     * @param        $pagesize
     * @param string $select
     * @param array  $junctionIds 路口列表，空代表全部路口
     *
     * @return array
     * @throws Exception
     */
    public function getTopStopDelay($cityId, $date, $hour, $pagesize, $select = '*', $junctionIds=[])
    {
        $this->isExisted($cityId);
        if(!empty($junctionIds)){
            $res = $this->db->select($select)
                ->from($this->tb . $cityId)
                ->where_in('logic_junction_id', $junctionIds)
                ->where('hour', $hour)
                ->where('traj_count >=', 10)
                ->where('updated_at >=', $date . ' 00:00:00')
                ->where('updated_at <=', $date . ' 23:59:59')
                ->group_by('logic_junction_id')
                ->order_by('stop_delay', 'desc')
                ->limit($pagesize)
                ->get();
        }else{
            $res = $this->db->select($select)
                ->from($this->tb . $cityId)
                ->where('hour', $hour)
                ->where('traj_count >=', 10)
                ->where('updated_at >=', $date . ' 00:00:00')
                ->where('updated_at <=', $date . ' 23:59:59')
                ->group_by('logic_junction_id')
                ->order_by('stop_delay', 'desc')
                ->limit($pagesize)
                ->get();
        }
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param        $cityId
     * @param        $date
     * @param        $hour
     * @param        $pagesize
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getTopCycleTime($cityId, $date, $hour, $pagesize, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('hour', $hour)
            ->where('traj_count >=', 10)
            ->where('updated_at >=', $date . ' 00:00:00')
            ->where('updated_at <=', $date . ' 23:59:59')
            ->order_by('stop_time_cycle', 'desc')
            ->limit($pagesize)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 获取路口的当天平均延误数据
     * @param        $cityId
     * @param        $date
     * @param        $hour
     * @param        $pagesize
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getJunctionAvgStopDelayList($cityId, $junctionId, $date)
    {
        $this->isExisted($cityId);
        $res = $this->db->select("avg(`stop_delay`) as avg_stop_delay, hour")
                ->from($this->tb . $cityId)
                ->where('logic_junction_id', $junctionId)
                // ->where('traj_count >=', 10)
                ->where('updated_at >=', $date . ' 00:00:00')
                ->where('updated_at <=', $date . ' 23:59:59')
                ->group_by('hour')
                ->order_by('hour')
                ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 获取路口的当天平均延误数据
     * @param        $cityId
     * @param        $date
     * @param        $offset
     *
     * @return array
     * @throws Exception
     */
    public function delOutdateRealtimeData($cityId, $date, $offset)
    {
        $this->isExisted($cityId);
        $res = $this->db->delete($this->tb . $cityId)->where("updated_at < ", $date . ' 00:00:00')->limit($offset);
        return $res;
    }

    public function getOutdateRealtimeDataCnt($cityId, $date)
    {
        $this->isExisted($cityId);
        $res = $this->db->select("count(id) as cnt")
                        ->from($this->tb . $cityId)
                        ->where("updated_at < ", $date . ' 00:00:00')
                        ->get()
                        ->row_array();
        if (!isset($res['cnt'])) {
            return false;
        }
        return $res['cnt'];
    }
}
