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
        $this->load->model('traj_model');
        $this->load->model("waymap_model");
        $this->setTimingType();
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
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params,
            [
//                'task_id'          => 'min:1',
                'junction_id'      => 'nullunable',
                'task_time_range'  => 'nullunable'
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
    * 获取绿信比优化方案
    * @param task_id         interger Y 任务ID
    * @param junction_id     string   Y 路口ID
    * @param time_range      string   Y 方案开始结束时间 00:00-09:00
    * @param task_time_range string   Y 任务时段 例：00:00-24:00
    * @param dates           array    Y 评估/诊断日期
    * @return json
    */
    public function getSplitOptimizePlan()
    {
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params,
            [
                'junction_id'      => 'nullunable',
                'time_range'       => 'nullunable',
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

        foreach ($params['dates'] as $key=>$v){
            $params['dates'][$key] = date("Y-m-d",strtotime($v));
        }

        $params['time_range'] = explode('-', trim($params['time_range']));

        $data = [
            'logic_junction_id'     => strip_tags(trim($params['junction_id'])),
            'dates'                 => implode(',', $params['dates']),
            'start_time'            => $params['time_range'][0],
            'end_time'              => $params['time_range'][1],
        ];

        $result = $this->traj_model->getSplitOptimizePlan($data);
        //替换flow名称
        $flowInfos = $this->waymap_model->flowsByJunctionOnline(trim($data['logic_junction_id']));
        $flowMap = [];
        if(!empty($flowInfos)){
            foreach ($flowInfos as $fk=> $fv){
                if($fv['desc']!=""){
                    $flowMap[$fv['logic_flow_id']] = $fv["desc"];
                }
            }
        }
        foreach ($result['movements'] as $k=>$v){
            if(isset($flowMap[$v['info']['logic_flow_id']])){
                $result['movements'][$k]['info']['comment'] = $flowMap[$v['info']['logic_flow_id']];
            }
        }
        return $this->response($result);
    }

    /**
    * 获取绿信比优化方案
    * @param task_id         interger Y 任务ID
    * @param junction_id     string   Y 路口ID
    * @param time_range      string   Y 方案开始结束时间 00:00-09:00
    * @param task_time_range string   Y 任务时段 例：00:00-24:00
    * @param dates           array    Y 评估/诊断日期
    * @return json
    */
    public function getSplitOptimizePlanOld()
    {
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'          => 'min:1',
                'junction_id'      => 'nullunable',
                'time_range'       => 'nullunable',
                'task_time_range'  => 'nullunable',
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
            'dates'           => $params['dates'],
            'junction_id'     => strip_tags(trim($params['junction_id'])),
            'task_id'         => intval($params['task_id']),
            'time_range'      => strip_tags(trim($params['time_range'])),
            'task_time_range' => strip_tags(trim($params['task_time_range'])),
            'yellowLight'     => $this->yellowLight,
            'timingType'      => $this->timingType,
        ];

        $result = $this->splitoptimize_model->getSplitOptimizePlan($data);

        return $this->response($result);
    }


}
