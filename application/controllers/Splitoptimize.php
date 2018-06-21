<?php
/***************************************************************
# 时段优化类
# user:ningxiangbing@didichuxing.com
# date:2018-06-20
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Splitoptimize extends MY_Controller
{
    // 黄灯时长3秒
    private $yellowLight = 3;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('timing_model');
        $this->load->model('splitoptimize_model');
    }

    /**
    * 获取有黄灯的配时方案-绿信比优化
    * @param task_id         interger Y 任务ID
    * @param junction_id     string   Y 路口ID
    * @param task_time_range string   Y 任务时段 例：00:00-24:00
    * @param dates           array    Y 评估/诊断日期
    * @return json
    */
    public function getTimingPlan()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'          => 'min:1',
                'junction_id'      => 'nullunable',
                'task_time_range'  => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!is_array($params['dates']) || count($params['dates']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The dates cannot be empty and must be array.';
            return;
        }

        $data = [
            'dates'       => $params['dates'],
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'time_range'  => strip_tags(trim($params['task_time_range'])),
            'yellowLight' => $this->yellowLight,
            'timingType'  => $this->timingType,
        ];

        $timing = $this->timing_model->getTimingPlan($data);

        return $this->response($timing);
    }

    /**
    *
    */
    public function getSplitOptimizePlan()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'          => 'min:1',
                'junction_id'      => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!is_array($params['dates']) || count($params['dates']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The dates cannot be empty and must be array.';
            return;
        }

        if (!is_array($params['movements']) || count($params['movements']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The movements cannot be empty and must be array.';
            return;
        }

        $data = [
            'dates'       => $params['dates'],
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'task_id'     => intval($params['task_id']),
            'movements'   => $params['movements'],
            'divide_num'  => intval($params['divide_num']),
        ];

        $result = $this->splitoptimize_model->getSplitOptimizePlan($data);

        return $this->response($result);
    }


}
