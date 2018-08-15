<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Schedule extends CI_Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Shanghai');
        parent::__construct();
        $this->load->model('schedule_model');
    }

    public function callback()
    {

    }

    public function process($cityId)
    {
        if(!is_numeric($cityId)){
            echo "cityId 必须为数字! \n";exit;
        }
        $traceId = gen_traceid();
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=".$cityId."||trace_id=".$traceId."||message=processing\n\r";
        $this->schedule_model->process($cityId, $traceId);
        echo "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=".$cityId."||trace_id=".$traceId."||message=processed\n\r";
    }
}
