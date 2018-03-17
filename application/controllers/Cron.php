<?php
/***************************************************************
crontab
解释周期任务配置，生成新的运行任务，设置为等待运行状态
遍历任务状态表，获取等待运行的任务，启动任务
*2 * * * * cd /home/xiaoju/webroot/ipd-cloud/application/itstool; /home/xiaoju/php/bin/php index.php cron start > /dev/null 2>&1
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->helper('http');
		$this->load->config('nconf');

		$this->load->model('cycletask_model');
		$this->load->model('customtask_model');
		$this->load->model('task_model');
	}

	public function scan_custom_task() {
		$this->customtask_model->process();
	}

	public function scan_cycle_task() {
		$this->cycletask_model->process();
	}

	public function start() {
		for ($i = 0; ; ) {
			$task = $this->task_model->process();
			if ($task === true or $task === false) {
				$i ++;
				if ($i === 2) {
					break;
				}
				sleep(10 * 60);
			} else {
				var_dump($task);
				try {
					$trace_id = uniqid();
					$hdfs_dir = "/user/its_bi/its_flow_tool/{$task_id}_{$trace_id}/";
					// process_flow
					// process_index
					$task->area_flow_process($city_id, $task_id, $trace_id, $hdfs_dir, array_values($dateVersion));
					$task->caculate($city_id, $task_id, $trace_id, $hdfs_dir, $start_time, $end_time, $dateVersion);
					$bRet = $this->run($task);
					if ($bRet === false) {
						
					}
				} catch (\Exception $e) {
					$this->task_model->updateTask($task['id'], ['status' => -1, 'task_end_time' => time()]);
				}
			}
		}
	}

	public function run($task) {
		// thrift 路网
		// thritf 启动
		sleep(5 * 60);
		return true;
	}
}
