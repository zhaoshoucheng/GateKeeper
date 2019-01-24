<?php
/***************************************************************
# 干线路口类
# user:niuyufu@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialjunction extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('arterialjunction_model');
        $this->load->model('timing_model');
    }

    /**
     * 获取绿波全城路口集合接口
     */
    public function getAllJunctions()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            //'task_id' => 'min:1',
            'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = $this->arterialjunction_model->getAllJunctions([
            'task_id' => !empty($params['task_id']) ? intval($params['task_id']) : "",
            'city_id' => intval($params['city_id']),
        ]);

        return $this->response($data);
    }

    /**
     * 获取可连接为干线的路口集合
     */
    public function getAdjJunctions()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'q' => 'nullunable',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $qJson = json_decode($params['q'],true);
        /*if(empty($qJson["task_id"]) || !($qJson["task_id"]>0)){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The task_id cannot be empty and must be interger.';
            return;
        }*/
        if(empty($qJson["city_id"]) || !($qJson["city_id"]>0)){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The city_id cannot be empty and must be interger.';
            return;
        }
        if(!isset($qJson["map_version"]) || !is_integer($qJson["map_version"]) || !($qJson["map_version"]>-1)){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The map_version cannot be empty and must be interger.';
            return;
        }
        if(empty($qJson["selected_junctionid"]) || !is_string($qJson["selected_junctionid"])){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The selected_junctionid cannot be empty and must be string.';
            return;
        }
        if(empty($qJson["selected_path"]) && !is_array($qJson["selected_path"])){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The selected_path must be array.';
            return;
        }

        try{
            $data = $this->arterialjunction_model->getAdjJunctions([
                'q' => $qJson,
            ]);
        }catch (\Exception $e){
            com_log_warning('_itstool_Arterialjunction_getAdjJunctions_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }
}
