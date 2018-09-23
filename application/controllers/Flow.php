<?php
/***************************************************************
# flow 管理类
# user:zhuyewei
# date:2018-09-21
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Flow extends MY_Controller{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('http');

    }


    public function proxy()
    {
        $params = $this->input->post(NULL, TRUE);
        $requestUri = $_SERVER['REQUEST_URI'];
        $realUri = explode("/",$requestUri);
        $funcName = end($realUri);

        if(strpos($funcName,"query") === 0){
            $ret = httpGET($this->config->item('signal_control_interface')."/flowrelate/".$funcName,$params);
        }else{
            $ret = httpPOST($this->config->item('signal_control_interface')."/flowrelate/".$funcName,$params,0,'json');
        }

        if($ret){
            $ret = json_decode($ret,true);
            if($ret['errorCode']!=0){
                $this->errno = ERR_HTTP_FAILED;
                $this->errmsg = $ret['errorMsg'];
                return;
            }
            return $this->response($ret['data']);
        }

        $this->errno = ERR_HTTP_FAILED;
        $this->errmsg = "服务异常";
        return;
    }
}