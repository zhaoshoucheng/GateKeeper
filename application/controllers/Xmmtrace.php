<?php
/***************************************************************
# 自使用配时,参数 管理类
# user:niuyufu
# date:2018-09-21
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Xmmtrace extends MY_Controller{
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
            $ret = httpGET($this->config->item('xmmtrace_interface')."/".$funcName,$params);
        }elseif ($reqMethod == 'POST'){
            $params = file_get_contents("php://input");
            $ret = httpPOST($this->config->item('xmmtrace_interface')."/".$funcName,$params,0,'raw');
        }
        if($ret){
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode($ret);
            exit;
        }
        $this->errno = ERR_HTTP_FAILED;
        $this->errmsg = "服务异常";
        return;
    }
}