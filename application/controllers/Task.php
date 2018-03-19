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
		// $this->config->load('nconf');
		// $this->load->helper('http');

		$this->load->model('cycletask_model');
		$this->load->model('customtask_model');
		$this->load->model('task_model');
	}

	/**
	* 获取任务列表
	* @param city_id	Y 城市ID
	* @param type 		Y 获取任务类型 0：所有 1：周期任务 2：自定义任务
	* @param kind 		Y 获取任务类型 0：所有 1：指标任务 2：诊断任务
	* @return json
	*/
	public function getList(){
		$user = 'admin';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'city_id'		=> 'nullunable',
				'type'			=> 'nullunable',
				'kind'			=> 'nullunable',
			]
		);

		$city_id = intval($params['city_id']);
		$type = intval($params['type']);
		$kind = intval($params['kind']);

		$cycle_task_tmp = array();
		$custom_task_tmp = array();
		// 周期任务
		if ($type === 0 or $type === 1) {
			$user = 'admin';
			// 指标任务
			if ($kind === 0 or $kind === 1) {
				$aRet = $this->task_model->getTask($user, $city_id, 1, 1);
				$cycle_task_tmp = array_merge($cycle_task_tmp, $aRet);
			}
			// 诊断任务
			if ($kind === 0 or $kind === 2) {
				$aRet = $this->task_model->getTask($user, $city_id, 1, 2);
				$cycle_task_tmp = array_merge($cycle_task_tmp, $aRet);
			}
		}
		// 自定义任务
		if ($type === 0 or $type === 2) {
			if (isset($params['user'])) {
				$user = $params['user'];
				// 指标任务
				if ($kind === 0 or $kind === 1) {
					$aRet = $this->task_model->getTask($user, $city_id, 2, 1);
					$custom_task_tmp = array_merge($custom_task_tmp, $aRet);
				}
				// 诊断任务
				if ($kind === 0 or $kind === 2) {
					$aRet = $this->task_model->getTask($user, $city_id, 2, 2);
					$custom_task_tmp = array_merge($custom_task_tmp, $aRet);
				}
			}
		}
		$cycle_task = array();
		$custom_task = array();
		foreach ($cycle_task_tmp as $task) {
			$cycle_task[] = array(
				'task_id' => $task['id'],
				'dates' => explode(',', $task['dates']),
				'time_range' => $task['start_time'] . '-' . $task['end_time'],
				'junctions' => ($task['junctions'] === '' or $task['junctions'] === null) ? '全城' : '路口',
				'status' => ($task['status'] == -1) ? '失败' : $task['rate'] . '%',
				'exec_date' => date('m.d', $task['task_start_time']),
			);
		}
		foreach ($custom_task_tmp as $task) {
			$custom_task[] = array(
				'task_id' => $task['id'],
				'dates' => explode(',', $task['dates']),
				'time_range' => $task['start_time'] . '-' . $task['end_time'],
				'junctions' => ($task['junctions'] === '' or $task['junctions'] === null) ? '全城' : '路口',
				'status' => ($task['status'] == -1) ? '失败' : $task['rate'] . '%',
				'exec_date' => date('m.d', $task['task_start_time']),
			);
		}
		$this->output_data = [
			'cycle_task' => $cycle_task,
			'custom_task' => $custom_task,
		];
	}

	/**
	* 创建自定义任务
	* @param city_id	Y 城市ID
	* @param dates 		Y 评估日期 多个用逗号隔开
	* @param start_time Y 评估开始时间 00:00
	* @param end_time 	Y 评估结束时间 00:00
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
			'kind'		=> $params['kind'],
		];
		if (isset($params['junctions'])) {
			$task['junctions'] = $params['junctions'];
		}

		$iRet = $this->customtask_model->addTask($task);
		if ($iRet === -1) {
			$this->errno = -1;
			$this->errmsg = '创建任务失败';
		} else {
			$this->output_data = [
				'custom_conf_id' => $iRet,
			];
		}
	}

	/**
	* 创建周期任务
	* @param city_id	Y 城市ID
	* @param dates 		Y 评估日期 多个用逗号隔开
	* @param start_time Y 评估开始时间 00:00
	* @param end_time 	Y 评估结束时间 00:00
	* @param type	 	Y 1 前一天；2 前一自然周工作日/周末；3 前四个周*
	* @param expect_exec_time	 	N 周期望开始时间 hh:mm:ss
	* @return json
	*/
	public function createCycleTask(){
		$user = 'admin';
		$expect_exec_time = '02:00:00';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'city_id'		=> 'nullunable',
				'dates'			=> 'nullunable',
				'start_time'	=> 'nullunable',
				'end_time'		=> 'nullunable',
				'kind'			=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), -1, $validate['errmsg']);
		}

		$task = [
			'user'		=> $user,
			'city_id'	=> $params['city_id'],
			'dates'	=> $params['dates'],
			'start_time'=> $params['start_time'],
			'end_time'	=> $params['end_time'],
			'kind'		=> $params['kind'],
			'expect_exec_time'		=> $expect_exec_time,
		];
		if (isset($params['expect_exec_time'])) {
			$task['expect_exec_time'] = $params['expect_exec_time'];
		}
		if (isset($params['junctions'])) {
			$task['junctions'] = $params['junctions'];
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
	* @param rate	 			N 进度
	* @return json
	*/
	public function UpdateTaskRate(){
		$user = 'admin';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'		=> 'nullunable',
				'rate'			=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), -1, $validate['errmsg']);
		}

		$task_id = $params['task_id'];
		$rate = $params['rate'];

		// $task = array();
		// $keys = ['task_start_time', 'task_end_time', 'rate', 'status', 'task_comment'];
		// foreach ($keys as $key) {
		// 	if (isset($params[$key])) {
		// 		$task[$key] = $params[$key];
		// 	}
		// }

		$bRet = $this->task_model->updateTask($task_id, ['rate' => $rate]);
		if ($bRet === false) {
			$this->errno = -1;
			$this->errmsg = '更新任务进度失败';
		}
	}

	/**
	* 修改运行任务状态信息
	* @param task_id			Y 任务ID
	* @param ider	 			N 身份	0 mapflow, 1 calcute
	* @param status	 			N 执行状态，0 待执行；1 执行中；2 成功；-1 失败
	* @param task_comment	 	N 注释
	* @return json
	*/
	public function UpdateTaskStatus(){
		$user = 'admin';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'task_id'		=> 'nullunable',
				'ider'			=> 'nullunable',
				'status'		=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), -1, $validate['errmsg']);
		}

		$task_id = $params['task_id'];
		$ider = $params['ider'];
		$status = $params['status'];
		$task_comment = $params['task_comment'];

		// $ider_to_id = [
		// 	'mapflow' => 0,
		// 	'calcute' => 1,
		// ];
		// if (isset($ider_to_id[$ider])) {
		// 	$ider = $ider_to_id[$ider];
		// } else {
		// 	$this->errno = -1;
		// 	$this->errmsg = '参数错误';
		// }
		$ider = intval($ider);

		$bRet = $this->task_model->updateTaskStatus($task_id, $ider, $status, $task_comment);
		if ($bRet === false) {
			$this->errno = -1;
			$this->errmsg = '创建周期任务失败';
		}
	}

	/**
	* 获取最近执行成功的任务id
	* @param city_id	Y 城市ID
	* @return json
	*/
	public function getSuccTask() {
		$user = 'admin';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params,
			[
				'city_id'		=> 'nullunable',
			]
		);

		$city_id = $params['city_id'];

		$tasks = array();
		$types = [1 => 'last_day', 2 => 'last_week', 3 => 'last_month'];
		foreach ($types as $task_type => $value) {
			$aRet = $this->task_model->getSuccTask($user, $city_id, 1, 2, $task_type);
			if (!empty($aRet)) {
				$tasks[$value] = [
					'task_id' => $aRet[0]['conf_id'],
					'dates' => $aRet[0]['dates'],
				];
			} else {
				$tasks[$value] = [];
			}
		}
		$this->output_data = $tasks;
	}
}
