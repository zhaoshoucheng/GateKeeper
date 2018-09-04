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
    * @param task_id          interger Y 任务ID
    * @param junction_id      string   Y 城市ID
    * @param dates            array    Y 评估/诊断日期
    * @param task_time_range  string   Y 任务时间段
    * @param flow_id          string   Y 相位ID （flow_id）
    * @return json
    */
    public function getScatterMtraj()
    {
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'         => 'min:1',
                'junction_id'     => 'nullunable',
                'flow_id'         => 'nullunable',
                'task_time_range' => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!is_array($params['dates']) || empty($params['dates'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数dates必须为数组且不可为空！';
            return;
        }

        $data = [
            'task_id'     => intval($params['task_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'dates'       => $params['dates'],
            'time_range'  => strip_tags(trim($params['task_time_range'])),
            'flow_id'     => strip_tags(trim($params['flow_id'])),
            'timingType'  => $this->timingType
        ];

        $result_data = $this->scatter_model->getTrackData($data);

        return $this->response($result_data);
    }
}
