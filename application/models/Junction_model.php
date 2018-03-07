<?php
/********************************************
# desc:    路口数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-03-05
********************************************/

class Junction_model extends CI_Model {
	private $tb = 'junction_index';
    private $db = '';

	public function __construct(){
		parent::__construct();
		if(empty($this->db)){
			$this->db = $this->load->database('default', true);
		}

		$this->load->config('nconf');
	}

	/**
	* 获取全城路口信息
	*/
	public function getAllCityJunctionInfo($data){
		$selectstr = empty($this->selectColumns($data['quota_key'])) ? '' : ',' . $this->selectColumns($data['quota_key']);
		$select = 'id, task_id, junction_id, time_point, result_comment' . $selectstr;

		$where = 'task_id = ' . $data['task_id'];
		if($data['type'] == 0){
			$where .= " and type = " . $data['type'];
		}else if($data['type'] == 1){
			$where .= " and time_point = '{$data['time_point']}'";
		}

		$confidence_conf = $this->config->item('confidence');
		if(count($data['confidence']) != count($confidence_conf)){
			foreach($data['confidence'] as $v){
				$where .= ' and ' . $data['quota_key'] . '_confidence ' . $confidence_conf[$v]['expression'];
			}
		}

		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->get()
						->row_array();
		return $res;
	}

	private function selectColumns($key){
		$select = '';
		if(array_key_exists($key, $this->config->item('junction_quota_key'))){
			$select = $key . ', '. $key .'_confidence';
		}

		return $select;
	}

}