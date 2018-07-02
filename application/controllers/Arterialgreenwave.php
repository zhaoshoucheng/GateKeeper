<?php
/**
 * 干线绿波相关controller
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialgreenwave extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('arterialgreenwave_model');
        $this->load->model('timing_model');
    }

    /**
     * 获取绿波全城路口集合接口
     */
    public function getAllJunctions()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'task_id' => 'min:1',
            'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $data = $this->arterialgreenwave_model->getAllJunctions([
            'task_id' => intval($params['task_id']),
            'city_id' => intval($params['city_id']),
        ]);
        return $this->response($data);
    }
}
