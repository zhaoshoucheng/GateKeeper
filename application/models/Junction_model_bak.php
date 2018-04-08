<?php
/********************************************
# desc:    路口数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-03-05
********************************************/

class Junction_model_bak extends CI_Model {
	private $tb = 'junction_index';
    private $db = '';

	public function __construct(){
		parent::__construct();
		if(empty($this->db)){
			$this->db = $this->load->database('default', true);
		}

		$is_existed = $this->db->table_exists($this->tb);
		if (!$is_existed) {
			// 添加日志
			return [];
		}

		$this->load->config('nconf');
	}

	/**
	* 获取全城路口信息
	* @param data['task_id']    interger 任务ID
	* @param data['type']       interger 计算指数类型 1：统合 0：时间点
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
		if(empty($selectstr)){
			return [];
		}

		$select = '';
		if($data['type'] == 1){ // 综合
			$select = "id, junction_id, max({$quota_key}) as {$quota_key}";
		}else{
			$select = 'id, junction_id' . $selectstr;
		}

		$where = 'task_id = ' . $data['task_id'];
		if($data['type'] == 0){
			$where .= " and type = {$data['type']} and time_point = '{$data['time_point']}'";
		}

		$confidence_conf = $this->config->item('confidence');
		if(isset($data['confidence']) && (int)$data['confidence'] >= 1 && array_key_exists($data['confidence'], $confidence_conf)){
			$where .= ' and ' . $quota_key . '_confidence ' . $confidence_conf[$data['confidence']]['expression'];
		}

		$this->db->select($select);
		$this->db->from($this->tb);
		$this->db->where($where);
		if($data['type'] == 1){
			$this->db->group_by('junction_id');
		}
		$res = $this->db->get()->result_array();

		// 指标状态 1：高 2：中 3：低
		$quota_key_conf = $this->config->item('junction_quota_key');
		$temp_quota_data = [];
		foreach($res as &$v){
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
	* @param data['task_id']		任务ID		                     interger
	* @param data['time_point']		时间点		                     string
	* @param data['junction_id']	逻辑路口ID	                     string
	* @param data['dates']			评估/诊断日期	                     array
	* @param data['type']			详情类型 1：指标详情页 2：诊断详情页  interger
	* @param data['time_range']     评估/诊断时间段                    string
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
		$time_range = array_filter(explode('-', trim($data['time_range'])));
		$phase_data = [
						'logic_junction_id'	=>trim($data['junction_id']),
						'days'				=>trim(implode(',', $data['dates'])),
						'time'				=>trim($data['time_point']),
						'start_time'        =>trim($time_range[0]),
						'end_time'          =>trim($time_range[1])
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

			$confidence = $this->config->item('confidence');
			// 标注相位名称
			foreach($res['movements'] as $k=>$v){
				$res['movements'][$k]['comment'] = isset($phase_position[$v['movement_id']]) ? $phase_position[$v['movement_id']] : "";
				$res['movements'][$k]['confidence'] = '';
				if(isset($v['confidence'])){
					$res['movements'][$k]['confidence'] = isset($confidence[$v['confidence']]['name']) ? $confidence[$v['confidence']]['name'] : '';
				}
			}

			$result_comment_conf = $this->config->item('result_comment');
			$result_comment_view = isset($result_comment_conf[$res['result_comment']]) ? $result_comment_conf[$res['result_comment']] : '';
			$res['result_comment'] = $result_comment_view;

			$flow_quota_key = $this->config->item('flow_quota_key');

			// 诊断详情
			if((int)$data['type'] == 2){
				foreach($diagnose_key_conf as $k=>$v){
					if($this->compare($res[$k], $v['junction_threshold'], $v['junction_threshold_formula'])){
						$res['diagnose_detail'][$k]['name'] = $v['name'];
						$res['diagnose_detail'][$k]['key'] = $k;
						$temp_arr = array_merge($v['flow_quota'], ['confidence'=>'置信度']);
						$res['diagnose_detail'][$k]['flow_quota'] = array_intersect_key($flow_quota_key, $temp_arr);
						$compare_val = $res[$k];
						if($k == 'saturation_index'){ // 空放问题，因为统一算法，空放的性质阈值设置为负数，所以当是空放问题时，传递负数进行比较
							$compare_val = $res[$k] * -1;
						}
						// 诊断问题性质 1:重度 2:中度 3:轻度
						if($compare_val > $v['nature_threshold']['high']){
							$res['diagnose_detail'][$k]['nature'] = 1;
						}else if($compare_val > $v['nature_threshold']['mide'] && $compare_val <= $v['nature_threshold']['high']){
							$res['diagnose_detail'][$k]['nature'] = 2;
						}else if($compare_val > $v['nature_threshold']['low'] && $compare_val <= $v['nature_threshold']['mide']){
							$res['diagnose_detail'][$k]['nature'] = 3;
						}
					}
				}

				// 组织每个问题的不同指标数据集合
				if(isset($res['diagnose_detail']) && count($res['diagnose_detail']) >= 1){
					foreach($res['diagnose_detail'] as $k=>$v){
						foreach($res['movements'] as $k1=>$v1){
							$res['diagnose_detail'][$k]['movements'][$k1] = array_intersect_key($v1, array_merge($v['flow_quota'], ['movement_id'=>'', 'comment'=>'']));
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
	* @param data['diagnose_key'] array    诊断问题KEY
	* @return array
	*/
	public function getJunctionsDiagnoseList($data){
		// 获取全城路口模板 没有模板就没有lng、lat = 画不了图
		$all_city_junctions = $this->getAllCityJunctions($data['city_id']);
		if(count($all_city_junctions) < 1 || !$all_city_junctions){
			return [];
		}

		if($data['type'] == 1){ // 综合
			$res = $this->getJunctionsDiagnoseBySynthesize($data);
		}else{ // 时间点
			$res = $this->getJunctionsDiagnoseByTimePoint($data);
		}

		$diagnose_key_conf = $this->config->item('diagnose_key');
		$temp_diagnose_data = [];
		if(count($res) >= 1){
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
		}

		$result_data = $this->mergeAllJunctions($all_city_junctions, $temp_diagnose_data, 'diagnose_detail');
		//echo "getJunctionsDiagnoseList res = <pre>";print_r($result_data);
		return $result_data;
	}

