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
	* @param $data['time_point']  string 时间点
	* @param $data['time_range']  string 时间段 00:00-00:30
	* @return array
	*/
	public function getJunctionsTimingInfo($data){
		if(count($data) < 1){
			return [];
		}

		$time_range = array_filter(explode('-', trim($data['time_range'])));
		$this->load->helper('http');

		// 获取配时详情
		$timing_data = [
						'logic_junction_id'	=> trim($data['junction_id']),
						'days'              => trim(implode(',', $data['dates'])),
						'time'              => trim($data['time_point']),
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

		// 对返回数据格式化,返回需要的格式
		if(isset($timing['data']) && count($timing['data'] >= 1)){
			$timing = $this->formatTimingData($timing['data']);
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
	private function formatTimingData($data){
		$result = [];
		// 方案总数
		$result['total_plan'] = isset($data['total_plan']) ? $data['total_plan'] : 0;

		if(isset($data['data']['latest_plan']) && !empty($data['data']['latest_plan'])){
			foreach($data['data']['latest_plan'] as $k=>$v){
				// 方案列表
				$result['plan_list'][$k]['id'] = $v['time_plan_id'];
				$result['plan_list'][$k]['start_time'] = $v['tod_start_time'];
				$result['plan_list'][$k]['end_time'] = $v['tod_end_time'];

				// 每个方案对应的详情配时详情
				if(isset($v['plan_detail']['extra_timing']['cycle']) && isset($v['plan_detail']['extra_timing']['offset'])){
					$result['timing_detail'][$v['time_plan_id']]['cycle'] = $v['plan_detail']['extra_timing']['cycle'];
					$result['timing_detail'][$v['time_plan_id']]['offset'] = $v['plan_detail']['extra_timing']['offset'];
				}

				if(isset($v['plan_detail']['movement_timing']) && !empty($v['plan_detail']['movement_timing'])){
					foreach($v['plan_detail']['movement_timing'] as $k1=>$v1){
						// 信号灯状态 1=绿灯
						$result['timing_detail'][$v['time_plan_id']]['state'] = isset($v1[0]['state']) ? $v1[0]['state'] : 0;
						// 绿灯开始时间
						$result['timing_detail'][$v['time_plan_id']]['start_time'] = isset($v1[0]['start_time']) ? $v1[0]['start_time'] : 0;
						// 绿灯结束时间
						$result['timing_detail'][$v['time_plan_id']]['duration'] = isset($v1[0]['duration']) ? $v1[0]['duration'] : 0;
						// 逻辑flow id
						$result['timing_detail'][$v['time_plan_id']]['logic_flow_id'] = isset($v1[0]['flow_logic']['logic_flow_id']) ? $v1[0]['flow_logic']['logic_flow_id'] : 0;
						// flow 描述
						$result['timing_detail'][$v['time_plan_id']]['comment'] = isset($v1[0]['flow_logic']['comment']) ? $v1[0]['flow_logic']['comment'] : '';
					}
				}
			}
		}

		return $result;
	}
}