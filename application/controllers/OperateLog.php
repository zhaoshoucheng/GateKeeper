<?php
/**
 * 路口分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\OperateLogService;

class OperateLog extends MY_Controller
{
    protected $operateLogService;

    public function __construct()
    {
        parent::__construct();
        $this->operateLogService = new OperateLogService();
    }

    public function pageList() {
        $params = $this->input->post(null);
        $this->get_validate([
            'city_id' => 'required|trim',
            'page_size' => 'required|trim',
            'page_num' => 'required|trim',
            // 'start_time' => 'required|trim',
            // 'end_time' => 'required|trim',
            // 'user_name' => 'required|trim',
            // 'module' => 'required|trim',
            // 'action'     => 'required|trim',
            // 'action_type'       => 'required|trim',
        ],$params); 

        $passportInfo = $_SERVER['HTTP_DIDI_HEADER_PASSPORTINFO'] ?? "";
        $passportArr = json_decode($passportInfo,true);
        if($passportArr["level"]!=9){
            $params["user_name"] = $passportArr["phone"];
        }
        $data = $this->operateLogService->pageList($params);
        $this->response($data);
    }
}