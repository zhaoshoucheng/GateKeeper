<?php
/***************************************************************
# 时段优化类
# user:ningxiangbing@didichuxing.com
# date:2018-06-012
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Timeframeoptimize extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * 获取单点时段优化路口集合
    * @param task_id   Y 任务ID
    * @param city_id   Y 城市ID
    * @return json
    */
    public function getAllJunctions()
    {
        
    }

    /**
    * 获取路口相位集合（按NEMA排序）
    * @param city_id         interger Y 城市ID
    * @param task_id         interger Y 任务ID
    * @param junction_id     string   Y 路口ID
    * @param dates           array    Y 评估/诊断日期
    * @param task_time_range string   Y 任务时间段
    * @return json
    */
    public function getJunctionMovements()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'      => 'min:1',
                'city_id'      => 'min:1',
                'junction_id'  => 'nullunable'
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
            'task_id'     => intval($params['task_id']),
            'junction_id' => trim($params['junction_id']),
            'dates'       => trim($params['dates']),
            'time_range'  => trim($params['task_time_range']),
            'timingType'  => $this->timingType
        ];

        $result = $this->timeframeoptimize->getJunctionMovements($data);

        return $this->response($result);
    }

}
