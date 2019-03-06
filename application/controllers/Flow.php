<?php
/***************************************************************
# flow 管理类
# user:zhuyewei
# date:2018-09-21
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\DataService;

class Flow extends MY_Controller{

    private $dataService;

    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('http');

        $this->dataService = new DataService();

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
            $ret = httpGET($this->config->item('signal_control_interface')."/flowrelate/".$funcName,$params);
        }elseif ($reqMethod == 'POST'){
            $params = file_get_contents("php://input");
            $ret = httpPOST($this->config->item('signal_control_interface')."/flowrelate/".$funcName,$params,0,'raw');
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

    // 获取flow的各种详细指标，开放平台使用
    public function Quota()
    {
        // 获取flow的指标
        $params = $this->input->post(null, true);

        list($errno, $errmsg, $data) = $this->dataService->call(DataService::ApiFlowDetailQuota, $params, DataService::METHOD_POST);
        if ($errno != 0) {
            throw new Exception($errmsg);
        }

        $this->response($data);
    }
}