	/**
	* 查询综合类型全城路口诊断问题列表
	* @param data['task_id']      interger 任务ID
	* @param data['city_id']      interger 城市ID
	* @param data['time_point']   string   时间点
	* @param data['type']         interger 计算类型
	* @param data['confidence']   interger 置信度
	* @param data['diagnose_key'] array    诊断问题KEY
	* @return array
	*/
	private function getJunctionsDiagnoseBySynthesize($data){
		$sql_data = array_map(function($diagnose_key) use ($data){
			$selectstr = "id, junction_id, max({$diagnose_key}) as {$diagnose_key}, {$diagnose_key}_confidence";
			$where = 'task_id = ' . $data['task_id'] . ' and type = 1';
			$temp_data = $this->db->select($selectstr)
								->from($this->tb)
								->where($where)
								->group_by('junction_id')
								->get()->result_array();
			$new_data = [];
			if(count($temp_data) >= 1){
				foreach ($temp_data as $value) {
					$new_data[$value['junction_id']] = $value;
				}
			}
			return $new_data;
		}, $data['diagnose_key']);

		$count = count($data['diagnose_key']);

		$diagnose_confidence_threshold = $this->config->item('diagnose_confidence_threshold');

		$flag = [];
		if(count($sql_data) >= 1){
			$flag = $sql_data[0];
			foreach($flag as $k=>&$v){
				$v = array_reduce($sql_data, function($carry, $item) use($k){
					return array_merge($carry, $item[$k]);
				}, []);
				if((int)$data['confidence'] != 0){
					$total = 0;
					foreach($data['diagnose_key'] as $key){
						$total += $v[$key];
					}

					if($data['confidence'] == 1){ // 置信：高 unset低的
						if($total / $count <= $diagnose_confidence_threshold){
							unset($flag[$k]);
						}
					}else if($data['confidence'] == 2){ // 置信：低 unset高的
						if($total / $count > $diagnose_confidence_threshold){
							unset($flag[$k]);
						}
					}
				}
			}
		}

		return $flag;
	}

	/**
	* 根据时间点查询全城路口诊断问题列表
	* @param data['task_id']      interger 任务ID
	* @param data['time_point']   string   时间点
	* @param data['confidence']   interger 置信度
	* @param data['diagnose_key'] array    诊断问题KEY
	* @return array
	*/
	private function getJunctionsDiagnoseByTimePoint($data){
		$diagnose_key_conf = $this->config->item('diagnose_key');
		$select_quota_key = [];
		foreach($diagnose_key_conf as $k=>$v){
			$select_quota_key[] = $k;
		}

		$selectstr = empty($this->selectColumns($select_quota_key)) ? '' : ',' . $this->selectColumns($select_quota_key);
		$select = 'id, junction_id' . $selectstr;

		$where = "task_id = " . $data['task_id'] . " and type = 0 and time_point = '{$data['time_point']}'";

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
		$res = [];
		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->get()
						->result_array();

		return $res;
	}

