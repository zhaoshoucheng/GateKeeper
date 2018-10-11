<?php
/***************************************************************
# 新版人工配时 管理类
# user:niuyufu
# date:2018-09-21
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class SignalControl extends MY_Controller{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('http');

    }

    public function proxy()
    {

        $requestUri = $_SERVER['REQUEST_URI'];
        $realUri = explode("?",$requestUri);
        $realUri = explode("/",$realUri[0]);
        $funcName = strtolower(end($realUri));
        $reqMethod = $_SERVER['REQUEST_METHOD'];
        if($reqMethod == 'GET'){
            $params = $this->input->get();
            if(isset($params['read_only']) && $params['read_only']==1){
                $ret = httpGET($this->config->item('signal_control_interface')."/timingrelease/".$funcName,$params);
            }else{
                $ret = httpGET($this->config->item('signal_control_interface')."/signalcontrol/".$funcName,$params);
            }
        }elseif ($reqMethod == 'POST'){
            $params = file_get_contents("php://input");
            $ret = httpPOST($this->config->item('signal_control_interface')."/signalcontrol/".$funcName,$params,0,'raw');
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