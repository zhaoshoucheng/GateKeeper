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
	* @param data['task_id']    interger 任务ID
	* @param data['type']       interger 计算指数类型 0：统合 1：时间点
	* @param data['city_id']    interger 城市ID
	* @param data['time_point'] string   评估时间点 指标计算类型为1时非空
	* @param data['confidence']	interger 置信度
	* @param data['quota_key']  string   指标key
	* @return array
	*/
	public function getAllCityJunctionInfo($data){
		$quota_key = $data['quota_key'];

		// 获取全城路口模板 没有模板就没有lng、lat = 画不了图
		$all_city_junctions = $this->getAllCityJunctions($data['city_id']);
		if(count($all_city_junctions) < 1 || !$all_city_junctions){
			return [];
		}

		$selectstr = empty($this->selectColumns($quota_key)) ? '' : ',' . $this->selectColumns($quota_key);
		$select = 'id, junction_id' . $selectstr;

		$where = 'task_id = ' . $data['task_id'];
		if($data['type'] == 1){
			$where .= " and type = " . $data['type'];
		}else if($data['type'] == 0){
			$where .= " and type = {$data['type']} and time_point = '{$data['time_point']}'";
		}

		$confidence_conf = $this->config->item('confidence');
		if($data['confidence'] >= 1 && array_key_exists($data['confidence'], $confidence_conf)){
			$where .= ' and ' . $quota_key . '_confidence ' . $confidence_conf[$data['confidence']]['expression'];
		}

		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->get()
						->result_array();

		// 指标状态 1：高 2：中 3：低
		$quota_key_conf = $this->config->item('junction_quota_key');
		$temp_quota_data = [];
		foreach($res as $k=>&$v){
			if($v[$quota_key] > $quota_key_conf[$quota_key]['status_max']){
				$v['quota_status'] = 1;
			}else if($v[$quota_key] <= $quota_key_conf[$quota_key]['status_max'] && $v[$quota_key] > $quota_key_conf[$quota_key]['status_min']){
				$v['quota_status'] = 2;
			}else{
				$v['quota_status'] = 3;
			}
			$v[$quota_key] = round($v[$quota_key], 5);
			$temp_quota_data[$v['junction_id']][$quota_key] = $v[$quota_key];
			$temp_quota_data[$v['junction_id']]['quota_status'] = $v['quota_status'];
		}

		$result_data = $this->mergeAllJunctions($all_city_junctions, $temp_quota_data, 'quota_detail');

		return $result_data;
	}

	/**
	* 获取路口指标详情
	* @param data['task_id']		任务ID		interger
	* @param data['time_point']		时间点		string
	* @param data['junction_id']	逻辑路口ID	string
	* @param data['dates']			评估/诊断日期	array
	* @param data['type']			详情类型 1：指标详情页 2：诊断详情页
	* @return array
	*/
	public function getFlowQuotas($data){
		$diagnose_key_conf = $this->config->item('diagnose_key');
		$select_str = '';
		foreach($diagnose_key_conf as $k=>$v){
			$select_str .= empty($select_str) ? $k : ',' . $k;
		}
		$select = 'id, junction_id, ' . $select_str . ', movements, result_comment';
		$time_point = trim($data['time_point']);
		$where = 'task_id = ' . (int)$data['task_id']
				. ' and junction_id = "' . trim($data['junction_id']) . '"'
				. " and type = 0 and time_point = '{$time_point}'";

		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->get()
						->row_array();
		// 获取此路口相位名称
		$this->load->helper('http');
		$phase_data = [
						'logic_junction_id'	=>trim($data['junction_id']),
						'days'				=>trim(implode(',', $data['dates'])),
						'time'				=>trim($data['time_point'])
					];

		$timing = httpGET($this->config->item('timing_interface') . '/signal-mis/TimingService/queryTimingByTimePoint', $phase_data);
		if(!$timing){
			return [];
		}
		$timing = json_decode($timing, true);
		if($timing['errorCode'] != 0){
			return [];
		}
		if(count($timing['data']['latest_plan']) < 1){
			return [];
		}

		if(count($res) >= 1){
			$phase_position = [];
			foreach($timing['data']['latest_plan'][0]['plan_detail']['movement_timing'] as $k=>$v){
				$phase_position[$v[0]['flow_logic']['logic_flow_id']] = $v[0]['flow_logic']['comment'];
			}

			$res['movements'] = json_decode($res['movements'], true);

			// 标注相位名称
			foreach($res['movements'] as $k=>$v){
				$res['movements'][$k]['comment'] = isset($phase_position[$v['movement_id']]) ? $phase_position[$v['movement_id']] : "";
			}

			$flow_quota_key = $this->config->item('flow_quota_key');
			// 诊断详情
			if((int)$data['type'] == 2 && count($res) >= 1){
				foreach($diagnose_key_conf as $k=>$v){
					if($this->compare($res[$k], $v['junction_threshold'], $v['junction_threshold_formula'])){
						$res['diagnose_detail'][$k]['name'] = $v['name'];
						$res['diagnose_detail'][$k]['key'] = $k;
						$res['diagnose_detail'][$k]['flow_quota'] = array_intersect_key($flow_quota_key, $v['flow_quota']);
						// 诊断问题性质 1:重度 2:中度 3:轻度
						if($res[$k] > $v['nature_threshold']['low'] && $res[$k] <= $v['nature_threshold']['mide_left']){
							$res['diagnose_detail'][$k]['nature'] = 3;
						}else if($res[$k] > $v['nature_threshold']['mide_left'] && $res[$k] <= $v['nature_threshold']['mide_right']){
							$res['diagnose_detail'][$k]['nature'] = 2;
						}else if($res[$k] > $v['nature_threshold']['mide_right'] && $res[$k] <= $v['nature_threshold']['high']){
							$res['diagnose_detail'][$k]['nature'] = 1;
						}
					}
				}

				// 组织每个问题的不同指标数据集合
				if(isset($res['diagnose_detail'])){
					foreach($res['diagnose_detail'] as $k=>$v){
						foreach($res['movements'] as $k1=>$v1){
							$res['diagnose_detail'][$k]['movements'][$k1] = array_intersect_key($v1, array_merge($v['flow_quota'], ['movement_id'=>'', 'comment'=>'', 'green_split'=>'', 'route_length'=>'']));
						}
					}
				}
			}

			$res['flow_quota'] = $flow_quota_key;
		}

		return $res;
	}

	/**
	* 获取全城路口诊断问题列表
	* @param data['task_id']      interger 任务ID
	* @param data['city_id']      interger 城市ID
	* @param data['time_point']   string   时间点
	* @param data['type']         interger 计算类型
	* @param data['confidence']   interger 置信度
	* @param data['diagnose_key'] string   诊断问题KEY
	* @return array
	*/
	public function getJunctionsDiagnoseList($data){
		// 获取全城路口模板 没有模板就没有lng、lat = 画不了图
		$all_city_junctions = $this->getAllCityJunctions($data['city_id']);
		if(count($all_city_junctions) < 1 || !$all_city_junctions){
			return [];
		}

		$diagnose_key_conf = $this->config->item('diagnose_key');
		$select_quota_key = [];
		foreach($diagnose_key_conf as $k=>$v){
			$select_quota_key[] = $k;
		}

		$selectstr = empty($this->selectColumns($select_quota_key)) ? '' : ',' . $this->selectColumns($select_quota_key);
		$select = 'id, junction_id' . $selectstr;

		$where = 'task_id = ' . $data['task_id'];
		if($data['type'] == 1){
			$where .= " and type = " . $data['type'];
		}else if($data['type'] == 0){
			$where .= " and type = {$data['type']} and time_point = '{$data['time_point']}'";
		}

		// 诊断问题数
		$diagnose_key_count = count($data['diagnose_key']);

		$confidence_where = '';
		foreach($data['diagnose_key'] as $v){
			$confidence_where .= empty($confidence_where) ? $v . '_confidence' : '+' . $v . '_confidence';

		}
		$confidence_threshold = $this->config->item('diagnose_confidence_threshold');

		$temp_confidence_expression[1] = '(' . $confidence_where . ') / ' . $diagnose_key_count . '>=' . $confidence_threshold;
		$temp_confidence_expression[2] = '(' . $confidence_where . ') / ' . $diagnose_key_count . '<' . $confidence_threshold;

		$confidence_conf = $this->config->item('confidence');
		if($data['confidence'] >= 1 && array_key_exists($data['confidence'], $confidence_conf)){
			$where .= ' and ' . $temp_confidence_expression[$data['confidence']];
		}

		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->get()
						->result_array();
		$temp_diagnose_data = [];
		foreach($res as $k=>$v){
			foreach($data['diagnose_key'] as $val){
				$temp_diagnose_data[$v['junction_id']][$val] = round($v[$val], 5);
				$is_diagnose = 0;
				if($this->compare($v[$val], $diagnose_key_conf[$val]['junction_threshold'], $diagnose_key_conf[$val]['junction_threshold_formula'])){
					$is_diagnose = 1;
				}

				$temp_diagnose_data[$v['junction_id']][$val . '_diagnose'] = $is_diagnose;
			}
		}

		$result_data = $this->mergeAllJunctions($all_city_junctions, $temp_diagnose_data, 'diagnose_detail');

		return $result_data;
	}

	/**
	* 诊断-诊断问题排序列表
	* @param data['task_id']		任务ID		interger
	* @param data['time_point']		时间点		string
	* @param data['diagnose_key']	诊断问题KEY	string
	* @param data['orderby']		排序			interger
	* @return array
	*/
	public function getDiagnoseRankList($data){
		$select = 'junction_id, ' . $data['diagnose_key'];

		$where = 'task_id = ' . $data['task_id'] . " and type = 0 and time_point = '{$data["time_point"]}'";

		$diagnose_key_conf = $this->config->item('diagnose_key');
		$where .= " and {$data['diagnose_key']} {$diagnose_key_conf[$data['diagnose_key']]['junction_threshold_formula']} {$diagnose_key_conf[$data['diagnose_key']]['junction_threshold']}";

		$sort_conf = $this->config->item("sort_conf");

		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->order_by($data['diagnose_key'], $sort_conf[$data['orderby']])
						->get()
						->result_array();

		$logic_junction_ids = '';
		if(count($res) >= 1){
			foreach($res as $k=>$v){
				$res[$k][$data['diagnose_key']] = round($v[$data['diagnose_key']], 5);
				$logic_junction_ids .= empty($logic_junction_ids) ? $v['junction_id'] : ',' . $v['junction_id'];
			}
		}

		$junction_info = [];
		if(!empty($logic_junction_ids)){
			$junction_info = $this->getJunctionInfo($logic_junction_ids);
		}

		$junction_id_name = [];
		if(count($junction_info) >= 1){
			foreach($junction_info as $v){
				$junction_id_name[$v['logic_junction_id']] = $v['name'];
			}
		}

		if(count($res) >= 1){
			foreach($res as $k=>$v){
				$res[$k]['junction_label'] = isset($junction_id_name[$v['junction_id']]) ? $junction_id_name[$v['junction_id']] : '';
			}
		}
		return $res;
	}

	/**
	* 批量获取路口信息
	* @param logic_junction_ids 	逻辑路口ID串 	string
	* @return array
	*/
	private function getJunctionInfo($ids){
		$this->load->helper('http');
		$data['logic_ids'] = $ids;
		$res = httpGET($this->config->item('waymap_interface') . '/flow-duration/map/many', $data);
		if(!$res){
			return $this->response([], 100500, 'Failed to connect to waymap service.');
		}
		$res = json_decode($res, true);
		if($res['errorCode'] != 0){
			return $this->response([], 100500, $res['errorMsg']);
		}

		return $res['data'];
	}

	/**
	* 组织select 字段
	*/
	private function selectColumns($key){
		$select = '';
		if(is_string($key)){ // 评估，单选
			if(array_key_exists($key, $this->config->item('junction_quota_key'))){
				$select = $key;
			}
		}
		if(is_array($key)){ // 诊断问题， 多选
			foreach($key as $v){
				if(array_key_exists($v, $this->config->item('diagnose_key'))){
					$select .= empty($select) ? $v : ',' . $v;
				}
			}
		}

		return $select;
	}

	/**
	* 获取全城路口
	* @param city_id 		Y 城市ID
	* @return array
	*/
	private function getAllCityJunctions($city_id){
		$redis = new redis();

		if(!$redis->connect('127.0.0.1', 6379)){
			return [];
		}

		$city_junctions = $redis->get("all_city_junctions_{$city_id}");
		if(!$city_junctions){
			$this->load->helper('http');

			$data = [
						'city_id'	=> $city_id,
						'token'		=> $this->config->item('waymap_token'),
						'offset'	=> 0,
						'count'		=> 10000
					];

			$res = httpGET($this->config->item('waymap_interface') . '/flow-duration/map/getList', $data);
			if(!$res){
				return $this->response([], 100500, 'Failed to connect to waymap service.');
			}
			$res = json_decode($res, true);
			if($res['errorCode'] != 0){
				return $this->response([], 100500, $res['errorMsg']);
			}
			$city_junctions = $res['data'];
			$redis->delete("all_city_junctions_{$city_id}");
			$redis->set("all_city_junctions_{$city_id}", json_encode($city_junctions));
			$redis->expire("all_city_junctions_{$city_id}", 3600 * 24);
		}else{
			$city_junctions = json_decode($city_junctions, true);
		}

		return $city_junctions;
	}

	/**
	* 将查询出来的评估/诊断数据合并到全城路口模板中
	*/
	private function mergeAllJunctions($all_data, $data, $merge_key = 'detail'){
		$result_data = [];
		$temp_lng[$k] = [];
		$temp_lat[$k] = [];
		foreach($all_data as $k=>$v){
			if(isset($data[$v['logic_junction_id']])){
				$temp_lng[$k] = $v['lng'];
				$temp_lat[$k] = $v['lat'];
				$result_data[$k]['logic_junction_id'] = $v['logic_junction_id'];
				$result_data[$k]['name'] = $v['name'];
				$result_data[$k]['lng'] = $v['lng'];
				$result_data[$k]['lat'] = $v['lat'];
				$result_data[$k][$merge_key] = $data[$v['logic_junction_id']];
			}
		}

		$center_lat = 0;
		$center_lng = 0;

		if(count($temp_lat) >= 1 && count($temp_lng) >= 1){
			asort($temp_lng);
			asort($temp_lat);

			reset($temp_lat);
			$min_lat = current($temp_lat);
			$max_lat = end($temp_lat);
			reset($temp_lng);
			$min_lng = current($temp_lng);
			$max_lng = end($temp_lng);

			$center_lat = ($min_lat + $max_lat) / 2;
			$center_lng = ($min_lng + $max_lng) / 2;
		}

		$result_data['center']['lng'] = $center_lng;
		$result_data['center']['lat'] = $center_lat;

		return array_values($result_data);
	}

	/**
	* 比较函数
	*/
	public function compare($val1, $val2, $symbol) {
	    $compare = [
	        '>' => function ($val1, $val2) { return $val1 > $val2; },
	        '<' => function ($val1, $val2) { return $val1 < $val2; },
	        '=' => function ($val1, $val2) { return $val1 == $val2;}
	    ];
	    return $compare[$symbol]($val1, $val2);
	}
}