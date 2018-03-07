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
				'city_id'		=> 'min:1'
			]
		);

		if(!$validate['status']){
			return $this->response(array(), 100400, $validate['errmsg']);
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
				'city_id'		=> 'min:1',
				'dates'			=> 'nullunable',
				'start_time'	=> 'nullunable',
				'end_time'		=> 'nullunable'
			]
		);

		if(!$validate['status']){
			return $this->response(array(), 100400, $validate['errmsg']);
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
	* 获取任务详情
	* @param task_id	Y 任务ID
	* @return json
	*/
	public function getTaskDetail(){
		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'		=> 'min:1'
			]
		);

		if(!$validate['status']){
			return $this->response(array(), 100400, $validate['errmsg']);
		}

		$res = httpPOST($this->config->item('task_interface') . '/create', $data);
		if(!$res){
			return $this->response([], 100500, 'The connection task service failed.');
		}

		return $this->response($res['data']);
	}
}
