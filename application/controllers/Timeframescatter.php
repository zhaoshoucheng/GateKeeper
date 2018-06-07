<?php
/***************************************************************
# 时段优化全天路口散点图类
# user:ningxiangbing@didichuxing.com
# date:2018-06-07
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Timeframescatter extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('scatter_model');
    }

    /**
    * 获取散点图
    * @param task_id     interger 任务ID
    * @param junction_id string   城市ID
    * @param flow_id     string   相位ID （flow_id）
    * @return json
    */
    public function getScatterMtraj()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'     => 'min:1',
                'junction_id' => 'nullunable',
                'flow_id'     => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'task_id'         => intval($params['task_id']),
            'junction_id'     => strip_tags(trim($params['junction_id'])),
            'flow_id'         => strip_tags(trim($params['flow_id'])),
        ];

        $result_data = $this->scatter_model->getTrackData($data);

        return $this->response($result_data);
    }
}
