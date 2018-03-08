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
		$this->load->model('task_model');
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
	* @param type 		Y 1 周期任务；2 自定义任务
	* @param kind 		Y 1 指标任务；2 诊断任务
	* @return json
	*/
	public function createCustomTask(){
		$user = 'admin';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'city_id'		=> 'nullunable',
				'dates'			=> 'nullunable',
				'start_time'	=> 'nullunable',
				'end_time'		=> 'nullunable',
				'type'			=> 'nullunable',
				'kind'			=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), $errno, $validate['errmsg']);
		}

		$task = [
			'user'		=> $user,
			'city_id'	=> $params['city_id'],
			'dates'		=> $params['dates'],
			'start_time'=> $params['start_time'],
			'end_time'	=> $params['end_time'],
			'type'		=> $params['type'],
			'kind'		=> $params['kind'],
		];

		$iRet = $this->task_model->addTask($task);
		if ($iRet === -1) {
			$this->errno = -1;
			$this->errmsg = '创建任务失败';
		} else {
			$this->output_data = [
				'task_id' => $iRet,
			];
		}
	}

	/**
	* 创建周期任务
	* @param city_id	Y 城市ID
	* @param start_time Y 评估开始时间 00:00
	* @param end_time 	Y 评估结束时间 00:00
	* @param type	 	Y 1 前一天；2 前一自然周工作日/周末；3 前四个周*
	* @param expect_start_time	 	N 周期望开始时间 hh:mm:ss ms
	* @return json
	*/
	public function createCycleTask(){
		$user = 'admin';
		$expect_start_time = '02:00:00 0';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'city_id'		=> 'nullunable',
				'start_time'	=> 'nullunable',
				'end_time'		=> 'nullunable',
				'type'			=> 'nullunable',
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
			'expect_start_time'		=> $expect_start_time,
		];
		if (isset($params['expect_start_time'])) {
			$task['expect_start_time'] = $params['expect_start_time'];
		}

		$iRet = $this->cycletask_model->addTask($task);
		if ($iRet === -1) {
			$this->errno = -1;
			$this->errmsg = '创建周期任务失败';
		} else {
			$this->output_data = [
				'cycle_conf_id' => $iRet,
			];
		}
	}

	/**
	* 修改运行任务状态信息
	* @param task_id			Y 任务ID
	* @param task_start_time 	N 任务实际开始时间
	* @param task_end_time 		N 任务实际结束时间
	* @param rate	 			N 进度
	* @param status	 			N 状态
	* @param task_comment	 	N 注释
	* @return json
	*/
	public function UpdateTaskStatus(){

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'		=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), -1, $validate['errmsg']);
		}

		$task_id = $params['task_id'];

		$task = array();
		$keys = ['task_start_time', 'task_end_time', 'rate', 'status', 'task_comment'];
		foreach ($keys as $key) {
			if (isset($params[$key])) {
				$task[$key] = $params[$key];
			}
		}

		$bRet = $this->task_model->updateTask($task_id, $task);
		if ($bRet === false) {
			$this->errno = -1;
			$this->errmsg = '创建周期任务失败';
		}
	}
}
