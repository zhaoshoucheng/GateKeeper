<?php
/***************************************************************
# 时段优化全天路口散点图类
# user:ningxiangbing@didichuxing.com
# date:2018-06-07
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class TimingAdaptationArea extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 更新自适应路口开关
     */
    public function junctionSwitch()
    {
        $params = $this->input->post();

        $validator = Validator::make($params, [
            'logic_junction_id' => 'required',
            'area_id' => 'required',
            'is_upload' => 'required;in:0,1',
        ]);

        if($validator->fail()) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }

        $address = 'http://100.90.164.31:8006/signal-mis';
        $data = httpPOST($address . '/TimingAdaptation/junctionSwitch', $params);

        echo $data;
        exit();
    }
}