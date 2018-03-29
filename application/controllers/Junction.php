<?php
/***************************************************************
# 路口类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Junction extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->model('junction_model');
		$this->load->config('nconf');
	}

	/**
	* 评估-获取全城路口信息
	* @param task_id     interger  Y 任务ID
	* @param city_id     interger  Y 城市ID
	* @param type        interger  Y 指标计算类型 1：统合 0：时间点
	* @param time_point  string    N 评估时间点 指标计算类型为1时非空
	* @param confidence  interger  Y 置信度 0:全部 1:高 2:低
	* @param quota_key   string    Y 指标key
	* @return json
	*/
	public function getAllCityJunctionInfo(){
		$params = $this->input->post();
		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'    => 'min:1',
				'type'       => 'min:0',
				'city_id'    => 'min:1',
				'quota_key'  => 'nullunable',
				'confidence' => 'min:0'
			]
		);
		if(!$validate['status']){
			return $this->response([], 100400, $validate['errmsg']);
		}

		$data['task_id'] = (int)$params['task_id'];
		$data['type'] = (int)$params['type'];
		$data['city_id'] = $params['city_id'];

		if($data['type'] == 0 && (!isset($params['time_point']) || empty(trim($params['time_point'])))){
			return $this->response([], 100400, 'The time_point cannot be empty.');
		}
		if($data['type'] == 0){
			$data['time_point'] = trim($params['time_point']);
		}

		if(!array_key_exists($params['confidence'], $this->config->item('confidence'))){
			return $this->response([], 100400, 'The value of confidence ' . $params['confidence'] . ' is wrong.');
		}
		$data['confidence'] = $params['confidence'];

		$data['quota_key'] = strtolower(trim($params['quota_key']));
		if(!array_key_exists($data['quota_key'], $this->config->item('junction_quota_key'))){
			return $this->response([], 100400, 'The value of quota_key ' . $data['quota_key'] . ' is wrong.');
		}

		$data = $this->junction_model->getAllCityJunctionInfo($data);

		return $this->response($data);
	}

	/**
	* 获取路口指标详情
	* @param task_id      interger Y 任务ID
	* @param dates        string   Y 评估/诊断日期
	* @param junction_id  string   Y 逻辑路口ID
	* @param time_point   string   Y 时间点
	* @param type         interger Y 详情类型 1：指标详情页 2：诊断详情页
	* @return json
	*/
	public function getJunctionQuotaDetail(){
		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'     => 'min:1',
				'junction_id' => 'nullunable',
				'time_point'  => 'nullunable',
				'type'        => 'min:1'
			]
		);

		if(!$validate['status']){
			return $this->response([], 100400, $validate['errmsg']);
		}

		if(!is_array($params['dates']) || count($params['dates']) < 1){
			return $this->response([], 100400, 'The dates cannot be empty and must be array.');
		}

		// 获取路口指标详情
		$res = $this->junction_model->getFlowQuotas($params);
		if(count($res) < 1){
			return [];
		}

		return $this->response($res);
	}

	/**
	* 获取配时方案及配时详情
	* @param dates        string Y 评估/诊断日期
	* @param junction_id  string Y 路口ID
	* @param time_point   string Y 时间点
	* @param time_range   string Y 时间段
	* @return json
	*/
	public function getJunctionTiming(){
		$params = $this->input->post();
		// 校验参数
		$validate = Validate::make($params,
			[
				'junction_id' => 'nullunable',
				'time_point'  => 'nullunable',
				'time_range'  => 'nullunable'
			]
		);
		if(!$validate['status']){
			return $this->response([], 100400, $validate['errmsg']);
		}

		if(!is_array($params['dates']) || count($params['dates']) < 1){
			return $this->response([], 100400, 'The dates cannot be empty and must be array.');
		}

		$time_range = array_filter(explode('-', trim($params['time_range'])));

		$this->load->helper('http');

		// 获取配时详情
		$timing_data = [
						'logic_junction_id'	=>trim($params['junction_id']),
						'days'              =>trim(implode(',', $params['dates'])),
						'time'              =>trim($params['time_point']),
						'start_time'        =>trim($time_range[0]),
						'end_time'          =>trim($time_range[1])
					];
		$timing = httpGET($this->config->item('timing_interface') . '/signal-mis/TimingService/queryTimingByTimePoint', $timing_data);
		if(!$timing){
			return $this->response([], 100500, 'Failed to connect to timing service.');
		}
		$timing = json_decode($timing, true);
		if($timing['errorCode'] != 0){
			return $this->response([], 100500, $timing['errorMsg']);
		}

		// 格式化配时数据
		$timing_result = $this->formatTimingData($timing['data']);
		if($this->debug){
			echo "interface : " . $this->config->item('timing_interface') . '/signal-mis/TimingService/queryTimingByTimePoint';
			echo "<hr>data : " . json_encode($timing_data);
			echo "<hr>return :" . json_encode($timing);
			echo "<hr>json_decode(return) : <pre>";print_r($timing);
			echo "<hr> result_data : ";print_r($timing_result);
			exit;
		}
		return $this->response($timing_result);
	}

	/**
	* 诊断-获取全城路口诊断问题列表
	* @param task_id        interger  Y 任务ID
	* @param city_id        interger  Y 城市ID
	* @param type           interger  Y 指标计算类型 1：统合 0：时间点
	* @param time_point     string    N 时间点 指标计算类型为1时非空
	* @param confidence     interger  Y 置信度 0:全部 1:高 2:低
	* @param diagnose_key	string    Y 诊断key
	* @return json
	*/
	public function getAllCityJunctionsDiagnoseList(){
		$params = $this->input->post();
		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'    => 'min:1',
				'city_id'    => 'min:1',
				'type'       => 'min:0',
				'confidence' => 'min:0'
			]
		);
		if(!$validate['status']){
			return $this->response([], 100400, $validate['errmsg']);
		}

		$data['task_id'] = (int)$params['task_id'];
		$data['city_id'] = $params['city_id'];
		$data['type'] = (int)$params['type'];

		if($data['type'] == 0 && (!isset($params['time_point']) || empty(trim($params['time_point'])))){
			return $this->response([], 100400, 'The time_point cannot be empty.');
		}
		if($data['type'] == 0){
			$data['time_point'] = trim($params['time_point']);
		}

		if(!array_key_exists($params['confidence'], $this->config->item('confidence'))){
			return $this->response([], 100400, 'The value of confidence ' . $params['confidence'] . ' is wrong.');
		}
		$data['confidence'] = $params['confidence'];

		$data['diagnose_key'] = $params['diagnose_key'];
		if(is_array($data['diagnose_key']) && count($data['diagnose_key']) >= 1){
			foreach($data['diagnose_key'] as $v){
				if(!array_key_exists($v, $this->config->item('diagnose_key'))){
					return $this->response([], 100400, 'The value of diagnose_key ' . $v . ' is wrong.');
				}
			}
		}else{
			return $this->response([], 100400, 'The diagnose_key cannot be empty and must be array.');
		}

		$res = $this->junction_model->getJunctionsDiagnoseList($data);

		return $this->response($res);

	}

	/**
	* 诊断-诊断问题排序列表
	* @param task_id       interger Y 任务ID
	* @param city_id       interger Y 城市ID
	* @param time_point    string   Y 时间点
	* @param diagnose_key  string   Y 诊断key
	* @param orderby       interger N 诊断问题排序 1：正序 2：倒序 默认2
	* @return json
	*/
	public function getDiagnoseRankList(){
		$params = $this->input->post();
		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'    => 'min:1',
				'time_point' => 'nullunable',
				'city_id'    => 'min:1'
			]
		);
		if(!$validate['status']){
			return $this->response([], 100400, $validate['errmsg']);
		}

		$data['orderby'] = 2;
		if(isset($params['orderby']) && (int)$params['orderby'] == 1){
			$data['orderby'] = 1;
		}

		$data['task_id'] = (int)$params['task_id'];
		$data['time_point'] = trim($params['time_point']);

		$res = [];

		$diagnose_key = $params['diagnose_key'];
		$diagnose_key_conf = $this->config->item('diagnose_key');
		if(is_array($diagnose_key) && count($diagnose_key) >= 1){
			foreach($diagnose_key as $k=>$v){
				if(!array_key_exists($v, $diagnose_key_conf)){
					return $this->response([], 100400, 'The value of diagnose_key ' . $v . ' is wrong.');
				}
				$data['diagnose_key'] = $v;
				$res[$v] = $this->junction_model->getDiagnoseRankList($data);
			}
		}else{
			return $this->response([], 100400, 'The diagnose_key cannot be empty and must be array.');
		}

		return $this->response($res);
	}

	/**
	* 获取路口地图绘制数据
	* @param junction_id  string Y 逻辑路口ID
	* @param dates        string Y 评估/诊断时间
	* @param time_point   string Y 时间点
	* @return json
	*/
	public function getJunctionMapData(){
		$params = $this->input->post();
		// 校验参数
		$validate = Validate::make($params,
			[
				'junction_id' => 'nullunable',
				'time_point'  => 'nullunable'
			]
		);
		if(!$validate['status']){
			return $this->response([], 100400, $validate['errmsg']);
		}

		if(!is_array($params['dates']) || count($params['dates']) < 1){
			return $this->response([], 100400, 'The dates cannot be empty and must be array.');
		}

		// 获取配时信息，组织地图version flow_id=>flow_label
		$this->load->helper('http');
		$phase_data = [
						'logic_junction_id'	=>trim($params['junction_id']),
						'days'				=>trim(implode(',', $params['dates'])),
						'time'				=>trim($params['time_point'])
					];

		$timing = httpGET($this->config->item('timing_interface') . '/signal-mis/TimingService/queryTimingByTimePoint', $phase_data);
		if(!$timing){
			return $this->response([], 100500, 'Failed to connect to timing service.');
		}
		$timing = json_decode($timing, true);
		if($timing['errorCode'] != 0){
			if($this->debug){
				$timing['errorMsg'] = "interface : " . $this->config->item('timing_interface') . '/signal-mis/TimingService/queryTimingByTimePoint' . ' & data : ' . json_encode($phase_data) . ' & return : ' . json_encode($timing);
			}
			return $this->response([], 100500, $timing['errorMsg']);
		}
		if(count($timing['data']['latest_plan']) < 1){
			return $this->response([]);
		}

		// flow_id => flow_label
		$position = ['东'=>1, '西'=>2, '南'=>3, '北'=>4];
		$turn = ['直'=>1, '左'=>2, '右'=>3];
		$phase_position = [];
		$temp_arr = [];
		foreach($timing['data']['latest_plan'][0]['plan_detail']['movement_timing'] as $k=>$v){
			$comment = $v[0]['flow_logic']['comment'];
			foreach($position as $k1=>$v1){
				foreach($turn as $k2=>$v2){
					if(stristr($comment, $k1.$k2) !== false){
						$temp_arr[$k1][str_replace($k1.$k2, $v1.$v2, $comment)]['logic_flow_id'] = $v[0]['flow_logic']['logic_flow_id'];
						$temp_arr[$k1][str_replace($k1.$k2, $v1.$v2, $comment)]['comment'] = $comment;
					}
				}
			}
		}

		foreach ($temp_arr as $key => &$value) {
			ksort($value);
			reset($value);
			$arr1 = current($value);
			$phase_position[$arr1['logic_flow_id']] = mb_substr($arr1['comment'], 0, 1, "utf-8");
		}

		$waymap_token = $this->config->item('waymap_token');
		// 获取路网数据
		$data['version'] = $timing['data']['map_version'];
		$data['logic_junction_id'] = trim($params['junction_id']);
		$data['token'] = $waymap_token;
		$map = httpGET($this->config->item('waymap_interface') . '/flow-duration/mapFlow/AllByJunctionWithLinkAttr', $data);
		if(!$map){
			return $this->response($data, 100500, 'Failed to connect to waymap service.');
		}
		$map = json_decode($map, true);
		if($map['errorCode'] != 0){
			if($this->debug){
				$map['errorMsg'] = "map--interface : " . $this->config->item('waymap_interface') . '/flow-duration/mapFlow/AllByJunctionWithLinkAttr' . ' & data : ' . json_encode($data) . ' & return : ' . json_encode($map);
			}
			return $this->response([], 100500, $map['errorMsg']);
		}

		$result = [];
		foreach($map['data'] as $k=>$v){
			if(isset($phase_position[$v['logic_flow_id']]) && !empty($phase_position[$v['logic_flow_id']])){
				$result['dataList'][$k]['logic_flow_id'] = $v['logic_flow_id'];
				$result['dataList'][$k]['flow_label'] = $phase_position[$v['logic_flow_id']];
				$result['dataList'][$k]['lng'] = $v['inlink_info']['s_node']['lng'];
				$result['dataList'][$k]['lat'] = $v['inlink_info']['s_node']['lat'];
			}
		}

		$result['center'] = '';
		// 获取路口详情--获取路口中心点坐标
		$junction_info = httpGET($this->config->item('waymap_interface') . '/flow-duration/map/detail', ['logic_id'=>trim($params['junction_id']), 'token'=>$waymap_token]);
		$junction_info = json_decode($junction_info, true);
		if($junction_info['errorCode'] == 0 && count($junction_info['data']) >= 1){
			$result['center']['lng'] = $junction_info['data']['lng'];
			$result['center']['lat'] = $junction_info['data']['lat'];
		}
		if(count($result['dataList']) >= 1){
			$result['dataList'] = array_values($result['dataList']);
		}

		return $this->response($result);
	}

	/**
	* 格式化配时数据
	*/
	private function formatTimingData($data){
		$result = [];
		$result['total_plan'] = $data['total_plan'];
		$result['tod_start_time'] = $data['latest_plan'][0]['tod_start_time'];
		$result['tod_end_time'] = $data['latest_plan'][0]['tod_end_time'];
		$result['cycle'] = $data['latest_plan'][0]['plan_detail']['extra_timing']['cycle'];
		$result['offset'] = $data['latest_plan'][0]['plan_detail']['extra_timing']['offset'];

		foreach($data['latest_plan'][0]['plan_detail']['movement_timing'] as $k=>$v){
			$result['timing_detail'][$k]['logic_flow_id'] = $v[0]['flow_logic']['logic_flow_id'];
			$result['timing_detail'][$k]['state'] = $v[0]['state'];
			$result['timing_detail'][$k]['start_time'] = $v[0]['start_time'];
			$result['timing_detail'][$k]['duration'] = $v[0]['duration'];
			$result['timing_detail'][$k]['comment'] = $v[0]['flow_logic']['comment'];
		}

		$result['timing_detail'] = array_values($result['timing_detail']);
		return $result;
	}

	/**
	* 测试登录
	*/
	public function testLogin(){
		echo "welcome!";
		exit;
	}
}
