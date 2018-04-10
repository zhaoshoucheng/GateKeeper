<?php
/********************************************
# desc:    配时数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-08
********************************************/

class Timing_model extends CI_Model {

	public function __construct(){
		parent::__construct();

		$this->load->config('nconf');
	}

	/**
	* 获取路口配时信息
	* @param $data['junction_id'] string 逻辑路口ID
	* @param $data['dates']       array  评估/诊断日期
	* @param $data['time_range']  string 时间段 00:00-00:30
	* @return array
	*/
	public function getJunctionsTimingInfo($data){
		if(count($data) < 1){
			return [];
		}

		// 获取配时数据
		$timing = $this->getTimingData($data);

		// 对返回数据格式化,返回需要的格式
		if(count($timing >= 1)){
			$timing = $this->formatTimingData($timing, $data['time_range']);
		}else{
			return [];
		}

		return $timing;
	}

	/**
	* 获取flow_id对应名称的数组，用于匹配相位名称
	* @param $data['junction_id'] string 逻辑路口ID
	* @param $data['dates']       array  评估/诊断日期
	* @param $data['time_range']  string 时间段 00:00-00:30
	* @return array
	*/
	public function getFlowIdToName($data){
		if(count($data) < 1){
			return [];
		}

		// 获取配时数据
		$timing = $this->getTimingData($data);

		// 对返回数据格式化,返回需要的格式
		if(count($timing >= 1)){
			$timing = $this->formatTimingIdToName($timing);
		}else{
			return [];
		}

		return $timing;
	}

	/**
	* 格式化配时数据
	* @param $data
	* @return array
	*/
	private function formatTimingData($data, $time_range){
		// 任务最小时间、最大时间
		$time_range = array_filter(explode('-', $time_range));
		if(empty($time_range[0]) || empty($time_range[1])){
			return [];
		}
		$task_min_time = strtotime($time_range[0]);
		$task_max_time = strtotime($time_range[1]);

		$result = [];
		// 方案总数
		$result['total_plan'] = isset($data['total_plan']) ? $data['total_plan'] : 0;
		$result['map_version'] = isset($data['map_version']) ? $data['map_version'] : 0;

		if(isset($data['latest_plan']) && !empty($data['latest_plan'])){
			foreach($data['latest_plan'] as $k=>$v){
				// 方案列表
				$result['plan_list'][strtotime($v['tod_start_time'])]['id'] = $v['time_plan_id'];
				$result['plan_list'][strtotime($v['tod_start_time'])]['start_time'] = $v['tod_start_time'];
				$result['plan_list'][strtotime($v['tod_start_time'])]['end_time'] = $v['tod_end_time'];

				// 每个方案对应的详情配时详情
				if(isset($v['plan_detail']['extra_timing']['cycle']) && isset($v['plan_detail']['extra_timing']['offset'])){
					$result['timing_detail'][$v['time_plan_id']]['cycle'] = $v['plan_detail']['extra_timing']['cycle'];
					$result['timing_detail'][$v['time_plan_id']]['offset'] = $v['plan_detail']['extra_timing']['offset'];
				}

				if(isset($v['plan_detail']['movement_timing']) && !empty($v['plan_detail']['movement_timing'])){
					foreach($v['plan_detail']['movement_timing'] as $k1=>$v1){
						// 信号灯状态 1=绿灯
						$result['timing_detail'][$v['time_plan_id']]['timing'][$k1]['state'] = isset($v1[0]['state']) ? $v1[0]['state'] : 0;
						// 绿灯开始时间
						$result['timing_detail'][$v['time_plan_id']]['timing'][$k1]['start_time'] = isset($v1[0]['start_time']) ? $v1[0]['start_time'] : 0;
						// 绿灯结束时间
						$result['timing_detail'][$v['time_plan_id']]['timing'][$k1]['duration'] = isset($v1[0]['duration']) ? $v1[0]['duration'] : 0;
						// 逻辑flow id
						$result['timing_detail'][$v['time_plan_id']]['timing'][$k1]['logic_flow_id'] = isset($v1[0]['flow_logic']['logic_flow_id']) ? $v1[0]['flow_logic']['logic_flow_id'] : 0;
						// flow 描述
						$result['timing_detail'][$v['time_plan_id']]['timing'][$k1]['comment'] = isset($v1[0]['flow_logic']['comment']) ? $v1[0]['flow_logic']['comment'] : '';
					}
				}

				if(!empty($result['timing_detail'][$v['time_plan_id']]['timing'])){
                    $result['timing_detail'][$v['time_plan_id']]['timing'] = array_values($result['timing_detail'][$v['time_plan_id']]['timing']);
                }
			}

			// 对方案按时间正序排序并对配时方案开始结束时间对应任务开始结束时间
			if(!empty($result['plan_list'])){
				ksort($result['plan_list']);
				$first = current($result['plan_list']);
				$end = end($result['plan_list']);
				$plan_min_time = strtotime($first['start_time']);
				$plan_max_time = strtotime($end['end_time']);
				if($plan_min_time > $task_min_time || $plan_min_time < $task_min_time){
					$result['plan_list'][strtotime($first['start_time'])]['start_time'] = date("H:i:s", $task_min_time);
				}
				if($plan_max_time > $task_max_time || $plan_max_time < $task_max_time){
					$result['plan_list'][strtotime($end['start_time'])]['end_time'] = date("H:i:s", $task_max_time);
				}

				$result['plan_list'] = array_values($result['plan_list']);
			}
		}

		return $result;
	}

	/**
	* 格式化配时数据 返回flow_id=>name结构
	* @param $data
	* @return array
	*/
	private function formatTimingIdToName($data){
		if(empty($data)){
			return [];
		}

		$result = [];
		if(!empty($data['latest_plan'][0]['plan_detail']['movement_timing'])){
			foreach($data['latest_plan'][0]['plan_detail']['movement_timing'] as $v){
				if(!empty($v[0]['flow_logic']['logic_flow_id']) && !empty($v[0]['flow_logic']['comment'])){
					$result[$v[0]['flow_logic']['logic_flow_id']] = $v[0]['flow_logic']['comment'];
				}
			}
		}

		return $result;
	}

	/**
	* 获取配时数据
	* @param $data['junction_id'] string 逻辑路口ID
	* @param $data['dates']       array  评估/诊断日期
	* @param $data['time_range']  string 时间段 00:00-00:30
	* @return array
	*/
	private function getTimingData($data){
		$time_range = array_filter(explode('-', trim($data['time_range'])));
		$this->load->helper('http');

		// 获取配时详情
		$timing_data = [
						'logic_junction_id'	=> trim($data['junction_id']),
						'days'              => trim(implode(',', $data['dates'])),
						'start_time'        => trim($time_range[0]),
						'end_time'          => trim($time_range[1])
					];
		try {
			$timing = httpGET($this->config->item('timing_interface') . '/signal-mis/TimingService/queryTimingByTimePoint', $timing_data);
			$timing = json_decode($timing, true);
			if(isset($timing['errorCode']) && $timing['errorCode'] != 0){
				// 日志
				return [];
			}
		} catch (Exception $e) {
			return [];
		}

		if(isset($timing['data']) && count($timing['data'] >= 1)){
			return $timing['data'];
		}else{
			return [];
		}
	}
}