	/**
	* 诊断-诊断问题排序列表
	* @param data['task_id']      interger 任务ID
	* @param data['time_point']   string   时间点
	* @param data['diagnose_key'] array    诊断问题KEY
	* @param data['confidence']   interger 置信度
	* @param data['orderby']      interger 诊断问题排序 1：按指标值正序 2：按指标值倒序 默认2
	* @return array
	*/
	public function getDiagnoseRankList($data){
		/*$select = 'junction_id, ' . $data['diagnose_key'];

		$where = 'task_id = ' . $data['task_id'] . " and type = 0 and time_point = '{$data["time_point"]}'";

		$diagnose_key_conf = $this->config->item('diagnose_key');
		$where .= " and {$data['diagnose_key']} {$diagnose_key_conf[$data['diagnose_key']]['junction_threshold_formula']} {$diagnose_key_conf[$data['diagnose_key']]['junction_threshold']}";

		$sort_conf = $this->config->item("sort_conf");

		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->order_by($data['diagnose_key'], $sort_conf[$data['orderby']])
						->get()
						->result_array();*/
		$res = $this->getJunctionsDiagnoseByTimePoint($data);
		echo "res = <pre>";print_r($res);
		if(!$res || empty($res)){
			return [];
		}

		// 按诊断问题组织数组 且 获取路口ID串
		$result = [];
		$logic_junction_ids = '';
		foreach($res as $k=>$v){
			//$res[$k][$data['diagnose_key']] = round($v[$data['diagnose_key']], 5);
			foreach($data['diagnose_key'] as $k1=>$v1){
				$result[$v1][$k]['junction_id'] = $v['junction_id'];
				$result[$v1][$k][$v1] = round($v[$v1], 5);
			}
			$logic_junction_ids .= empty($logic_junction_ids) ? $v['junction_id'] : ',' . $v['junction_id'];
		}

		echo "<hr>result = <pre>";print_r($result);exit;

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

		foreach($res as $k=>$v){
			$res[$k]['junction_label'] = isset($junction_id_name[$v['junction_id']]) ? $junction_id_name[$v['junction_id']] : '';
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
		$data['token'] = $this->config->item('waymap_token');

		try {
			$res = httpGET($this->config->item('waymap_interface') . '/flow-duration/map/many', $data);
			if(!$res){
				// 日志
				return [];
			}
			$res = json_decode($res, true);
			if($res['errorCode'] != 0 || !isset($res['data']) || empty($res['data'])){
				return [];
			}
			return $res['data'];
		} catch (Exception $e) {
			return [];
		}
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
		if((int)$city_id < 1){
			return false;
		}

		/*---------------------------------------------------
		| 先去redis中获取，如没有再调用api获取且将结果放入redis中 |
		-----------------------------------------------------*/
		$this->load->model('redis_model');
		$redis_key = "all_city_junctions_{$city_id}";

		// 获取redis中数据
		$city_junctions = $this->redis_model->getData($redis_key);
		if(!$city_junctions){
			$this->load->helper('http');

			$data = [
						'city_id'	=> $city_id,
						'token'		=> $this->config->item('waymap_token'),
						'offset'	=> 0,
						'count'		=> 10000
					];
			try {
				$res = httpGET($this->config->item('waymap_interface') . '/flow-duration/map/getList', $data);
				if(!$res){
					// 添加日志、发送邮件
					return false;
				}
				$res = json_decode($res, true);
				if(isset($res['errorCode']) && $res['errorCode'] == 0 && isset($res['data']) && count($res['data']) >= 1){
					$this->redis_model->deleteData($redis_key);
					$this->redis_model->setData($redis_key, json_encode($res['data']));
					$this->redis_model->setExpire($redis_key, 3600 * 24);
					$city_junctions = $res['data'];
				}
			} catch (Exception $e) {
				return false;
			}
		}else{
			$city_junctions = json_decode($city_junctions, true);
		}

		return $city_junctions;
	}

	/**
	* 将查询出来的评估/诊断数据合并到全城路口模板中
	*/
	private function mergeAllJunctions($all_data, $data, $merge_key = 'detail'){
		if(!is_array($all_data) || count($all_data) < 1 || !is_array($data) || count($data) < 1){
			return [];
		}

		$result_data = [];
		$temp_lng = [];
		$temp_lat = [];
		foreach($all_data as $k=>$v){
			if(isset($data[$v['logic_junction_id']])){
				$temp_lng[$k] = $v['lng'];
				$temp_lat[$k] = $v['lat'];
				$result_data['dataList'][$k]['logic_junction_id'] = $v['logic_junction_id'];
				$result_data['dataList'][$k]['name'] = $v['name'];
				$result_data['dataList'][$k]['lng'] = $v['lng'];
				$result_data['dataList'][$k]['lat'] = $v['lat'];
				$result_data['dataList'][$k][$merge_key] = $data[$v['logic_junction_id']];
			}
		}

		// 暂时定死一个中心点
		$center_lat = 36.663083;
		$center_lng = 117.033513;
		$result_data['center'] = '';
		$result_data['center']['lng'] = $center_lng;
		$result_data['center']['lat'] = $center_lat;
		if(isset($result_data['dataList']) && count($result_data['dataList']) >= 1){
			$result_data['dataList'] = array_values($result_data['dataList']);
		}

		return $result_data;
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