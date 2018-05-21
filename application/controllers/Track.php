<?php
/***************************************************************
# 轨迹类
# user:ningxiangbing@didichuxing.com
# date:2018-04-13
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Track extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('track_model');
    }

    /**
    * 获取散点图
    * @param task_id     interger 任务ID
    * @param junction_id string   城市ID
    * @param flow_id     string   相位ID （flow_id）
    * @param search_type interger 搜索类型 查询类型 1：按方案查询 0：按时间点查询
    * @param time_point  string   时间点 当search_type = 0 时 必传 格式：00:00
    * @param time_range  string   时间段 当search_type = 1 时 必传 格式：00:00-00:30
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
                'search_type' => 'min:0',
                'flow_id'     => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if ((int)$params['search_type'] == 0) {
            if (empty($params['time_point'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_point cannot be empty.';
                return;
            }
        } else {
            if (empty($params['time_range'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_range cannot be empty.';
                return;
            }
            $time_range = array_filter(explode('-', $params['time_range']));
            if (empty($time_range[0]) || empty($time_range[1])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_range is wrong.';
                return;
            }
        }

        $data = [
            'task_id'         => intval($params['task_id']),
            'junction_id'     => strip_tags(trim($params['junction_id'])),
            'flow_id'         => strip_tags(trim($params['flow_id'])),
            'search_type'     => intval($params['search_type']),
            'time_point'      => strip_tags(trim($params['time_point'])),
            'time_range'      => strip_tags(trim($params['time_range'])),
            'timingType'      => $this->timingType
        ];

        $result_data = $this->track_model->getTrackData($data, 'getScatterMtraj');

        return $this->response($result_data);
    }

    /**
    * 获取时空图
    * @param task_id     interger 任务ID
    * @param junction_id string   路口ID
    * @param flow_id     string   相位ID （flow_id）
    * @param search_type interger 搜索类型 查询类型 1：按方案查询 0：按时间点查询
    * @param time_point  string   时间点 当search_type = 0 时 必传 格式：00:00
    * @param time_range  string   时间段 当search_type = 1 时 必传 格式：00:00-00:30
    * @return json
    */
    public function getSpaceTimeMtraj()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'     => 'min:1',
                'junction_id' => 'nullunable',
                'search_type' => 'min:0',
                'flow_id'     => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if ((int)$params['search_type'] == 0) {
            if (empty($params['time_point'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_point cannot be empty.';
                return;
            }
        } else {
            if (empty($params['time_range'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_range cannot be empty.';
                return;
            }
            $time_range = array_filter(explode('-', $params['time_range']));
            if (empty($time_range[0]) || empty($time_range[1])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_range is wrong.';
                return;
            }
        }

        $data = [
            'task_id'         => intval($params['task_id']),
            'junction_id'     => strip_tags(trim($params['junction_id'])),
            'flow_id'         => strip_tags(trim($params['flow_id'])),
            'search_type'     => intval($params['search_type']),
            'time_point'      => strip_tags(trim($params['time_point'])),
            'time_range'      => strip_tags(trim($params['time_range'])),
            'timingType'      => $this->timingType
        ];
        $result_data = $this->track_model->getTrackData($data, 'getSpaceTimeMtraj');

        return $this->response($result_data);
    }
}
