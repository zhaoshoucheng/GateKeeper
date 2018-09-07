<?php
/***************************************************************
crontab
解释周期任务配置，生成新的运行任务，设置为等待运行状态
遍历任务状态表，获取等待运行的任务，启动任务
*2 * * * * cd /home/xiaoju/webroot/ipd-cloud/application/itstool; /home/xiaoju/php/bin/php index.php cron start > /dev/null 2>&1
***************************************************************/


defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\Task;

class Cron extends CI_Controller
{
	public function __construct(){
		parent::__construct();
		$this->load->helper('http');
		$this->load->config('nconf');

		$this->load->model('cycletask_model');
		$this->load->model('customtask_model');
		$this->load->model('task_model');
		$this->load->model('taskdateversion_model');
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
				if ($i === 5) {
					break;
				}
				sleep(1 * 60);
			} else {
				print_r($task);
				com_log_notice('_its_task', $task);
				try {
					$trace_id = uniqid();
					$task_id = $task['id'];
					$city_id = $task['city_id'];
					$start_time = $task['start_time'];
					$end_time = $task['end_time'];
					$user = $task['user'];
					$timingType = '1';
					$back_timing_roll = $this->config->item('back_timing_roll');
					if (in_array($user, $back_timing_roll)) {
					    $timingType = '2';
					}
					$dateVersion = $task['dateVersion'];
					$hdfs_dir = "/user/its_bi/its_flow_tool/{$task_id}_{$trace_id}/";
					$this->task_model->updateTask($task['id'], ['trace_id' => $trace_id]);
					// process_flow
					$task = new Task();
					$response = $task->areaFlowProcess($city_id, $task_id, $trace_id, $hdfs_dir, array_values(array_unique($dateVersion)));
					print_r($response);
					// process_index
					$response = $task->calculate($city_id, $task_id, $trace_id, $hdfs_dir, $start_time . ':00', $end_time . ':00', $dateVersion, $timingType);
					print_r($response);
				} catch (\Exception $e) {
					com_log_warning('_its_task_failed',  -1, 'thrift failed', array_merge($task, ['trace_id' => $trace_id, 'message' => $e]));
					// todo 失败分类，路网or计算thrift调用失败，计入task_comment，便于排查问题
					$this->task_model->updateTask($task_id, ['status' => -1, 'task_comment' => 100, 'task_end_time' => time()]);
				}
				exit();
			}
		}
	}

	public function test() {
		$city_id = strval(12);
		$trace_id = uniqid();
		$task_id = '123456';
		$start_time = '06:00:00';
		$end_time = '10:00:00';
		$hdfs_dir = "/user/its_bi/its_flow_tool/{$task_id}_{$trace_id}/";
		$dateVersion = [
			'2018-03-25' => '2018031110',
		];

		$task = new Task();
		$task->areaFlowProcess($city_id, $task_id, $trace_id, $hdfs_dir, array_values($dateVersion));
		$dateVersion = [
			'2018-03-25' => '2018031110',
		];
		$task->calculate($city_id, $task_id, $trace_id, $hdfs_dir, $start_time, $end_time, $dateVersion);
	}

	public function mailTest() {
		$this->load->helper('mail');
		$to = 'lizhaohua@didichuxing.com';
		$subject = 'for test';
		$content = 'test helpser sendMail';
		$ret = sendMail($to, $subject, $content);
		var_dump($ret);
	}

	public function logTest() {
		log_message('error', "hello failed");
		log_message('notice', "hello failed");
		log_message('debug', "hello failed");
	}

	public function cron() {
		$this->load->helper('http');
		$this->config->load('cron', TRUE);
        $checkItems = $this->config->item('checkItems', 'cron');
        $webHook = $this->config->item('webHook', 'cron');
        $token = $this->config->item('token', 'cron');
        $city_ids = $this->config->item('city_ids', 'cron');
        $basedir = $this->config->item('basedir', 'cron');

		foreach ($city_ids as $city_id) {
			try {
				$all = [];
				foreach ($checkItems as $item) {
					$data = array ('msgtype' => 'text','text' => array ('content' => json_encode($item)));
					$data_string = json_encode($data);
					httpPOST($webhook, $data_string);
					if (isset($item['params']['city_id'])) {
						$item['params']['city_id'] = $city_id;
					}
					if (strtoupper($item['method']) === 'GET') {
						$ret = httpGET($item['url'], array_merge($item['params'], $token));
						if ($ret === false) {
							throw new Exception($item['url'] .  json_encode($item['params']), 1);
						}
					} elseif (strtoupper($item['method']) === 'POST') {
						$ret = httpPOST($item['url'], array_merge($item['params'], $token));
						if ($ret === false) {
							throw new Exception($item['url'] .  json_encode($item['params']), 1);
							break;
						}
					} else {
						throw new Exception(json_encode($item), 1);
					}
					$all[] = [
						'method' => strtoupper($item['method']),
						'url' => $item['url'],
						'params' => $item['params'],
						'data' => $ret,
					];
				}
				if (!file_exists($basedir)) {
					if (mkdir($basedir, 0777, true) === false) {
						throw new Exception("mkdir {$basedir} failed", 1);
					}
				}
				foreach ($all as $one) {
					$file = $this->getCacheFileName($one['method'], $one['url'], $one['params']);
					if (file_put_contents($basedir . $file, $one['data']) === false) {
						throw new Exception("file_put_contents {$basedir}{$one['data']} failed", 1);
					}
				}
			} catch (Exception $e) {
				$message = $e->getMessage();
				var_dump($message);
				$data = array ('msgtype' => 'text','text' => array ('content' => $message));
				$data_string = json_encode($data);
				httpPOST($webhook, $data_string);
				continue;
			}

		}
	}

	// /home/xiaoju/webroot/cache/itstool/
	private function getCacheFileName($method, $url, $params) {
		$method = strtoupper($method);
		ksort($params);
		$data = http_build_query($params);
		return md5($method . $url . $data) . '.json';
	}
}
