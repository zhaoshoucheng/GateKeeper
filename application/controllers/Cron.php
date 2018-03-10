<?php
/***************************************************************
crontab
解释周期任务配置，生成新的运行任务，设置为等待运行状态
遍历任务状态表，获取等待运行的任务，启动任务
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->helper('http');

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
			if ($task === false) {
				$i ++;
				if ($i === 2) {
					break;
				}
				sleep(10 * 60);
			} else {
				var_dump($task);
				$bRet = $this->run($task);
				if ($bRet === false) {
					$this->task_model->updateTask($task['id'], ['status' => -1, 'task_end_time' => time()]);
				}
			}
		}
	}

	public function run($task) {
		return true;
	}
}
