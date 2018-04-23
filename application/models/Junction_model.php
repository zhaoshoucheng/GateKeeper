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

		$is_existed = $this->db->table_exists($this->tb);
		if (!$is_existed) {
			// 添加日志
			return [];
		}

		$this->load->config('nconf');
		$this->load->model('waymap_model');
		$this->load->model('timing_model');
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
		$all_city_junctions = $this->waymap_model->getAllCityJunctions($data['city_id']);
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
	* @param $data['task_id']         interger 任务ID
	* @param $data['junction_id']     string   逻辑路口ID
	* @param $data['dates']           array    评估/诊断日期
	* @param $data['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
	* @param $data['time_point']      string   时间点 当search_type = 0 时 必传
	* @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
	* @param $data['type']            interger 详情类型 1：指标详情页 2：诊断详情页
	* @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
	* @return array
	*/
	public function getFlowQuotas($data){
		if(!isset($data['type']) || empty($data) || !in_array((int)$data['type'], [1, 2], true)){
			return [];
		}

		if((int)$data['type'] == 2){ // 诊断详情页
			$res = $this->getDiagnoseJunctionDetail($data);
		}else{ // 指标详情页
			$res = $this->getQuotaJunctionDetail($data);
		}

		if(!$res || empty($res)){
			return [];
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
		$all_city_junctions = $this->waymap_model->getAllCityJunctions($data['city_id']);
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
			foreach($data['diagnose_key'] as $val){
				$temp_diagnose_data['count'][$val] = 0;
				foreach($res as $k=>$v){
					$temp_diagnose_data[$v['junction_id']][$val] = round($v[$val], 5);
					$is_diagnose = 0;
					if($this->compare($v[$val], $diagnose_key_conf[$val]['junction_threshold'], $diagnose_key_conf[$val]['junction_threshold_formula'])){
						$is_diagnose = 1;
						$temp_diagnose_data['count'][$val] += 1;
					}

					$temp_diagnose_data[$v['junction_id']][$val . '_diagnose'] = $is_diagnose;
				}
			}
		}

		$result_data = $this->mergeAllJunctions($all_city_junctions, $temp_diagnose_data, 'diagnose_detail');

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

					if($data['confidence'] == 1){ // 置信度：高 unset低的
						if($total / $count <= $diagnose_confidence_threshold){
							unset($flag[$k]);
						}
					}else if($data['confidence'] == 2){ // 置信度：低 unset高的
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
		if(!is_array($data['diagnose_key']) || empty($data['diagnose_key'])){
			return [];
		}
		// PM规定页面左侧列表与右侧地图数据一致，而且只在概览页有此列表，固使用 根据时间点查询全城路口诊断问题列表 接口获取初始数据
		$res = $this->getJunctionsDiagnoseByTimePoint($data);
		if(!$res || empty($res)){
			return [];
		}

		$diagnose_key_conf = $this->config->item('diagnose_key');

		// 按诊断问题组织数组 且 获取路口ID串
		$result = [];
		$logic_junction_ids = '';
		foreach($res as $k=>$v){
			foreach($data['diagnose_key'] as $k1=>$v1){
				// 列表只展示有问题的路口 组织新数据 junction_id=>指标值 因为排序方便
				if($this->compare($v[$v1], $diagnose_key_conf[$v1]['junction_threshold'], $diagnose_key_conf[$v1]['junction_threshold_formula'])){
					$result[$v1][$v['junction_id']] = round($v[$v1], 5);
				}
			}
			// 组织路口ID串，用于获取路口名称
			$logic_junction_ids .= empty($logic_junction_ids) ? $v['junction_id'] : ',' . $v['junction_id'];
		}

		if(empty($result)){
			return [];
		}

		// 排序默认 2
		if(!isset($data['orderby']) || !array_key_exists((int)$data['orderby'], $this->config->item('sort_conf'))){
			$data['orderby'] = 2;
		}
		// 排序
		foreach($data['diagnose_key'] as $v){
			if(isset($result[$v]) && !empty($result[$v])){
				if((int)$data['orderby'] == 1){
					asort($result[$v]);
				}else{
					arsort($result[$v]);
				}
			}
		}

		// 获取路口名称
		$junction_info = [];
		if(!empty($logic_junction_ids)){
			$junction_info = $this->waymap_model->getJunctionInfo($logic_junction_ids);
		}

		// 组织 junction_id=>name 数组 用于匹配路口名称
		$junction_id_name = [];
		if(count($junction_info) >= 1){
			foreach($junction_info as $v){
				$junction_id_name[$v['logic_junction_id']] = $v['name'];
			}
		}

		// 组织最终返回数据结构 ['quota_key'=>['junction_id'=>'xx','junction_label'=>'xxx', 'value'=>0], ......]
		$result_data = [];
		foreach($result as $k=>$v){
			foreach($v as $k1=>$v1){
				$result_data[$k][$k1]['junction_id'] = $k1;
				$result_data[$k][$k1]['junction_label'] = isset($junction_id_name[$k1]) ? $junction_id_name[$k1] : '';
				$result_data[$k][$k1]['value'] = $v1;
			}

			if(isset($result_data[$k]) && !empty($result_data[$k])){
				$result_data[$k] = array_values($result_data[$k]);
			}
		}

		return $result_data;
	}

	/**
	* 获取诊断详情页数据
	* @param $data['task_id']         interger 任务ID
	* @param $data['junction_id']     string   逻辑路口ID
	* @param $data['dates']           array    评估/诊断日期
	* @param $data['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
	* @param $data['time_point']      string   时间点 当search_type = 0 时 必传
	* @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
	* @param $data['type']            interger 详情类型 1：指标详情页 2：诊断详情页
	* @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
	* @return array
	*/
	private function getDiagnoseJunctionDetail($data) {
		$diagnose_key_conf = $this->config->item('diagnose_key');

		// 组织select 需要的字段
		$select_str = '';
		foreach($diagnose_key_conf as $k=>$v){
			$select_str .= empty($select_str) ? $k : ',' . $k;
		}
		$select = "id, junction_id, {$select_str}, start_time, end_time, result_comment, movements";

		// 组织where条件
		$where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

		if((int)$data['search_type'] == 1){ // 按方案查询
			// 综合查询
			$time_range = array_filter(explode('-', $data['time_range']));
			$where  .= ' and type = 1';
			$where	.= ' and start_time = "' . trim($time_range[0]) . '"';
			$where  .= ' and end_time = "' . trim($time_range[1]) . '"';;
		}else{ // 按时间点查询
			$select .= ', time_point';
			$where  .= ' and type = 0';
			$where  .= ' and time_point = "' . trim($data['time_point']) . '"';
		}

		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->get();
		//echo 'sql = ' . $this->db->last_query();

		if(!$res || empty($res)){
			return [];
		}
		$result = $res->row_array();
		//echo "<hr>data = <pre>";print_r($result);
		$result = $this->formatJunctionDetailData($result, $data['dates'], 2);

		return $result;
	}

	/**
	* 获取指标详情页数据
	* @param $data['task_id']         interger 任务ID
	* @param $data['junction_id']     string   逻辑路口ID
	* @param $data['dates']           array    评估/诊断日期
	* @param $data['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
	* @param $data['time_point']      string   时间点 当search_type = 0 时 必传
	* @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
	* @param $data['type']            interger 详情类型 1：指标详情页 2：诊断详情页
	* @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
	* @return array
	*/
	private function getQuotaJunctionDetail($data) {
		$select = 'id, junction_id, start_time, end_time, result_comment, movements';

		// 组织where条件
		$where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

		if((int)$data['search_type'] == 1){ // 按方案查询
			// 综合查询
			$time_range = array_filter(explode('-', $data['time_range']));
			$where  .= ' and type = 1';
			$where	.= ' and start_time = "' . trim($time_range[0]) . '"';
			$where  .= ' and end_time = "' . trim($time_range[1]) . '"';;
		}else{ // 按时间点查询
			$select .= ', time_point';
			$where  .= ' and type = 0';
			$where  .= ' and time_point = "' . trim($data['time_point']) . '"';
		}

		$res = $this->db->select($select)
						->from($this->tb)
						->where($where)
						->get();

		if(!$res || empty($res)){
			return [];
		}
		$result = $res->row_array();
		//echo "<hr>data = <pre>";print_r($result);
		$result = $this->formatJunctionDetailData($result, $data['dates'], 1);

		return $result;

	}

	/**
	* 格式化路口详情数据
	* @param $data        路口详情数据
	* @param $dates       评估/诊断日期
	* @param $result_type 数据返回类型 1：指标详情页 2：诊断详情页
	*/
	private function formatJunctionDetailData($data, $dates, $result_type){
		if(empty($data) || empty($dates) || (int)$result_type < 1){
			return [];
		}

		// 因为详情页地图下方列表所有相位都有 置信度字段，而置信度不属于指标，固将此放到扩展指标集合中
		$data['extend_flow_quota']['confidence'] = '置信度';

		$data['movements'] = json_decode($data['movements'], true);

		// 获取flow_id=>name数组
		$timing_data = [
			'junction_id' => trim($data['junction_id']),
			'dates'       => $dates,
			'time_range'  => $data['start_time'] . '-' . date("H:i", strtotime($data['end_time']) - 60)
		];
		$flow_id_name = $this->timing_model->getFlowIdToName($timing_data);

		$flow_quota_key_conf = $this->config->item('flow_quota_key');
		// 匹配相位名称 并按 南左、北直、西左、东直、北左、南直、东左、西直 进行排序
		if(!empty($data['movements'])){
			$phase = [
				'南左' => 10,
				'北直' => 20,
				'西左' => 30,
				'东直' => 40,
				'北左' => 50,
				'南直' => 60,
				'东左' => 70,
				'西直' => 80
			];
			$temp_movements = [];
			foreach($data['movements'] as $k=>&$v){
				$v['comment'] = !empty($flow_id_name[$v['movement_id']]) ? $flow_id_name[$v['movement_id']] : '';
				foreach($flow_quota_key_conf as $kkk=>$vvv){
					$v[$kkk] = round($v[$kkk], $vvv['round_num']);
				}
				foreach($phase as $kk=>$vv){
					if(!empty($v['comment']) && strpos($v['comment'], $kk) !== false){
						$temp_movements[str_replace($kk, $vv, $v['comment'])] = $v;
					}
				}
				if(empty($v['comment'])){
					$temp_movements[mt_rand(100, 900) + mt_rand(1, 99)] = $v;
				}
				if($result_type == 1){ // 指标详情页，组织每个指标对应各相位集合
					foreach($flow_quota_key_conf as $key=>$val){
						$data['flow_quota_all'][$key]['name'] = $val['name'];
						$data['flow_quota_all'][$key]['movements'][$k]['id'] = $v['movement_id'];
						$data['flow_quota_all'][$key]['movements'][$k]['value'] = round($v[$key], $val['round_num']);
					}
				}
			}

			if(!empty($temp_movements)){
				unset($data['movements']);
				ksort($temp_movements);
				$data['movements'] = array_values($temp_movements);
			}
		}

		$result_comment_conf = $this->config->item('result_comment');
		$data['result_comment'] = isset($result_comment_conf[$data['result_comment']]) ? $result_comment_conf[$data['result_comment']] : '';

		if($result_type == 2){ // 诊断详情页
			// flow级别所有指标集合
			foreach($flow_quota_key_conf as $k=>$v){
				$data['flow_quota_all'][$k] = $v['name'];
			}

			// 组织问题集合
			$diagnose_key_conf = $this->config->item('diagnose_key');
			foreach($diagnose_key_conf as $k=>$v){
				if($this->compare($data[$k], $v['junction_threshold'], $v['junction_threshold_formula'])){
					$data['diagnose_detail'][$k]['name'] = $v['name'];
					$data['diagnose_detail'][$k]['key'] = $k;

					// 计算性质程度
					$compare_val = $data[$k];
					if($k == 'saturation_index'){ // 空放问题，因为统一算法，空放的性质阈值设置为负数，所以当是空放问题时，传递负数进行比较
						$compare_val = $data[$k] * -1;
					}
					// 诊断问题性质 1:重度 2:中度 3:轻度
					if($compare_val > $v['nature_threshold']['high']){
						$data['diagnose_detail'][$k]['nature'] = 1;
					}else if($compare_val > $v['nature_threshold']['mide'] && $compare_val <= $v['nature_threshold']['high']){
						$data['diagnose_detail'][$k]['nature'] = 2;
					}else if($compare_val > $v['nature_threshold']['low'] && $compare_val <= $v['nature_threshold']['mide']){
						$data['diagnose_detail'][$k]['nature'] = 3;
					}

					// 匹配每个问题指标
					$temp_merge = array_merge($v['flow_quota'], ['movement_id'=>'logic_flow_id', 'comment'=>'name', 'confidence'=>'置信度']);
					foreach($data['movements'] as $kk=>$vv){
						$data['diagnose_detail'][$k]['movements'][$kk] = array_intersect_key($vv, $temp_merge);
						foreach($v['flow_quota'] as $key=>$val){
							$data['diagnose_detail'][$k]['flow_quota'][$key]['name'] = $val['name'];
							$data['diagnose_detail'][$k]['flow_quota'][$key]['movements'][$kk]['id'] = $vv['movement_id'];
							$data['diagnose_detail'][$k]['flow_quota'][$key]['movements'][$kk]['value'] = round($vv[$key], $flow_quota_key_conf[$key]['round_num']);
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	* 获取路口地图底图数据
	* @param $data['junction_id']     string   Y 逻辑路口ID
	* @param $data['dates']           string   Y 评估/诊断任务日期 ['20180102','20180103']
	* @param $data['search_type']     interger Y 查询类型 1：按方案查询 0：按时间点查询
	* @param $data['time_point']      string   N 时间点 格式 00:00 PS:当search_type = 0 时 必传
	* @param $data['time_range']      string   N 方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传 时间段
	* @param $data['task_time_range'] string   Y 评估/诊断任务开始结束时间 格式 00:00-24:00
	* @return array
	*/
	public function getJunctionMapData($data) {
		if(empty($data)){
			return [];
		}

		$junction_id = trim($data['junction_id']);

		$result = [];

		// 获取配时数据 地图底图数据源用配时的
		$timing_data = [
			'junction_id'     => $junction_id,
			'dates'           => $data['dates']
		];
		if((int)$data['search_type'] == 1){ // 按方案查询
			$time_range = array_filter(explode('-', $data['time_range']));
			$timing_data['time_range'] = trim($time_range[0]) . '-' . date("H:i", strtotime($time_range[1]) - 60);
		}else{ // 按时间点查询
			$timing_data['time_point'] = trim($data['time_point']);
			$timing_data['time_range'] = trim($data['task_time_range']);
		}

		$timing = $this->timing_model->getTimingDataForJunctionMap($timing_data);
		if(!$timing || empty($timing)){
			return [];
		}

		/*------------------------------------
		| 获取路网路口各相位经纬度及路口中心经纬度 |
		-------------------------------------*/
		// 是否有地图版本
		if(empty($timing['map_version'])){
			return [];
		}
		// 获取路网路口各相位坐标
		$waymap_data = [
			'version'           => trim($timing['map_version']),
			'logic_junction_id' => $junction_id
		];
		$waymap = $this->waymap_model->getJunctionFlowAndCenterLngLat($waymap_data);
		if(!$waymap || empty($waymap)){
			return [];
		}
		foreach($waymap as $k=>$v){
			if(!empty($timing['list'][$v['logic_flow_id']])){
				$result['dataList'][$k]['logic_flow_id'] = $v['logic_flow_id'];
				$result['dataList'][$k]['flow_label'] = $timing['list'][$v['logic_flow_id']];
				$result['dataList'][$k]['lng'] = $v['lng'];
				$result['dataList'][$k]['lat'] = $v['lat'];
			}
		}

		// 获取路口中心坐标
		$result['center'] = '';
		$center_data['logic_id'] = $junction_id;
		$center = $this->waymap_model->getJunctionCenterCoords($center_data);

		$result['center'] = $center;
		$result['map_version'] = $timing['map_version'];

		if(count($result['dataList']) >= 1){
			$result['dataList'] = array_values($result['dataList']);
		}

		return $result;
	}

	/**
	* 获取路口信息用于轨迹
	* @param $data['task_id']     interger 任务ID
	* @param $data['junction_id'] string   路口ID
	* @param $data['flow_id']     string   flow_id
	* @param $data['search_type'] interger 搜索类型 1：按方案时间段 0：按时间点
	* @param $data['time_point']  string   时间点 当search_type = 0 时有此参数
	* @param $data['time_range']  string   时间段 当search_type = 1 时有此参数
	* @return array
	*/
	public function getJunctionInfoForTheTrack($data) {
		if(empty($data)){
			return [];
		}

		$result = [];

		$select = 'task_id, junction_id, dates, start_time, end_time, clock_shift, movements';
		$where  = "task_id = {$data['task_id']} and junction_id = '{$data['junction_id']}'";
		if((int)$data['search_type'] == 1){
			$time_range = explode('-', $data['time_range']);
			$where .= " and type = 1 and start_time = '{$time_range[0]}' and end_time = '{$time_range[1]}'";
		}else{
			$where .= " and type = 0 and time_point = '{$data['time_point']}'";
		}

		$result = $this->db->select($select)
							->from($this->tb)
							->where($where)
							->get();
		if(!$result){
			return [];
		}

		$result = $result->row_array();
		if(isset($result['movements'])){
			$result['movements'] = json_decode($result['movements'], true);
			foreach($result['movements'] as $v){
				if($v['movement_id'] == trim($data['flow_id'])){
					$result['flow_id'] = $v['movement_id'];
					$result['af_condition'] = $v['af_condition'];
					$result['bf_condition'] = $v['bf_condition'];
					$result['num'] = $v['num'];
					unset($result['movements']);
				}
			}
		}

		return $result;
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
			$diagnose_key_conf = $this->config->item('diagnose_key');
			foreach($key as $v){
				if(array_key_exists($v, $diagnose_key_conf)){
					$select .= empty($select) ? $v : ',' . $v;
				}
			}
		}

		return $select;
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
		if(isset($data['count'])){
			$result_data['count'] = $data['count'];
		}
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
		if(!empty($result_data['dataList'])){
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