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
	* 获取全城路口信息
	* @param task_id		Y 任务ID
	* @param city_id 		Y 城市ID
	* @param type 			Y 指标计算类型 0：统合 1：时间点
	* @param time_point		N 评估时间点 指标计算类型为1时非空
	* @param confidence		Y 置信度 多个用|隔开
	* @param quota_key		Y 指标key
	* @return json
	*/
	public function getAllCityJunctionInfo(){
		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'		=> 'min:1',
				'type'			=> 'min:0',
				'confidence'	=> 'nullunable',
				'quota_key'		=> 'nullunable'
			]
		);
		if(!$validate['status']){
			return $this->response([], 100400, $validate['errmsg']);
		}

		$data['task_id'] = (int)$params['task_id'];
		$data['type'] = (int)$params['type'];
		$data['time_point'] = trim($params['time_point']);

		if($data['type'] == 1 && empty(trim($data['time_point']))){
			return $this->response([], 100400, 'The time_point cannot be empty.');
		}

		$data['confidence'] = array_filter(explode("|", trim($params['confidence'])));
		foreach($data['confidence'] as $v){
			if(!array_key_exists($v, $this->config->item('confidence'))){
				return $this->response([], 100400, 'The value of confidence ' . $v . ' is wrong.');
			}
		}

		$data['quota_key'] = strtolower(trim($params['quota_key']));
		if(!array_key_exists($data['quota_key'], $this->config->item('junction_quota_key'))){
			return $this->response([], 100400, 'The value of quota_key ' . $data['quota_key'] . ' is wrong.');
		}

		$data = $this->junction_model->getAllCityJunctionInfo($data);

		return $this->response($data);
	}

	/**
	* 获取路口详情
	*/
	public function getJunctionDetail(){

	}

	/**
	* 获取全城路口
	* @param city_id 		Y 城市ID
	* @return json
	*/
	public function getAllCityJunctions(){
		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params, ['city_id' => 'min:1']);
		if(!$validate['status']){
			return $this->response([], 100400, $validate['errmsg']);
		}

		$this->load->helper('http');

		$data = [
					'city_id'	=> (int)$params['city_id'],
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
		return $this->response($res['data']);
	}

}