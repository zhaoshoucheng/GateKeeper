<?php
/***************************************************************
# git文件上传类
# user:niuyufu@didichuxing.com
# date:2018-08-21
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Gift extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('junction_model');
        $this->load->model('timing_model');
        $this->load->config('nconf');
    }

    public function Upload()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'filecontent' => 'nullunable',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
//            return;
        }

    }
}
