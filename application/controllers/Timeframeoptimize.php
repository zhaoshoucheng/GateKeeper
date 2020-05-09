<?php

/***************************************************************
# 时段优化类
# user:ningxiangbing@didichuxing.com
# date:2018-06-12
 ***************************************************************/

defined('BASEPATH') or exit('No direct script access allowed');

use Services\TimingService;

class Timeframeoptimize extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('timeframeoptimize_model');
        $this->load->model('timing_model');
        $this->setTimingType();
        $this->load->model('traj_model');
        $this->load->model('waymap_model');

        $this->timingService = new TimingService();
    }

    /**
     * 获取单点时段优化路口集合
     * @param task_id   Y 任务ID
     * @param city_id   Y 城市ID
     * @return json
     */
    public function getAllJunctions()
    {
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make(
            $params,
            [
                'task_id'      => 'min:1',
                'city_id'      => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $data = [
            'task_id'     => intval($params['task_id']),
            'city_id'     => intval($params['city_id']),
        ];
        $result = $this->timeframeoptimize_model->getAllJunctions($data);
        return $this->response($result);
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
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make(
            $params,
            [
                'city_id'      => 'min:1',
                'junction_id'  => 'nullunable'
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
            'junction_id' => trim($params['junction_id']),
            'dates'       => $params['dates'],
            'time_range'  => trim($params['task_time_range']),
            'timingType'  => $this->timingType
        ];

        $result = $this->timeframeoptimize_model->getJunctionMovements($data);

        return $this->response($result);
    }


    /**
     * 获取多路口配时时间方案
     * @param junction_id     string Y 路口ID
     * @param dates           array  Y 评估/诊断时间
     * @param task_time_range string Y 任务时间段
     * @return json
     */
    public function getOptimizeTimingPlus()
    {
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make(
            $params,
            [
                'junction_ids'      => 'nullunable',
                'task_time_range'  => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (empty($params['dates']) || !is_array($params['dates'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数dates必须为数组且不可为空！';
            return;
        }

        $junctionIds = explode(",", $params["junction_ids"]);
        $listTiming = [];
        foreach ($junctionIds as $junctionId) {
            $data = [
                'dates'       => $params['dates'],
                'junction_id' => strip_tags(trim($junctionId)),
                'time_range'  => strip_tags(trim($params['task_time_range'])),
            ];
            // if (isset($params['source'])) {
            //     $data['source'] = $params['source']);
            // } elseif if (isset($params['source_type'])) {
            //     $data['source'] = $params['source_type']);
            // }
            $timing = $this->timingService->getOptimizeTiming($data);
            if (empty($timing)) {
                $listTiming[$junctionId] = [
                    "type" => "1",
                    "timing" => [
                        [
                            "comment" => "1",
                            "start" => "05:00:00",
                            "end" => "00:00:00",
                        ],
                        [
                            "comment" => "1",
                            "start" => "05:00:00",
                            "end" => "10:00:00",
                        ],
                        [
                            "comment" => "1",
                            "start" => "10:00:00",
                            "end" => "15:00:00",
                        ],
                        [
                            "comment" => "1",
                            "start" => "15:00:00",
                            "end" => "20:00:00",
                        ],
                        [
                            "comment" => "1",
                            "start" => "20:00:00",
                            "end" => "24:00:00",
                        ],
                    ],
                ];
            } else {
                $listTiming[$junctionId] = [
                    "type" => "1",
                    "timing" => $timing,
                ];
            }
        }
        return $this->response($timing);
    }

    /**
     * 获取配时时间方案
     * @param junction_id     string Y 路口ID
     * @param dates           array  Y 评估/诊断时间
     * @param task_time_range string Y 任务时间段
     * @return json
     */
    public function getOptimizeTiming()
    {
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make(
            $params,
            [
                'junction_id'      => 'nullunable',
                'task_time_range'  => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (empty($params['dates']) || !is_array($params['dates'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数dates必须为数组且不可为空！';
            return;
        }

        $data = [
            'dates'       => $params['dates'],
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'time_range'  => strip_tags(trim($params['task_time_range'])),
        ];
        // if (isset($params['source'])) {
        //     $data['source'] = $params['source']);
        // } elseif if (isset($params['source_type'])) {
        //     $data['source'] = $params['source_type']);
        // }
        $timing = $this->timingService->getOptimizeTiming($data);
        return $this->response($timing);
    }

    /**
     * 获取多套时段划分方案
     * @param junction_id string   Y 路口ID
     * @param dates       array    Y 评估/诊断日期
     * @param movements   array    Y 路口相位集合
     * @param divide_num  interger Y 划分数量
     * @return json
     */
    public function getTodOptimizePlanPlus()
    {
        $params = $this->input->post(NULL, TRUE);
        $validate = Validate::make(
            $params,
            [
                'junction_id'      => 'nullunable',
                'task_time_range'      => 'nullunable',
                'divide_num'       => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $params = $this->input->post(NULL, TRUE);
        $timeSplit = explode("-", $params['task_time_range']);
        $startTime = $timeSplit[0];
        $endTime = $timeSplit[1];
        $shm = explode(":", $startTime);
        $ehm = explode(":", $endTime);

        if (($ehm[0] * 3600 + $ehm[1] * 60 - $shm[0] * 3600 + $shm[1] * 60) < $params['divide_num'] * 15 * 60) {
            return $this->response(array(
                "tod_plans" => [],
                "cutTime" => [],
                "warning" => "时段划分至少15分钟",
            ));
        }
        $flowInfo = $this->waymap_demo->getFlowInfo32();
        $movementIDS = [];
        foreach ($flowInfo as $flow) {
            $movementIDS[] = $flow["logic_flow_id"];
        }
        $params["movements"] = $movementIDS;
        //todo 这里获取一下相位列表
        // type GetTodOptimizePlanRequest struct {
        //     DivideNum  string   `json:"divide_num" validate:"required"`
        //     Dates      []string `json:"dates" validate:"required"`
        //     JunctionID string   `json:"junction_id" validate:"required"`
        //     CityID     string   `json:"city_id" validate:"required"`
        //     TaskID     string   `json:"task_id" validate:""`
        //     Movements  []string `json:"movements" validate:"required"`
        //     StartTime  string   `json:"start_time" validate:"required"`
        //     EndTime    string   `json:"end_time" validate:"required"`
        // }

        $result = $this->traj_model->getTodOptimizePlan($params);
        return $this->response($result);
    }

    /**
     * 获取时段划分方案
     * @param junction_id string   Y 路口ID
     * @param dates       array    Y 评估/诊断日期
     * @param movements   array    Y 路口相位集合
     * @param divide_num  interger Y 划分数量
     * @return json
     */
    public function getTodOptimizePlan()
    {
        $params = $this->input->post(NULL, TRUE);
        $timeSplit = explode("-", $params['task_time_range']);
        //每段保证最短15分钟

        $startTime = $timeSplit[0];
        $endTime = $timeSplit[1];
        $shm = explode(":", $startTime);
        $ehm = explode(":", $endTime);

        if (($ehm[0] * 3600 + $ehm[1] * 60 - $shm[0] * 3600 + $shm[1] * 60) < $params['divide_num'] * 15 * 60) {
            return $this->response(array(
                "tod_plans" => [],
                "cutTime" => [],
                "warning" => "时段划分至少15分钟",
            ));
        }
        // print_r($params);
        // exit;
        $result = $this->traj_model->getTodOptimizePlan($params);
        return $this->response($result);
        /*
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'          => 'min:1',
                'junction_id'      => 'nullunable',
                'divide_num'       => 'min:1',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (empty($params['dates']) || !is_array($params['dates'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数dates必须为数组且不可为空！';
            return;
        }

        if (empty($params['movements']) || !is_array($params['movements'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数movements必须为数组且不可为空！';
            return;
        }

        $data = [
            'dates'       => $params['dates'],
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'task_id'     => intval($params['task_id']),
            'movements'   => $params['movements'],
            'divide_num'  => intval($params['divide_num']),
        ];

        $result = $this->timeframeoptimize_model->getTodOptimizePlan($data);

        return $this->response($result);
        */
    }
}
