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
        $data = json_decode($params,true);
        $data['token'] = md5(microtime(true) * mt_rand(1, 10000));
        $result = $this->traj_model->queryGreenWaveOptPlan(json_encode($data));
        return $this->response($result);
    }

    /**
     * 轮询获取绿波优化方案
     */
    public function getGreenWaveOptPlan()
    {
        $params = $this->input->post(NULL, TRUE);
        if (empty($params['token'])) {
            //待正式算法给出后该行删除
            //return $this->getGreenWaveOptPlanOld();
        }
        $result = $this->traj_model->getGreenWaveOptPlan($params);
        if(!empty($result['dataList'])){
            foreach ($result['dataList'] as $key=>$item){
                $result['dataList'][$key]["backward_green"] =
                    !empty($result['dataList'][$key]["backward_green"]) ?
                        $result['dataList'][$key]["backward_green"] : [];
            }
        }
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
    public function getGreenWaveOptPlanOld()
    {
        $params = $this->input->post(NULL, TRUE);

        if (empty($params['token'])) {
            //此话括号内的代码将在v4.1之后弃用

            $data['token'] = md5(microtime(true) * mt_rand(1, 10000));

            // junctions
            if (empty($params['junctions']) || !is_array($params['junctions'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数junctions 必须为数组格式且不能为空！';
                return;
            }
            $data['junctions'] = $params['junctions'];

            // method
            if (!isset($params['method'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数method为必传参数！';
                return;
            }
            if (!in_array(intval($params['method']), [0, 1, 2], true)) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数method传递错误！';
                return;
            }
            $data['method'] = intval($params['method']);

            // 当获取正向或双向时
            if ($data['method'] == 0 || $data['method'] == 2) {
                // forward_length
                if (empty($params['forward_length']) || !is_array($params['forward_length'])) {
                    $this->errno = ERR_PARAMETERS;
                    $this->errmsg = '参数forward_length必须为数组格式且不为空！';
                    return;
                }
                array_unshift($params['forward_length'], 0);
                array_push($params['forward_length'], 0);
                $data['forward_length'] = $params['forward_length'];

                // forward_speed
                if (empty($params['forward_speed']) || !is_array($params['forward_speed'])) {
                    $this->errno = ERR_PARAMETERS;
                    $this->errmsg = '参数forward_speed必须为数组格式且不为空！';
                    return;
                }
                array_unshift($params['forward_speed'], 0);
                array_push($params['forward_speed'], 0);
                $data['forward_speed'] = $params['forward_speed'];

            }

            // 当获取反向或双向时
            if ($data['method'] == 1 || $data['method'] == 2) {
                // reverse_length
                if (empty($params['reverse_length']) || !is_array($params['reverse_length'])) {
                    $this->errno = ERR_PARAMETERS;
                    $this->errmsg = '参数forward_length必须为数组格式且不为空！';
                    return;
                }
                array_unshift($params['reverse_length'], 0);
                array_push($params['reverse_length'], 0);
                $data['reverse_length'] = $params['reverse_length'];

                // reverse_speed
                if (empty($params['reverse_speed']) || !is_array($params['reverse_speed'])) {
                    $this->errno = ERR_PARAMETERS;
                    $this->errmsg = '参数reverse_speed必须为数组格式且不为空！';
                    return;
                }
                array_unshift($params['reverse_speed'], 0);
                array_push($params['reverse_speed'], 0);
                $data['reverse_speed'] = $params['reverse_speed'];
            }
        } else {
            $data['token'] = html_escape(trim($params['token']));
        }

        $result = $this->arterialgreenwave_model->getGreenWaveOptPlan($data);
        $result['token'] = $data['token'];

        return $this->response($result);
    }
}
