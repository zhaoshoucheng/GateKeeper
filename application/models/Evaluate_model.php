<?php
/********************************************
# desc:    评估数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Evaluate_model extends CI_Model
{
    private $tb = 'offline_';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('realtime_conf.php');
        $this->load->model('waymap_model');
    }

    /**
     * 获取路口指标排序列表
     * @param $data['city_id']    Y 城市ID
     * @param $data['quota_key']  Y 指标KEY
     * @param $data['date']       N 日期 默认当前日期
     * @param $data['time_point'] N 时间 默认当前时间
     * @return array
     */
    public function getJunctionQuotaSortList($data)
    {
    	if (empty($data)) {
    		return [];
    	}

    	$result = [];

    	$table = $this->tb . $data['city_id'];
    	$where = 'date = (select updated_at from ' . $table . ' order by updated_at desc limit 1)';

    	$this->db->select("logic_junction_id, {$data['quota_key']}");
    	$this->db->from($table);
    	$this->db->where($where);
    	$res = $this->db->get()->result_array();
    	echo "sql = " . $this->db->last_query();
    	if (empty($res)) {
    		return [];
    	}


    	return $result;
    }
}