<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Realtimewarning
 * @property \Realtimewarning_model $realtimewarning_model
 * @property \Common_model $common_model
 */
class Realtimewarning extends Inroute_Controller
{
    private $quotaCityIds = [];
    public function __construct()
    {
        date_default_timezone_set('Asia/Shanghai');
        parent::__construct();
        $this->load->model('realtimewarning_model');
        $this->load->config('nconf');
        $this->load->model('redis_model');
        $this->load->model('common_model');
        $this->quotaCityIds = $this->common_model->getV5DMPCityID();
    }

    public function getNewHour(){
        $params   = array_merge($this->input->get(), $this->input->post());
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'uid' => 'min:1',
        ]);
        if (!$validate['status']) {
            $output = [
                'errno' => ERR_PARAMETERS,
                'errmsg' => $validate['errmsg'],
            ];
            echo json_encode($output);
            return;
        }

        $hour = $this->redis_model->getHour($params["city_id"]);
        $output = [
            'errno' => ERR_SUCCESS,
            'errmsg' => "",
            'data' => $hour,
        ];
        echo json_encode($output);
    }

    public function callback()
    {
        $params   = array_merge($this->input->get(), $this->input->post());
        $validate = Validate::make($params, [
            'hour' => 'min:1',
            'date' => 'min:1',
            'city_id' => 'min:1',
            'trace_id' => 'min:1',
            'uid' => 'min:1',
        ]);
        if (!$validate['status']) {
            $output = [
                'errno' => ERR_PARAMETERS,
                'errmsg' => $validate['errmsg'],
            ];
            echo json_encode($output);
            return;
        }

        //权限验证
        if (ENVIRONMENT != 'development') {
            $this->authToken($params);
        }
        $hour    = $params["hour"];
        $date    = $params["date"];
        $cityId  = $params["city_id"];
        $traceId = $params["trace_id"];
        $uid     = $params["uid"];

        //参数强校验
        if (!preg_match('/^\d{1,2}:\d{1,2}:\d{1,2}$/ims', $hour)) {
            echo "hour 必须为时间点! \n";
            exit;
        }
        if (!preg_match('/^\d{4,4}-\d{1,2}-\d{1,2}$/ims', $date)) {
            echo "date 必须为日期! \n";
            exit;
        }
        if (!is_numeric($cityId)) {
            echo "cityId 必须为数字! \n";
            exit;
        }
        if (!preg_match('/^\S+$/ims', $traceId)) {
            echo "trace_id 必须为非空字符串! \n";
            exit;
        }

        //附加执行济南大脑项目
        exec("ps aux | grep \"realtimewarn\" | grep 'jinan_task/{$cityId}/' | grep '{$hour}' | grep -v \"grep\" | wc -l", $processOut);
        $processNum = !empty($processOut[0]) ? $processOut[0] : 0;
        //执行任务
        $command = "";
        if ($processNum == 0) {
            $logPath = $this->config->item('log_path');

            $phpPath = "/home/xiaoju/php7/bin/php -c /home/xiaoju/php7/etc/php.ini ";
            if (gethostname() == 'ipd-cloud-server01.gz01') {
                $phpPath = "php ";
            }
            $command = "nohup {$phpPath}  /home/xiaoju/webroot/ipd-cloud/application/jinan-city-brain/itstool/index.php realtimewarning jinan_task/{$cityId}/{$hour}/{$date}/{$traceId}/{$uid} >>" .
                "{$logPath}jinan_task_realtimewarning.log  2>&1 &";
            exec($command);
        }


        $output = [
            'errno' => ERR_SUCCESS,
            'errmsg' => "",
            'command' => $command,
            'traceid' => $traceId,
        ];
        echo json_encode($output);
        return;
    }

    /**
     * 拦截 特殊城市且非指定ip请求
     * @param $cityID
     * @return bool
     */
    private function alarmV2Auth($cityID){
        $cityIDs = $this->config->item('alarm_v2_city_ids');
        $clientIPs = $this->config->item('alarm_v2_client_ips');
        $remoteIP = $_SERVER["REMOTE_ADDR"];
        if(in_array($cityID,$cityIDs) && !in_array($remoteIP,$clientIPs)){
            return false;
        }
        return true;
    }

    /**
     * 根据城市返回 报警处理方法
     * @param $cityID
     * @return string
     */
    private function getAlarmFuncName($cityID){
        $cityIDs = $this->config->item('alarm_v2_city_ids');
        if(in_array($cityID,$cityIDs)){
            return "esworker";
        }else{
            return "prepare";
        }
    }

    public function escallback()
    {
        $params   = array_merge($this->input->get(), $this->input->post());
        $validate = Validate::make($params, [
            'hour' => 'min:1',
            'date' => 'min:1',
            'city_id' => 'min:1',
            'trace_id' => 'min:1',
            'uid' => 'min:1',
        ]);
        if (!$validate['status']) {
            $output = [
                'errno' => ERR_PARAMETERS,
                'errmsg' => $validate['errmsg'],
            ];
            echo json_encode($output);
            return;
        }

        //权限验证
        if (ENVIRONMENT != 'development') {
            $this->authToken($params);
        }
        $hour    = $params["hour"];
        $date    = $params["date"];
        $cityId  = $params["city_id"];
        $traceId = $params["trace_id"];
        $uid     = $params["uid"];

        //新版报警通知限定指定ip
        if(!$this->alarmV2Auth($cityId)){
            $output = [
                'errno' => ERR_PARAMETERS,
                'errmsg' => "alarmV2Auth",
            ];
            echo json_encode($output);
            return;
        }

        //参数强校验
        if (!preg_match('/\d{1,2}:\d{1,2}:\d{1,2}/ims', $hour)) {
            echo "hour 必须为时间点! \n";
            exit;
        }
        if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims', $date)) {
            echo "date 必须为日期! \n";
            exit;
        }
        if (!is_numeric($cityId)) {
            echo "cityId 必须为数字! \n";
            exit;
        }
        if (!preg_match('/\S+/ims', $traceId)) {
            echo "trace_id 必须为非空字符串! \n";
            exit;
        }

        // 实时报警城市验证
        $quotaCityIds = $this->quotaCityIds;
        if(in_array($cityId,$quotaCityIds) && !in_array($uid,["traj_index_pro","traffic_realtime_alarm"])){
            echo "uid 非预期! \n";
            exit;
        }

        $alarmFunction = $this->getAlarmFuncName($cityId);
        exec("ps aux | grep \"realtimewarn\" | grep '{$alarmFunction}/{$cityId}/' | grep -v \"grep\" | wc -l", $processOut);
        $processNum = !empty($processOut[0]) ? $processOut[0] : 0;
        //执行任务
        $command = "";
        if ($processNum == 0) {
            $logPath = $this->config->item('log_path');

            $phpPath = "/home/xiaoju/php7/bin/php -c /home/xiaoju/php7/etc/php.ini ";
            if (gethostname() == 'ipd-cloud-server01.gz01') {
                $phpPath = "php ";
            }
            $command = "nohup {$phpPath} index.php realtimewarning {$alarmFunction}/{$cityId}/{$hour}/{$date}/{$traceId}/{$uid} >>" .
                "{$logPath}realtimewarning.log  2>&1 &";
            exec($command);
        }

        $output = [
            'errno' => ERR_SUCCESS,
            'errmsg' => "",
            'command' => $command,
            'traceid' => $traceId,
        ];
        echo json_encode($output);
        return;
    }

    public function esworker($cityId = '12', $hour = '00:00', $date = "", $traceId = "", $uid = "")
    {
        ini_set('memory_limit', '-1');
        ob_end_flush();
        date_default_timezone_set('Asia/Shanghai');
        if (!is_numeric($cityId)) {
            echo "cityId 必须为数字! \n";
            exit;
        }
        if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims', $date)) {
            echo "date 必须为日期! \n";
            exit;
        }
        //预先计算平均延误数据
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=pre_calculating\n\r";
        $this->realtimewarning_model->calculate($cityId, $date, $hour, $traceId, 1);
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=pre_calculated\n\r";

        //再计算报警数据
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=calculating\n\r";
        $this->realtimewarning_model->calculate($cityId, $date, $hour, $traceId);
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=calculated\n\r";
        return true;
    }



    public function prepare($cityId = '12', $hour = '00:00', $date = "", $traceId = "", $uid = "")
    {
        ini_set('memory_limit', '-1');
        ob_end_flush();
        date_default_timezone_set('Asia/Shanghai');
        if (!is_numeric($cityId)) {
            echo "cityId 必须为数字! \n";
            exit;
        }
        if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims', $date)) {
            echo "date 必须为日期! \n";
            exit;
        }
        //预先计算平均延误数据
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=pre_calculating\n\r";
        $this->realtimewarning_model->calculate($cityId, $date, $hour, $traceId, 1);

        exec("ps aux | grep \"realtimewarn\" | grep 'process/{$cityId}/' | grep -v \"grep\" | wc -l", $processOut);
        $processNum = !empty($processOut[0]) ? $processOut[0] : 0;
        //执行任务
        if ($processNum == 0) {
            $logPath = $this->config->item('log_path');

            $phpPath = "/home/xiaoju/php7/bin/php -c /home/xiaoju/php7/etc/php.ini ";
            if (gethostname() == 'ipd-cloud-server01.gz01') {
                $phpPath = "php ";
            }
            $command = "nohup {$phpPath} index.php realtimewarning process/{$cityId}/{$hour}/{$date}/{$traceId}/{$uid} >>" .
                "{$logPath}realtimewarning.log  2>&1 &";
            exec($command);
            echo $command."\n\r";
        }
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=pre_calculated\n\r";
        return true;
    }

    public function process($cityId = '12', $hour = '00:00', $date = "", $traceId = "", $uid = "")
    {
        ini_set('memory_limit', '-1');
        ob_end_flush();
        date_default_timezone_set('Asia/Shanghai');
        if (!is_numeric($cityId)) {
            echo "cityId 必须为数字! \n";
            exit;
        }
        if (!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims', $date)) {
            echo "date 必须为日期! \n";
            exit;
        }

        //回调历史报警接口
        $params = [
            'hour' => $hour,
            'date' => $date,
            'city_id' => $cityId,
            'trace_id' => $traceId,
            'uid' => $uid,
        ];
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=task_handler doing\n\r";
        $res = httpGET($this->config->item('realtime_callback')."/task_handler", $params, 600000);
        if (!$res) {
            com_log_warning('realtime_callback_task_handler_error', 0, $res, compact("params"));
            exit;
        }
        $res = json_decode($res, true);
        if($res['errno'] != 0){
            com_log_warning('realtime_callback_task_handler_error_errno', $res['errno'], $res, compact("params"));
            exit;
        }

        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=task_handler done\n\r";
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=calculating\n\r";
        $this->realtimewarning_model->calculate($cityId, $date, $hour, $traceId);
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=calculated\n\r";
        return true;
    }
}
