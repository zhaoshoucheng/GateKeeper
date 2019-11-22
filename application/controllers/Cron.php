<?php
/***************************************************************
 * crontab
 * 解释周期任务配置，生成新的运行任务，设置为等待运行状态
 * 遍历任务状态表，获取等待运行的任务，启动任务
 *2 * * * * cd /home/xiaoju/webroot/ipd-cloud/application/itstool; /home/xiaoju/php/bin/php index.php cron start > /dev/null 2>&1
 ***************************************************************/


defined('BASEPATH') OR exit('No direct script access allowed');

use Didi\Cloud\ItsMap\Task;
use Services\TimingAdaptionAreaService;
use Services\RoadService;

/**
 * Class Cron
 * @property \Redis_model          $redis_model
 * @property \Adapt_model          $adapt_model
 */
class Cron extends CI_Controller
{
    protected $roadService;

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
        $this->load->model('openCity_model');
        $this->load->model('flowDurationV6_model');
        $this->load->model('realtime_model');
        $this->load->model('adapt_model');

        $this->roadService = new RoadService();
    }

    public function deleteAdaptLog()
    {
        print_r(date("Y-m-d H:i:s")." deleteAdaptLog execute success.\n");
        $this->adapt_model->deleteAdaptLog("-1 day");
    }

    public function scan_custom_task()
    {
        $this->customtask_model->process();
    }

    public function scan_cycle_task()
    {
        $this->cycletask_model->process();
    }

    public function del_old_offline_data($cityIdsStr, $date)
    {
        if ($cityIdsStr == -1) {
            $cities = $this->openCity_model->getCities();
            $cityIds = [];
            foreach ($cities as $city) {
                $cityIds[] = $city['city_id'];
            }
        } else {
            $cityIds = explode(",", $cityIdsStr);
        }
        $offset = 1000;
        foreach ($cityIds as $cityId) {
            $cnt = $this->flowDurationV6_model->getOldQuotaDataCnt($cityId, $date);
            if ($cnt == 0) {
                continue;
            }
            var_dump($cityId);
            var_dump($cnt);
            for ($i = 0; $i < intval($cnt / $offset) + 1; $i ++) {
                $res = $this->flowDurationV6_model->delOldQuotaData($cityId, $date, $offset);
                if (empty($res)) {
                    return;
                }
            }
        }
    }

    public function del_old_realtime_data($cityIdsStr, $days)
    {
        $date = date('Y-m-d', strtotime("-{$days} days"));
        var_dump($date);
        if ($cityIdsStr == -1) {
            $cities = $this->openCity_model->getCities();
            $cityIds = [];
            foreach ($cities as $city) {
                $cityIds[] = $city['city_id'];
            }
        } else {
            $cityIds = explode(",", $cityIdsStr);
        }
        $offset = 500;
        foreach ($cityIds as $cityId) {
            $cnt = $this->realtime_model->getOutdateRealtimeDataCnt($cityId, $date);
            if ($cnt == 0) {
                continue;
            }
            var_dump($cityId);
            var_dump($cnt);
            for ($i = 0; $i < intval($cnt / $offset); $i ++) {
                $ret = $this->realtime_model->delOutdateRealtimeData($cityId, $date, $offset);
                if (empty($ret)) {
                    return;
                }
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

    // public function writecache(){
    //     //新实时数据城市
    //     //读取开城列表
    //     $this->config->load('cron', TRUE);
    //     $cityIds = $this->config->item('city_ids', 'cron');
    //     $date = date("Y-m-d");
    //     foreach ($cityIds as $cityID) {
    //         $todayJamCurveKey = "new_its_realtime_today_jam_curve_{$cityID}_{$date}";
    //         $groupIds = $this->userperm_model->getUserPermAllGroupid();
    //         if (empty($groupIds)) {
    //             com_log_warning("getUserPermAllGroupid_Empty", 0, "", array("groupids" => $groupIds));
    //         }
    //         foreach ($groupIds as $groupId) {
                
    //         }
    //     }
    // }

    //缓存近7天路口总数方法
    public function getOfflineJunctionNum(){
        $cityIds = ["38"];
        $cityValue = ["38"=>1007];
        foreach ($cityIds as $cityId) {
            echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||message=begin getOfflineJunctionNum\n\r";

            $url = $this->config->item('data_service_interface');
            $url = sprintf("%s/getOfflineJunctionNum?city_id=%s&date=%s",$url,$cityId,date("Y-m-d",strtotime("-7 day")));
            $retJson = httpGET($url);
            $ret = json_decode($retJson,true);
            if(isset($ret["data"]["count"]) && $ret["data"]["count"]>200){
                $redisKey = sprintf("itstool_offline_juncnum_%s",$cityId);
                $countValue = $ret["data"]["count"];
                $countValue = $cityValue[$cityId];
                $this->redis_model->setData($redisKey,$countValue);
                echo sprintf("%s=%s\n",$redisKey,$countValue);
            }
            echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||message=finish getOfflineJunctionNum\n\r";
        }
    }

    /**
     * 缓存指标数据到redis中
     * @param int $cityId
     * @param int $areaId
     * @param string $quotaKey
     */
    public function getAllRoadDetailCache()
    {
        $this->config->load('cron', TRUE);
        $cityIds = $this->config->item('all_city_ids', 'cron');
        foreach ($cityIds as $cityId) {
            echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||message=begin getAllRoadDetailCache\n\r";
            $params = [
                "city_id" => $cityId,
                "show_type" =>1, 
                "force" => 1,
            ];
            $this->roadService->getAllRoadDetail($params);
            $params = [
                "city_id" => $cityId,
                "show_type" =>0,
                "force" => 1,
            ];
            $this->roadService->getAllRoadDetail($params);
            echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||message=finish getAllRoadDetailCache\n\r";
        }
    }

    /**
     * 缓存指标数据到redis中
     * @param int $cityId
     * @param int $areaId
     * @param string $quotaKey
     */
    public function getAreaQuotaInfo(string $quotaKey)
    {
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " quota_key=" . $quotaKey . "||message=begin getAreaQuotaInfo\n\r";
        //读取开城列表
        $this->config->load('cron', TRUE);
        $cityIds = $this->config->item('city_ids', 'cron');
        foreach ($cityIds as $cityId){
            $taaService = new TimingAdaptionAreaService();
            $areaList = $taaService->getAreaList(["city_id"=>$cityId]);
            foreach ($areaList as $areaItem){
                $areaId = $areaItem["id"];
                // 获取每个区域的路口ID串
                $jdata = [
                    'city_id' => $cityId,
                    'area_id' => $areaId,
                ];
                try {
                    $junctions = $taaService->getAreaJunctionList($jdata);
                    if(empty($junctions["dataList"])){
                        continue;
                    }
                    $esJunctionIds = implode(',', array_filter(array_column($junctions["dataList"], 'logic_junction_id')));
                    $helperService = new \Services\HelperService();
                    $lastHour = $helperService->getIndexLastestHour($cityId);
                    $esTime = date('Y-m-d H:i:s', strtotime($lastHour));
                    $avgQuotaKeyConf = $this->config->item('avg_quota_key');
                    $EsquotaKey = $avgQuotaKeyConf[$quotaKey]['esColumn'];
                    $quotaInfo = $this->realtime_model->getEsAreaQuotaValue($cityId, $esJunctionIds, $esTime, $EsquotaKey);
                }catch(\Exception $e) {
                    com_log_warning('_itstool_getAreaQuotaInfo_error', 0, $e->getMessage(), compact("cityId","areaId", "quotaKey"));
                    echo "[WARNING] " . date("Y-m-d\TH:i:s") . " quota_key=" . $quotaKey . "||city_id={$cityId}||area_id={$areaId}||message={$e->getMessage()}\n\r";
                    continue;
                }
                $areaQuotaInfoKey = sprintf("itstool_area_quotainfo_%s_%s_%s",date("Y-m-d"),$areaId,$quotaKey);
                $this->redis_model->rPush($areaQuotaInfoKey,json_encode(current($quotaInfo)));
                $this->redis_model->setExpire($areaQuotaInfoKey,24*3600);
            }
        }
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " quota_key=" . $quotaKey . "||message=end getAreaQuotaInfo\n\r";
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
                        $message = sprintf("check %s failure. content:%s",json_encode($item),$ret);
                        throw new Exception($message, 1);
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

                    $message = sprintf("check %s success.",json_encode($item));
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
                    throw new Exception("city_id={$city_id} realTimeAlarmListCount={$realTimeAlarmListCount} junctionSurveyAlarmTotal={$junctionSurveyAlarmTotal} union checker false", 1);
                }
                if($realTimeAlarmListCount==0 && $junctionSurveyAlarmTotal>0){
                    throw new Exception("city_id={$city_id} realTimeAlarmListCount={$realTimeAlarmListCount} junctionSurveyAlarmTotal={$junctionSurveyAlarmTotal} union checker false", 1);
                }
                //高峰时段数据异常,放到monitor处理了
                if(in_array(date("H"), [8,9,10,17,18,19,20])){
                    if($city_id==12 && $junctionTotal<=400){
                        //throw new Exception("city_id={$city_id} junctionTotal={$junctionTotal} [早8-10 晚5-8] checker false", 1);
                    }
                    if($city_id==1 && $junctionTotal<=3000){
                        //throw new Exception("city_id={$city_id} junctionTotal={$junctionTotal} [早8-10 晚5-8] checker false", 1);
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
                $host = gethostname();
                echo "[ERROR] " . date("Y-m-d\TH:i:s") . " message={$message}\n\r";
                $data = array('msgtype' => 'text', 'text' => array('content' => "兜底数据写入报警: ".$message." host={$host}"));
                //禁用丁丁报警
                //httpPOST($webhook, $data, 0, 'json');
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

    public function testding() {
        $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=8d7a45fd3a5a4b7758c55f790fd85aef10fb43130be60d2797a3fd6ee80f9403';
        $message = 'Just for testing, please ignore this message.';
        $data = array('msgtype' => 'text', 'text' => array('content' => $message));
        $this->load->helper('http');
        httpPOST($webhook, $data, 0, 'json');
    }

    public function dailycheck() {
        $webhook = 'https://oapi.dingtalk.com/robot/send?access_token=8d7a45fd3a5a4b7758c55f790fd85aef10fb43130be60d2797a3fd6ee80f9403';

        $message = $this->task_model->dailycheck();
        if (!empty($message)) {
            $data = array('msgtype' => 'text', 'text' => array('content' => $message));
            $this->load->helper('http');
            httpPOST($webhook, $data, 0, 'json');
        }
    }
}
