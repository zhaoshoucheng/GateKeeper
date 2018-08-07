<?php
/********************************************
# desc:    评估数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Evaluate_model extends CI_Model
{
    private $offlintb = 'offline_';
    private $realtimetb = 'real_time_';
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

    	$table = $this->realtimetb . $data['city_id'];
    	$where = 'hour = (select hour from ' . $table;
    	$where .= ' where day(`hour`) = day(' . $data['date'] . ') order by hour desc limit 1)';

    	$this->db->select("logic_junction_id, logic_flow_id, {$data['quota_key']}");
    	$this->db->from($table);
    	$this->db->where($where);
    	$res = $this->db->get()->result_array();
    	echo "sql = " . $this->db->last_query();
    	if (empty($res)) {
    		return [];
    	}

    	echo "<pre>";print_r($res);

    	$result = $this->formatJunctionQuotaSortListData($res);

    	return $result;
    }

    /**
     * 格式化路口指标排序列表数据
     * @param $data 列表数据
     * @return array
     */
    private function formatJunctionQuotaSortListData($data)
    {
    	$result = [];

    	return $result;
    }
}
