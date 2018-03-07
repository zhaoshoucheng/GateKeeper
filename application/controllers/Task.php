<?php
/***************************************************************
# 周期/自定义任务类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Task extends MY_Controller {
	public function __construct(){
		parent::__construct();
		$this->config->load('nconf');
		$this->load->helper('http');

		$this->load->model('cycletask_model');
	}

	/**
	* 获取任务列表
	* @param type 		Y 获取任务类型 0：周期任务 1：自定义任务
	* @param city_id	Y 城市ID
	* @return json
	*/
	public function getList(){
		$user = 'ningxiangbing';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'type'			=> 'min:0',
				'city_id'		=> 'main:1'
			]
		);

		if(!$validate['status']){
			return $this->response(array(), $errno, $validate['errmsg']);
		}

		if(array_key_exists($type, $this->config->item('task_type'))){
			return $this->response([], 100400, 'The parameter type value error.');
		}

		$data = [
			'type'		=> (int)$params['type'],
			'city_id'	=> (int)$params['city_id'],
			'user'		=> $user
		];

		$res = httpPOST($this->config->item('task_interface') . '/getlist', $data);
		if(!$res){
			return $this->response([], 100500, 'The connection task service failed.');
		}

		return $this->response($res['data']);
	}

	/**
	* 创建自定义任务
	* @param city_id	Y 城市ID
	* @param dates 		Y 评估日期 多个用逗号隔开
	* @param start_time Y 评估开始时间 00:00
	* @param end_time 	Y 评估结束时间 00:00
	* @return json
	*/
	public function createCustomTask(){
		$user = 'ningxiangbing';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'city_id'		=> 'main:1',
				'dates'			=> 'nullunable',
				'start_time'	=> 'nullunable',
				'end_time'		=> 'nullunable'
			]
		);

		if(!$validate['status']){
			return $this->response(array(), $errno, $validate['errmsg']);
		}

		$data = [
			'user'		=> $user,
			'city_id'	=> $params['city_id'],
			'dates'		=> $params['dates'],
			'start_time'=> $params['start_time'],
			'end_time'	=> $params['end_time'],
			'type'		=> 1,
		];

		$res = httpPOST($this->config->item('task_interface') . '/create', $data);
		if(!$res){
			return $this->response([], 100500, 'The connection task service failed.');
		}

		return $this->response($res['data']);
	}

	/**
	* 创建周期任务
	* @param city_id	Y 城市ID
	* @param start_time Y 评估开始时间 00:00
	* @param end_time 	Y 评估结束时间 00:00
	* @param type	 	Y 周期任务类型 0 前一天；1 前一自然周工作日/周末；2 前四个周*'
	* @param expect_start_time	 	N 周期望开始时间
	* @return json
	*/
	public function createCycleTask(){
		$user = 'admin';
		$expect_start_time = '';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'city_id'		=> 'nullunable',
				'start_time'	=> 'nullunable',
				'end_time'		=> 'nullunable',
				'type'		=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), -1, $validate['errmsg']);
		}

		$task = [
			'user'		=> $user,
			'city_id'	=> $params['city_id'],
			'start_time'=> $params['start_time'],
			'end_time'	=> $params['end_time'],
			'type'		=> $params['type'],
		];

		$bRet = $this->cycletask_model->addTask($task);
		if ($bRet === false) {
			$this->errno = -1;
			$this->errmsg = '创建周期任务失败';
		}
	}
}
