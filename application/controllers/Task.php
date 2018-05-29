<?php
/***************************************************************
# 周期/自定义任务类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\Task as TaskService;

class Task extends MY_Controller
{
	private $to = 'lizhaohua@didichuxing.com';
	private $subject = 'task scheduler';

	private $task_result = array(
	    101 => '轨迹出错',
	    102 => '配时出错',
	    103 => '任务超时',
	);

	public function __construct(){
		parent::__construct();
		// $this->config->load('nconf');
		// $this->load->helper('http');
		$this->load->helper('mail');

		$this->load->model('cycletask_model');
		$this->load->model('customtask_model');
		$this->load->model('task_model');
		$this->load->model('taskdateversion_model');
	}

	/**
	* 获取任务列表
	* @param city_id	Y 城市ID
	* @param type 		Y 获取任务类型 0：所有 1：周期任务 2：自定义任务
	* @param kind 		Y 获取任务类型 0：所有 1：指标任务 2：诊断任务
	* @return json
	*/
	public function getList(){
		$user = $this->username;

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params, [
				'city_id'		=> 'nullunable',
				'type'			=> 'nullunable',
				'kind'			=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
		}

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
			if ($kind === 0 or $kind === 1 or $kind === 2) {
				$aRet = $this->task_model->getTask($user, $city_id, 1, 2);
				$cycle_task_tmp = array_merge($cycle_task_tmp, $aRet);
			}
		}
		// 自定义任务
		if ($type === 0 or $type === 2) {
			$user = $this->username;
			// 指标任务
			if ($kind === 0 or $kind === 1) {
				$aRet = $this->task_model->getTask($user, $city_id, 2, 1);
				$custom_task_tmp = array_merge($custom_task_tmp, $aRet);
			}
			// 诊断任务
			if ($kind === 0 or $kind === 1 or $kind === 2) {
				$aRet = $this->task_model->getTask($user, $city_id, 2, 2);
				$custom_task_tmp = array_merge($custom_task_tmp, $aRet);
			}
		}
		$cycle_task = array();
		$custom_task = array();
		$run_status = [0, 1, 10, 11];
		foreach ($cycle_task_tmp as $task) {
			$reason = '';
			if (! in_array($task['status'], $run_status)) {
				$reason = '未知原因';
				// 如果任务状态异常
				if ($task['task_comment'] != '' and $task['task_comment'] != null) {
					// 有状态
					$i = intval($task['task_comment']);
					if (isset($this->task_result[$i])) {
						$reason = $this->task_result[$i];
					}
				}
			}
			$cycle_task[] = array(
				'task_id' => $task['id'],
				'dates' => explode(',', $task['dates']),
				'time_range' => $task['start_time'] . '-' . $task['end_time'],
				'junctions' => ($task['junctions'] === '' or $task['junctions'] === null) ? '全城' : '路口',
				'status' => (in_array($task['status'], $run_status)) ? $task['rate'] . '%' : '失败',
				'exec_date' => date('m.d', $task['task_start_time']),
				'reason' => $reason,
			);
		}
		foreach ($custom_task_tmp as $task) {
			$reason = '';
			if (! in_array($task['status'], $run_status)) {
				$reason = '未知原因';
				// 如果任务状态异常
				if ($task['task_comment'] != '' and $task['task_comment'] != null) {
					// 有状态
					$i = intval($task['task_comment']);
					if (isset($this->task_result[$i])) {
						$reason = $this->task_result[$i];
					}
				}
			}
			$custom_task[] = array(
				'task_id' => $task['id'],
				'dates' => explode(',', $task['dates']),
				'time_range' => $task['start_time'] . '-' . $task['end_time'],
				'junctions' => ($task['junctions'] === '' or $task['junctions'] === null) ? '全城' : '路口',
				'status' => (in_array($task['status'], $run_status)) ? $task['rate'] . '%' : '失败',
				'exec_date' => date('m.d', $task['task_start_time']),
				'reason' => $reason,
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
		$user = $this->username;

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params, [
				'city_id'		=> 'nullunable',
				'dates'			=> 'nullunable',
				'start_time'	=> 'nullunable',
				'end_time'		=> 'nullunable',
				'kind'			=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
		}

		$st = strtotime('2000-01-01 ' . $params['start_time'] . ':00');
		$et = strtotime('2000-01-01 ' . $params['end_time'] . ':00');
		if ($st > $et or $et - $st < 1800 or $et - $st > 14400) {
			$this->errno = -1;
			$this->errmsg = '任务开始时间必须小于结束时间，最少30分钟，最多4个小时';
			return;
		}

		$task = [
			'user'		=> $user,
			'city_id'	=> $params['city_id'],
			'dates'		=> $params['dates'],
			'start_time'=> $params['start_time'],
			'end_time'	=> $params['end_time'],
			'kind'		=> $params['kind'],
			'status'    => 1,
		];
		if (isset($params['junctions'])) {
			$task['junctions'] = $params['junctions'];
		}
		$iRet = $this->customtask_model->addTask($task);
		if ($iRet === -1) {
			$this->errno = -1;
			$this->errmsg = '创建任务失败';
			return;
		}


		$taskRst = [
		    'user' => $user,
		    'city_id' => $params['city_id'],
		    'dates' => $params['dates'],
		    'start_time' => $params['start_time'],
		    'end_time' => $params['end_time'],
		    'type' => 2,
		    'kind' => $params['kind'],
		    'conf_id' => $iRet,
		    'rate' => 0,
		    'status' => 0,
		    'expect_try_time' => time(),
		    'try_times' => 0,
		    'created_at' => date('Y-m-d H:i:s'),
		    'updated_at' => date('Y-m-d H:i:s'),
		];
		if (isset($params['junctions'])) {
			$taskRst['junctions'] = $params['junctions'];
		}
		$iRet = $this->task_model->addTask($taskRst);
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
	* @param kind	 	Y 1 指标任务；2 诊断任务
	* @param type	 	Y 1 前一天；2 前一自然周工作日/周末；3 前四个周*
	* @param expect_exec_time	 	N 周期望开始时间 hh:mm:ss
	* @return json
	*/
	public function createCycleTask(){
		$user = 'admin';
		$expect_exec_time = '04:04:04';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params, [
				'city_id'		=> 'nullunable',
				'start_time'	=> 'nullunable',
				'end_time'		=> 'nullunable',
				'kind'			=> 'nullunable',
				'type'			=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
		}

		$task = [
			'user'		=> $user,
			'city_id'	=> $params['city_id'],
			'start_time'=> $params['start_time'],
			'end_time'	=> $params['end_time'],
			'kind'		=> $params['kind'],
			'type'		=> $params['type'],
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
	* 获取最近执行成功的任务id
	* @param city_id	Y 城市ID
	* @return json
	*/
	public function getSuccTask() {
		$user = 'admin';

		$params = $this->input->post();

		// 校验参数
		$validate = Validate::make($params, [
				'city_id'		=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
		}

		$city_id = $params['city_id'];

		$tasks = array();
		$types = [1 => 'last_day', 2 => 'last_week', 3 => 'last_month'];
		foreach ($types as $task_type => $value) {
			$aRet = $this->task_model->getSuccTask($user, $city_id, 1, 2, $task_type);
			$tasks[$value] = [];
			if (!empty($aRet)) {
				$tasks[$value] = [
					'task_id' => $aRet[0]['task_id'],
					'dates' => explode(',', $aRet[0]['dates']),
				];
			} else {
				$tasks[$value] = [];
			}
		}
		$this->output_data = $tasks;
	}

	/**
	* 修改运行任务状态信息
	* @param task_id			Y 任务ID
	* @param rate	 			N 进度
	* @return json
	*/
	public function UpdateTaskRate(){

		$params = $this->input->get();

		// 校验参数
		$validate = Validate::make($params, [
				'task_id'		=> 'nullunable',
				'rate'			=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
		}

		$task_id = $params['task_id'];
		$rate = $params['rate'];

		$task = array();
		$task['rate'] = $rate;
		if (intval($rate == 100)) {
			$task['task_end_time'] = time();
			$content = "{$task_id} succeed.";
			sendMail($this->to, $this->subject, $content);
		}
		$bRet = $this->task_model->updateTask($task_id, $task);
		if ($bRet === false) {
			$this->errno = -1;
			$this->errmsg = '更新任务进度失败';
		}
	}

	/**
	* 修改运行任务状态信息
	* @param task_id			Y 任务ID
	* @param ider	 			N 身份	0 mapflow, 1 calcute
	* @param status	 			N 执行状态，0 待执行/执行中；1 成功；2 失败
	* @param task_comment	 	N 注释
	* @return json
	*/
	public function UpdateTaskStatus(){

		$params = $this->input->get();

		// 校验参数
		$validate = Validate::make($params, [
				'task_id'		=> 'nullunable',
				'ider'			=> 'nullunable',
				'status'		=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
		}

		$task_id = $params['task_id'];
		$ider = $params['ider'];
		$status = $params['status'];
		if (isset($params['task_comment'])) {
			$task_comment = $params['task_comment'];
		} else {
			$task_comment = null;
		}

		if ($status == 2) {
			if ($ider == 0) {
				$content = "{$task_id} mapdata flow failed.";
			} elseif ($ider == 1) {
				$content = "{$task_id} calcute task failed.";
			}
			sendMail($this->to, $this->subject, $content);
		}

		$ider = intval($ider);

		$bRet = $this->task_model->updateTaskStatus($task_id, $ider, $status, $task_comment);
		if ($bRet === false) {
			$this->errno = -1;
			$this->errmsg = '更新任务状态失败';
		}
	}

	public function areaFlowProcess() {
		$params = $this->input->get();

		// 校验参数
		$validate = Validate::make($params, [
				'city_id'		=> 'nullunable',
				'dates'			=> 'nullunable',
			]
		);

		if(!$validate['status']){
			return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
		}

		try {
			$res = array();
			$city_id = $params['city_id'];
			$dates = $params['dates'];
			$ret = $this->task_model->getDateVersion($dates);
			$ret = json_decode($ret, true);
			if ($ret['errorCode'] == -1) {
			    // maptypeversion unready
			    $this->errno = -1;
			    $this->errmsg = 'maptypeversion unready';
			    return;
			}
			$task_id = "CT{$city_id}";
			$trace_id = uniqid();
			$hdfs_dir = "/user/its_bi/its_flow_tool/{$task_id}_{$trace_id}/";
			$dateVersion = $ret['data'];
			$res['errno'] = 0;
			$res['errmsg'] = '';
			foreach ($dateVersion as $date => $version) {
				$res['dateversion'][$date] = $version;
			}
			$res['hdfs_dir'] = $hdfs_dir;
			$res['task_id'] = $task_id;
			$res['trace_id'] = $trace_id;
			print(json_encode($res));
			$this->output_data = $res;
			fastcgi_finish_request();

			$taskService = new TaskService();
			$response = $taskService->areaFlowProcess($city_id, $task_id, $trace_id, $hdfs_dir, array_values(array_unique($dateVersion)));
		} catch (Exception $e) {
			$this->errno = -1;
			$this->errmsg = 'areaFlowProcess failed.';
		}

	}

	public function mapVersionCB() {
		$data = $this->input->raw_input_stream;
        if ($data === null) {
            return;
        }
        $data = @json_decode($data, true);
        if ($data === null or empty($data)) {
            $this->errno = -1;
            $this->errmsg = '参数错误';
            return;
        }

		try {
			$task_id = $data[0]['task_id'];
			$this->taskdateversion_model->delete($task_id);
			$this->taskdateversion_model->insert_batch($data);
		} catch (Exception $e) {
			$this->errno = -1;
			$this->errmsg = 'mapVersionCB failed.';
		}

	}


    /**
     * 获取相关的任务id
     * @param city_id	Y 城市ID
     * @return json
     */
    public function getSameDayTaskId()
    {
        $taskId = intval($this->input->post('task_id', true));

        $task = $this->task_model->getTaskById($taskId);
        if (empty($task)) {
            return $this->response(array(), -1, "task is empty");
        }

        $tasks = $this->task_model->getDayCycleTaskSummary($task['user'], $task['city_id'], date("Y-m-d", strtotime($task['created_at'])));

        $types = [
            1 => 'last_day',
            2 => 'last_week',
            3 => 'last_month'
        ];

        $ret = [];
        foreach ($types as $taskType => $value) {
            $tasksSummary = array_filter($tasks, function($task) use($taskType){
                if ($task['cycleType'] == $taskType) {
                    return true;
                }
                return false;
            });

            if (empty($tasksSummary)) {
                continue;
            }


            $task = array_first($tasksSummary);

            $task_comment = "";
            $run_status = [0, 1, 10, 11];
            if (!empty($task['task_comment']) && isset($this->task_result[$task['task_comment']])) {
                $task_comment = $this->task_result[$task['task_comment']];
            }

            $ret[$value] = [
                'task_id' => $task['task_id'],
                'dates' => explode(',', $task['dates']),
                'reason' => $task_comment,
                'status' => (in_array($task['status'], $run_status)) ? $task['rate'] . '%' : '失败',
            ];
        }
        $this->output_data = $ret;
    }
}
