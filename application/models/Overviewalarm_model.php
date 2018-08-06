<?php
/********************************************
# desc:    实时报警数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Overviewalarm_model extends CI_Model
{
    private $tb = 'real_time_alarm';
    private $db = '';

    public function __construct()
    {
        parent::__construct();

        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('realtime_conf.php');
    }

    /**
     * 获取实时报警概览信息
     * @param $data['city_id']    interger Y 城市ID
     * @param $data['date']       string   Y 日期 Y-m-d
     * @param $data['time_point'] string   Y 时间 H:i:s
     * @return array
     */
    public function todayAlarmInfo($data)
    {
    	if (empty($data)) {
    		return [];
    	}
    	$result = [];

    	$this->db->select('logic_junction_id, logic_flow_id, updated_at, type');


    	return $result;
    }
}