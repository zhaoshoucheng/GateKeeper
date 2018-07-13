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
        $this->load->model('arterialspacetimediagram_model');
    }

    /**
    * 获取干线时空图
    * @param junctions   array   Y 路口信息集合 如下：
    *   [
    *       {
    *          "junction_id": "2017030116_5138189",  // 路口ID
    *          "forward_flow_id": "2017030116_i_73821231_2017030116_o_877944131", // 正向flow id
    *          "reverse_flow_id": "2017030116_i_877944150_2017030116_o_877944100",// 反向flow id
    *          "tod_start_time": "16:00:00", // 配时方案开始时间 PS:当前时间点所属方案的开始结束时间
    *          "tod_end_time": "19:30:00",   // 配时方案结束时间
    *          "cycle": 220                  // 配时周期
    *          "offset":30                   // 偏移量
    *       }
    *   ]
    * @param task_id    interger Y 任务ID
    * @param dates      array    Y 评估/诊断日期
    * @param time_point string   Y 时间点
    * @param method     interger Y 0=>正向 1=>反向 2=>双向
    * @param token      string   N 此次请求唯一标识，用于前端轮询 首次可不传
    * @return json
    */
    public function getSpaceTimeDiagram()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'    => 'min:1',
                'method'     => 'min:0',
                'time_point' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data['task_id'] = intval($params['task_id']);
        $data['method'] = intval($params['method']);
        $data['time_point'] = trim($params['time_point']);

        if (empty($params['dates']) || !is_array($params['dates'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数 dates 必须为数组格式且不可为空！';
            return;
        }
        $data['dates'] = $params['dates'];

        if (empty($params['token'])) {
            $data['token'] = md5(microtime(true) * mt_rand(1, 10000));
        } else {
            $data['token'] = html_escape(trim($params['token']));
        }

        // junctions
        if (empty($params['junctions']) || !is_array($params['junctions'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数junctions 必须为数组格式且不能为空！';
            return;
        }
        $data['junctions'] = $params['junctions'];


    	$result = $this->arterialspacetimediagram_model->getSpaceTimeDiagram($data);

        $result['token'] = $data['token'];
        return $this->response($result);
    }
}