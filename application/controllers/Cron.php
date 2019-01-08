<?php
/***************************************************************
 * crontab
 * 解释周期任务配置，生成新的运行任务，设置为等待运行状态
 * 遍历任务状态表，获取等待运行的任务，启动任务
 *2 * * * * cd /home/xiaoju/webroot/ipd-cloud/application/itstool; /home/xiaoju/php/bin/php index.php cron start > /dev/null 2>&1
 ***************************************************************/


defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\Task;

class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('http');
        $this->load->config('nconf');

        $this->load->model('cycletask_model');
        $this->load->model('customtask_model');
        $this->load->model('task_model');
        $this->load->model('taskdateversion_model');
        $this->load->model('downgrade_model');
        $this->load->model('opencity_model');
        $this->load->model('flowdurationv6_model');
    }

    public function scan_custom_task()
    {
        $this->customtask_model->process();
    }

    public function scan_cycle_task()
    {
        $this->cycletask_model->process();
    }

    public function del_old_offline_data($cityIds, $date)
    {
        if ($cityIds == -1) {
            $cities = $this->opencity_model->getCities();
        } else {
            $cities = explode(",", $cityIds);
        }
        $offset = 1000;
        foreach ($cities as $city) {
            $cnt = $this->flowdurationv6_model->getOldQuotaDataCnt($city, $date);
            if ($cnt == 0) {
                continue;
            }
            for ($i = 0; $i < intval($cnt / $offset); $i ++) {
                $this->flowdurationv6_model->delOldQuotaData($city, $date, $offset);
            }
        } 
    }

    public function del_old_realtime_data($cityIds, $days)
    {
        $date = date('Y-m-d', strtotime("-{$days} days"));
        if ($cityIds == -1) {
            $cities = $this->opencity_model->getCities();
        } else {
            $cities = explode(",", $cityIds);
        }
        $offset = 1000;
        foreach ($cities as $city) {
            $cnt = $this->realtime_model->getOutdateRealtimeDataCnt($city, $date);
            if ($cnt == 0) {
                continue;
            }
            for ($i = 0; $i < intval($cnt / $offset); $i ++) {
                $ret = $this->realtime_model->delOutdateRealtimeData($city, $date, $offset);
            }
        } 
    }

    public function start()
    {
        for ($i = 0; ;) {
            $task = $this->task_model->process();
            if ($task === true or $task === false) {
                $i++;
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
                    com_log_warning('_its_task_failed', -1, 'thrift failed', array_merge($task, ['trace_id' => $trace_id, 'message' => $e]));
                    // todo 失败分类，路网or计算thrift调用失败，计入task_comment，便于排查问题
                    $this->task_model->updateTask($task_id, ['status' => -1, 'task_comment' => 100, 'task_end_time' => time()]);
                }
                exit();
            }
        }
    }

    public function rerun()
    {
        $this->task_model->rerun();
    }

    public function test()
    {
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

    public function mailTest()
    {
        $this->load->helper('mail');
        $to = 'lizhaohua@didichuxing.com';
        $subject = 'for test';
        $content = 'test helpser sendMail';
        $ret = sendMail($to, $subject, $content);
        var_dump($ret);
    }

    public function logTest()
    {
        log_message('error', "hello failed");
        log_message('notice', "hello failed");
        log_message('debug', "hello failed");
    }

    public function downgradeWrite()
    {
        //清空（擦除）缓冲区并关闭输出缓冲
        if (!empty(ob_get_status())){
            ob_end_clean();
        }

        $this->load->helper('http');
        $this->config->load('cron', TRUE);
        $checkItems = $this->config->item('checkItems', 'cron');
        $webhook = $this->config->item('webhook', 'cron');
        $token = $this->config->item('token', 'cron');
        $city_ids = $this->config->item('city_ids', 'cron');
        $basedir = $this->config->item('basedir', 'cron');
        $baseUrl = $this->config->item('base_url', 'cron');
        $app_id = $this->config->item('app_id', 'cron');
        $secret = $this->config->item('secret', 'cron');

        foreach ($city_ids as $city_id) {
            try {
                if($this->downgrade_model->isOpen($city_id)){
                    $message="city_downgrade is open continue";
                    echo "[INFO] " . date("Y-m-d\TH:i:s") . " message={$message}\n\r";
                    continue;
                }
                $all = [];
                foreach ($checkItems as $item) {
                    $url = sprintf("%s/%s", $baseUrl,$item['url']);
                    $item['baseUrl'] = $baseUrl;
                    if (isset($item['params']['city_id'])) {
                        $item['params']['city_id'] = $city_id;
                    }
                    $params = $item['params'];
                 	$params['ts'] = time();
                 	$params['app_id'] = $app_id;
                    $sign = $this->genSign($params, $secret);
                 	$params['sign'] = $sign;
                    if (strtoupper($item['method']) === 'GET') {
                        $ret = httpGET($url, $params);
                        if ($ret === false) {
                            throw new Exception($url . json_encode($params)." get content exception ", 1);
                        }
                    } elseif (strtoupper($item['method']) === 'POST') {
                        $ret = httpPOST($url, $params);
                        if ($ret === false) {
                            throw new Exception($url . json_encode($params)." get content exception ", 1);
                            break;
                        }
                    } else {
                        throw new Exception(json_encode($item)." not support method ", 1);
                    }

                    //checker
                    $checker = $item['checker'];
                    if(!$checker($ret)){
                        throw new Exception(json_encode($item)." checker false", 1);
                    }

                    //设置缓存时间
                    $retArr = json_decode($ret,true);
                    $retArr['cache_time'] = date("Y-m-d H:i:s");
                    $all[] = [
                        'method' => strtoupper($item['method']),
                        'url' => $item['url'],
                        'params' => $item['params'],
                        'data' => json_encode($retArr),
                    ];

                    $message = sprintf("get %s success.",json_encode($item));
                    echo "[INFO] " . date("Y-m-d\TH:i:s") . " message={$message}\n\r";
                }
                if (!file_exists($basedir)) {
                    if (mkdir($basedir, 0777, true) === false) {
                        throw new Exception("mkdir {$basedir} failed", 1);
                    }
                }

                //union checker
                global $realTimeAlarmListCount,$junctionSurveyAlarmTotal,$junctionTotal;
                if($realTimeAlarmListCount>0 && $junctionSurveyAlarmTotal==0){
                    throw new Exception("city_id={$city_id} union checker false", 1);
                }
                if($realTimeAlarmListCount==0 && $junctionSurveyAlarmTotal>0){
                    throw new Exception("city_id={$city_id} union checker false", 1);
                }
                //高峰时段数据异常
                if(in_array(date("H"), [8,9,10,17,18,19,20])){
                    if($city_id==12 && $junctionTotal<=400){
                        throw new Exception("city_id={$city_id} junctionTotal={$junctionTotal} [早8-10 晚5-8] checker false", 1);
                    }
                    if($city_id==1 && $junctionTotal<=3000){
                        throw new Exception("city_id={$city_id} junctionTotal={$junctionTotal} [早8-10 晚5-8] checker false", 1);
                    }
                }

                foreach ($all as $one) {
                    $file = $this->downgrade_model->getCacheFileName($one['url'], $one['method'], $one['params']);
                    if (file_put_contents($basedir . $file, $one['data']) === false) {
                        throw new Exception("file_put_contents {$basedir}{$one['data']} failed", 1);
                    }
                    $message = $file." write success";
                    echo "[INFO] " . date("Y-m-d\TH:i:s") . " message={$message}\n\r";
                }
                $message = "city_id={$city_id} 写入成功!";
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " message={$message}\n\r";
            } catch (Exception $e) {
                $message = $e->getMessage();
                echo "[ERROR] " . date("Y-m-d\TH:i:s") . " message={$message}\n\r";
                $data = array('msgtype' => 'text', 'text' => array('content' => "兜底数据写入报警: ".$message));
                httpPOST($webhook, $data, 0, 'json');
                com_log_warning('downgradeWrite_error', 0, $e->getMessage());
                continue;
            }
        }
        $message = "全部写入成功!";
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " message={$message}\n\r";
    }

	private function genSign($params, $secret) {
		ksort($params);
		$query_str = http_build_query($params);
		$sign = substr(md5($query_str . "&" . $secret), 7, 16);
		return $sign;
	}

	private function checkSign($params, $secret) {
		if (abs(time() - $params['ts']) > 3) {
			return false;
		}
		$client_sign = $params['sign'];
		unset($params['sign']);
		$server_sign = $this->genSign($params, $secret);
		return $client_sign = $client_sign;
	}

    public function testding()
    {
        $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=8d7a45fd3a5a4b7758c55f790fd85aef10fb43130be60d2797a3fd6ee80f9403';
        $message = 'Just for testing, please ignore this message.';
        $data = array('msgtype' => 'text', 'text' => array('content' => $message));
        $this->load->helper('http');
        httpPOST($webhook, $data, 0, 'json');
    }
}
