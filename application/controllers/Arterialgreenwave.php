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
        $this->load->model('traj_model');
    }

    /**
     * 请求绿波图优化接口
     *
     * @param body json
     * {
     * "end_time": "13:30:00", //结束时间
     * "start_time": "11:30:00",  //开始时间
     * "dates": ["20181102"],  //开始日期
     * "request": {
     * "junction_list": [{
     * "junction_id": "111", //路口id
     * "cycle": 1, //周期
     * "offset": 1, //相位
     * "min_cycle": 1, //最小周期
     * "max_cycle": 1, //最大周期
     * "lock_cycle": 1, //周期锁定
     * "lock_offset": 1, //相位锁定
     * "clock_shift": 1, //相位偏移量
     * "forward_movement": {
     * "movement_id": "1111", //正向flow_id
     * "green": [{
     * "green_start": 1, //绿灯开始
     * "green_duration": 1, //绿灯持续
     * "yellow": 1,   //黄灯
     * "red_clean": 1 //全红
     * }],
     * "weight": 11 //权重
     *             },
     *             "backward_movement": {
     *     "movement_id": "22222",
     *                 "green": [{
     *         "green_start": 1,
     *                     "green_duration": 1,
     *                     "yellow": 1,
     *                     "red_clean": 1
     *                 }],
     *                 "weight": 11
     *             },
     *             "in_length": 1, //进入轨迹长度
     *             "out_length": 1, //出轨迹长度
     *             "in_speed": 1,  //正向进入速度
     *             "out_speed": 11  //反向进入速度
     *         }],
     *         "opt_type": 1,  //0为带宽模型，1为轨迹模型
     *         "equal_cycle": 1,  //周期一致标志，0为可不一致，1为必须一致
     *         "direction": 1 //优化方向，0为正向，1为反向，2为双向
     *     }
     * }
     **/
    public function queryGreenWaveOptPlan()
    {
        $params = file_get_contents("php://input");
        $result = $this->traj_model->queryGreenWaveOptPlan($params);
        $result['token'] = isset($result['token']) ?  $result['token'] : "";
        return $this->response($result);
    }

    /**
    * 轮询获取绿波优化方案
    * @param junctions      array     Y 路口集合 如下示例：
    * [
    *    {
    *        "junction_id": "xx432423423", // 路口ID
    *        "cycle": 60,                  // 配时周期
    *        "offset": 3,                  // 偏移量
    *        "forward_green": [            // 正向绿灯信息 如只取反向时传-1 例:forward_green['green_start':-1, "green_duration":-1]
    *            {
    *                "green_start": 0,     // 绿灯开始时间
    *                "green_duration": 10  // 绿灯持续时间
    *            },
    *            ......
    *        ],
    *        "reverse_green": [            // 反向绿灯信息 如只取正向时传-1 例:reverse_green['green_start':-1, "green_duration":-1]
    *            {
    *                "green_start": 0,     // 绿灯开始时间
    *                "green_duration": 10  // 绿灯持续时间
    *            },
    *            ......
    *        ],
    *        "lock_cycle": 1,              // 周期是否锁定 0：否 1：是
    *        "lock_offset": 0              // 偏移量是否锁定 0：否 1：是
    *    }
    * ]
    * @param forward_length array    N 正向路段长度  格式：[100, 200, 300] 只取反向时可不传
    * @param forward_speed  array    N 正向速度     格式：[100, 200, 300] 只取反向时可不传
    * @param reverse_length array    N 反向路段长度 格式：[100, 200, 300]  只取正向时可不传
    * @param reverse_speed  array    N 返向速度     格式：[100, 200, 300] 只取正向时可不传
    * @param method         interger Y 0=>正向 1=>反向 2=>双向
    * @param token          string   N 唯一标识，用于前端轮询
    * @return json
    */
    public function getGreenWaveOptPlan()
    {
        $params = $this->input->post(NULL, TRUE);
        $validate = Validate::make($params, [
                'token'        => 'nullunable',
            ]
        );
        if(!$validate['status']){
            return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
        }
        if (empty($params['token'])) {

        } else {
            $data['token'] = html_escape(trim($params['token']));
        }

        $result = $this->arterialgreenwave_model->getGreenWaveOptPlan($data);
        $result['token'] = $data['token'];
        return $this->response($result);
    }
}
