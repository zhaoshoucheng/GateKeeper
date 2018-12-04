<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/25
 * Time: 下午8:14
 */

class RealtimeAlarm_model extends CI_Model
{
    private $tb = 'real_time_alarm';

    /**
     * @var CI_DB_query_builder
     */
    private $db;

    /**
     * RealtimeAlarm_model constructor.
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
     * 获取实时报警信息详情
     *
     * @param $cityId     int    城市ID
     * @param $date       string 日期 yyyy-mm-dd
     * @param $lastTime   string 时间 yyyy-mm-dd HH:ii:ss
     * @param $cycleTime  string 报警周期 yyyy-mm-dd HH:ii:ss
     *
     * @return array
     */
    public function getRealtimeAlarmList($cityId, $date, $lastTime, $cycleTime = null)
    {
        $this->db->select('type, logic_junction_id, logic_flow_id, start_time, last_time')
            ->from($this->tb)
            ->where('city_id', $cityId)
            ->where('date', $date)
            ->where('last_time >=', $lastTime);

        if(!is_null($cycleTime)) {
            $this->db->where('last_time <=', $cycleTime);
        }

        $res = $this->db->order_by('type asc, (last_time - start_time) desc')
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
}