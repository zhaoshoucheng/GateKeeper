<?php
/***************************************************************
# 干线绿波类
# user:ningxiangbing@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialgreenwave extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('arterialgreenwave_model');
    }

    /**
    * 获取绿波优化方案
    * @param junctions      json Y 路口集合 如下示例：
    * [
    *  {
    *   "junction_id":"xx432423423", // 路口ID
    *   "cycle":60,                  // 配时周期
    *   "offset":3,                  // 偏移量
    *   "forward_green_start":0,     // 正向绿灯开始时间 如只取反向传-1
    *   "forward_green_duration":30, // 正向绿灯持续时间 如只取反向传-1
    *   "reverse_green_start":30,    // 反向绿灯开始时间 如只取正向传-1
    *   "reverse_green_duration":20, // 反向绿灯持续时间 如只取正向传-1
    *   "lock_cycle":1,              // 周期是否锁定 1是 0否
    *   "lock_offset":0              // 偏移量是否锁定 1是 0否
    *   },
    * ]
    * @param forward_length array  N 正向路段长度  格式：[100, 200, 300] 只取反向时可不传
    * @param forward_speed  array  N 正向速度     格式：[100, 200, 300] 只取反向时可不传
    * @param reverse_length array  N 反向路段长度 格式：[100, 200, 300]  只取正向时可不传
    * @param reverse_speed  array  N 返向速度     格式：[100, 200, 300] 只取正向时可不传
    * @param token          string Y 此次请求唯一标识，用于前端轮询
    * @return json
    */
    public function getGreenWaveOptPlan()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'token'      => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $data['token'] = trim($params['token']);

        // junctions
        if (empty($params['junctions'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数junctions不能为空！';
            return;
        }
        $junctions = json_decode($params['junctions']);
        if (json_last_error() != JSON_ERROR_NONE) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数junctions必须为json格式！';
            return;
        }
        $data['junctions'] = $junctions;

        // forward_length
        if (isset($params['forward_length'])) {
            $forward_length = $params['forward_length'];
            if (!is_array($forward_length)) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数forward_length必须为数组！';
                return;
            }
            $data['forward_length'] = $forward_length;
        }

        // forward_speed
        if (isset($params['forward_speed'])) {
            $forward_speed = $params['forward_speed'];
            if (!is_array($forward_speed)) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数forward_speed必须为数组！';
                return;
            }
            $data['forward_speed'] = $forward_speed;
        }

        // reverse_length
        if (isset($params['reverse_length'])) {
            $reverse_length = $params['reverse_length'];
            if (!is_array($reverse_length)) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数reverse_length必须为数组！';
                return;
            }
            $data['reverse_length'] = $reverse_length;
        }

        // reverse_speed
        if (isset($params['reverse_speed'])) {
            $reverse_speed = $params['reverse_speed'];
            if (!is_array($reverse_speed)) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数reverse_speed必须为数组！';
                return;
            }
            $data['reverse_speed'] = $reverse_speed;
        }

        $result = $this->arterialgreenwave_model->getGreenWaveOptPlan($data);

        return $this->response($result);
    }
}
