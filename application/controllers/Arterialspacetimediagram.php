<?php
/***************************************************************
# 干线时空图类
# user:ningxiangbing@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialspacetimediagram extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('traj_model');
    }

    /**
    * 获取干线时空图
    * @param junctions   array   Y 路口信息集合 如下：
    *   [
    *       [
    *          "junction_id"           => "2017030116_5138189",  // 路口ID
    *          "forward_flow_id"       => "2017030116_i_73821231_2017030116_o_877944131", // 正向flow id
    *          "forward_in_links"      => '111,222,333', // 正向inlinks  如取反向传 '-1'
    *          "forward_out_links"     => '111,222,333', // 正向outlinks 如取反向传 '-1'
    *          "reverse_flow_id"       => '2017030116_i_877944150_2017030116_o_877944100', // 反向flow
    *          "reverse_in_links"      => '111,222,333', // 反向inlinks  如取正向传 '-1'
    *          "reverse_out_links"     => '111,222,333', // 反向outlinks 如取正向传 '-1'
    *          "junction_inner_links"  => '111,222,333' // inner_links (正、反向是一样的)
    *          "tod_start_time"        => "16:00:00", // 配时方案开始时间 PS:当前时间点所属方案的开始结束时间
    *          "tod_end_time"          =>  "19:30:00",   // 配时方案结束时间
    *          "cycle"                 => 220                  // 配时周期
    *          "offset"                => 30                   // 偏移量
    *       ]
    *   ]
    * @param task_id     interger Y 任务ID
    * @param dates       array    Y 评估/诊断日期
    * @param map_version string   Y 地图版本
    * @param time_point  string   Y 时间点
    * @param method      interger Y 0=>正向 1=>反向 2=>双向
    * @param token       string   N 此次请求唯一标识，用于前端轮询 首次可不传
    * @return json
    */
    public function getSpaceTimeDiagram()
    {
        $params = $this->input->post(NULL, TRUE);
        $result = $this->traj_model->getSpaceTimeDiagram($params);
        $result['token'] = isset($params['token']) ?  $params['token'] : "";
        return $this->response($result);
    }

    public function getClockShiftCorrect()
    {
        $params = file_get_contents("php://input");
        $result = $this->traj_model->getClockShiftCorrect($params);
        $result['token'] = isset($params['token']) ?  $params['token'] : "";
        return $this->response($result);
    }
}