<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Realtimewarning extends CI_Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Shanghai');
        parent::__construct();
        $this->load->model('realtimewarning_model');
    }

    public function callback()
    {
        $params = array_merge($this->input->get(), $this->input->post());
        $validate = Validate::make($params, [
            'hour' => 'min:1',
            'date' => 'min:1',
            'city_id' => 'min:1',
            'trace_id' => 'min:1',
            'uid' => 'min:1',
        ]);
        if (!$validate['status']) {
            $output = array(
                'errno' => ERR_PARAMETERS,
                'errmsg' => $validate['errmsg'],
            );
            echo json_encode($output);
            return;
        }

        $hour = $params["hour"];
        $date = $params["date"];
        $cityId = $params["city_id"];
        $traceId = $params["trace_id"];
        $uid = $params["uid"];

        //查询当前是否执行任务?
        exec("ps aux | grep \"realtimewarn\" | grep 'process/{$cityId}' | grep '{$hour}' | grep -v \"grep\" | wc -l", $processOut);
        $processNum = !empty($processOut[0]) ? $processOut[0] : 0;

        //执行任务
        $command = "";
        if ($processNum == 0) {
            $logPath = $this->config->item('log_path');

            $phpPath = "/home/xiaoju/php7/bin/php -c /home/xiaoju/php7/etc/php.ini ";
            if (gethostname()=='ipd-cloud-server01.gz01'){
                $phpPath = "php ";
            }
            $command = "nohup {$phpPath} index.php realtimewarning process/{$cityId}/{$hour}/{$date}/{$traceId}/{$uid} >>" .
                "{$logPath}realtimewarning.log  2>&1 &";
            exec($command);
        }
        $output = array(
            'errno' => ERR_SUCCESS,
            'errmsg' => "",
            'command' => $command,
        );
        echo json_encode($output);
        return;
    }

    public function process($cityId = '12', $hour = '00:00', $date = "", $traceId = "", $uid = "")
    {
        ob_end_flush();
        date_default_timezone_set('Asia/Shanghai');
        if(!is_numeric($cityId)){
            echo "cityId 必须为数字! \n";exit;
        }
        if(!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims',$date)){
            echo "date 必须为日期! \n";exit;
        }
        if(!preg_match('/\d{4,4}-\d{1,2}-\d{1,2}/ims',$date)){
            echo "date 必须为日期! \n";exit;
        }
        if(!in_array($uid, ["traj_index"])){
            echo "uid 不在白名单中! \n";exit;
        }
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=".$cityId."||hour=".$hour."||date=".$date."||trace_id=".$traceId."||message=processing\n\r";
        $this->realtimewarning_model->process($cityId, $date, $hour, $traceId);
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=".$cityId."||hour=".$hour."||date=".$date."||trace_id=".$traceId."||message=processed\n\r";
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=".$cityId."||hour=".$hour."||date=".$date."||trace_id=".$traceId."||message=calculating\n\r";
        $this->realtimewarning_model->calculate($cityId, $date, $hour, $traceId);
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=".$cityId."||hour=".$hour."||date=".$date."||trace_id=".$traceId."||message=calculated\n\r";
        return true;
    }
}
