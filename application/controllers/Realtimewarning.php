<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Realtimewarning extends Inroute_Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Shanghai');
        parent::__construct();
        $this->load->model('realtimewarning_model');
        $this->load->config('nconf');
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


        //附加执行济南大脑项目
        exec("ps aux | grep \"realtimewarn\" | grep 'jinan_task/{$cityId}' | grep '{$hour}' | grep -v \"grep\" | wc -l", $processOut);
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

        exec("ps aux | grep \"realtimewarn\" | grep 'process/{$cityId}' | grep '{$hour}' | grep -v \"grep\" | wc -l", $processOut);
        $processNum = !empty($processOut[0]) ? $processOut[0] : 0;
        //执行任务
        $command = "";
        if ($processNum == 0) {
            $logPath = $this->config->item('log_path');

            $phpPath = "/home/xiaoju/php7/bin/php -c /home/xiaoju/php7/etc/php.ini ";
            if (gethostname() == 'ipd-cloud-server01.gz01') {
                $phpPath = "php ";
            }
            $command = "nohup {$phpPath} index.php realtimewarning process/{$cityId}/{$hour}/{$date}/{$traceId}/{$uid} >>" .
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

    public function process($cityId = '12', $hour = '00:00', $date = "", $traceId = "", $uid = "")
    {
        ini_set('memory_limit', '-1');
        sleep(10);
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
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||message=task_handler doing\n\r";
        $res = httpGET($this->config->item('realtime_callback')."/task_handler", $params, 600000);
        if (!$res) {
            //如果执行任务失败则直接退出
            com_log_warning('realtime_callback_task_handler_error', 0, $res, compact("params"));
            exit;
        }
        $res = json_decode($res, true);
        if($res['errno'] != 0){
            //如果执行任务失败则直接退出
            com_log_warning('realtime_callback_task_handler_error_errno', $res['errno'], $res, compact("params"));
            exit;
        }

        //临时附加线上地址
        //httpGET("http://10.85.128.81:30101/task_handler", $params, 600000);

        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||message=task_handler done\n\r";
        // echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||message=processing\n\r";
        // $this->realtimewarning_model->process($cityId, $date, $hour, $traceId);
        // echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||message=processed\n\r";
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||message=calculating\n\r";
        $this->realtimewarning_model->calculate($cityId, $date, $hour, $traceId);
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour=" . $hour . "||date=" . $date . "||trace_id=" . $traceId . "||message=calculated\n\r";
        return true;
    }
}
