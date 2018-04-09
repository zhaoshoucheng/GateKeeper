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
		$result['total_plan'] = $data['total_plan'];
		$result['tod_start_time'] = $data['latest_plan'][0]['tod_start_time'];
		$result['tod_end_time'] = $data['latest_plan'][0]['tod_end_time'];
		$result['cycle'] = $data['latest_plan'][0]['plan_detail']['extra_timing']['cycle'];
		$result['offset'] = $data['latest_plan'][0]['plan_detail']['extra_timing']['offset'];
		if(isset($data['latest_plan']) && count($data['latest_plan'][0]['plan_detail']['movement_timing']) >= 1){
			foreach($data['latest_plan'][0]['plan_detail']['movement_timing'] as $k=>$v){
				$result['timing_detail'][$k]['logic_flow_id'] = $v[0]['flow_logic']['logic_flow_id'];
				$result['timing_detail'][$k]['state'] = $v[0]['state'];
				$result['timing_detail'][$k]['start_time'] = $v[0]['start_time'];
				$result['timing_detail'][$k]['duration'] = $v[0]['duration'];
				$result['timing_detail'][$k]['comment'] = $v[0]['flow_logic']['comment'];
			}
		}
		if(isset($result['timing_detail'])){
			$result['timing_detail'] = array_values($result['timing_detail']);
		}

		return $result;
	}
}