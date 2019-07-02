<?php
/***************************************************************
# 时段优化类
# user:ningxiangbing@didichuxing.com
# date:2018-06-20
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\DiagnosisNoTimingService;

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


    // 获取配时分割
    public function getTimingSplit() {

        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params,
            [
                'junction_id'      => 'nullunable',
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

        $url = $this->config->item('traj_interface') . '/greensplit/getTimeSplit';
        $query = [
            'logic_junction_id' => $params['junction_id'],
            'dates' => implode(",", $params['dates']),
        ];
        $ret =  httpPOST($url, $query, 20000, "json");
        $ret = json_decode($ret, true);
        $data = $ret['data'];
        return $this->response($data);
    }

    // 获取配时指标
    public function getFlowQuotaWithTiming()
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

        $timeRange = explode('-', trim($params['time_range']));

        $query = [
            'logic_junction_id'     => strip_tags(trim($params['junction_id'])),
            'dates'                 => implode(',', $params['dates']),
            'start_time'            => $timeRange[0],
            'end_time'              => $timeRange[1],
        ];

        // 获取配时
        $url = $this->config->item('traj_interface') . '/greensplit/getOriginTimingPlan';
        $ret =  httpPOST($url, $query, 20000, "json");
        $ret = json_decode($ret, true);
        $plan = $ret['data'];
        // 配时重新组织
        $flowSignal = [];
        foreach ($plan['movements'] as $movement) {
            $logicFlowId = $movement['info']['logic_flow_id'];
            if (empty($logicFlowId)) {
                continue;
            }
            $signal = $movement['signal'];
            $flowSignal[$logicFlowId] = $signal;
        }

        // 获取指标
        $query['junction_id'] = strip_tags(trim($params['junction_id']));
        $query['search_type'] = 0;
        $query['type'] = 2;
        $query['time_range'] = trim($params['time_range']);
        $query['city_id'] = intval($params['city_id']);
        $query['timingType'] = 1;
        $query['dates'] = $params['dates'];

        // 获取路口指标详情
        $dianosisService = new DiagnosisNoTimingService();
        $quotas = $dianosisService->getFlowQuotas($query);

        $flow_quota_all = $quotas['flow_quota_all'];
        $movements = $quotas['movements'];
        $tmp = [];
        foreach ($movements as $movement) {
            $tmp[$movement['movement_id']] = $movement;
        }


        $result = [
            'flow_quota_all' => $flow_quota_all,
            'movements' => [],
        ];

        foreach ($plan['movements'] as $movement) {
            $logicFlowId = $movement['info']['logic_flow_id'];
            // 是非机动车相位，不显示，直接过滤
            if (empty($logicFlowId)|| $movement['info']['type'] == 1) {
                continue;
            }

            // 这里有个要注意的点，这里只用第一个配时信息，如果有二次放行，会出现问题。
            $signal = $movement['signal'];
            if (isset($tmp[$logicFlowId])) {
                // 有指标就计算上去
                $tmp[$logicFlowId]['comment'] = $movement['info']['comment'];
                $result['movements'][] = array_merge($tmp[$logicFlowId], $signal[0]);
            } else {
                // 没有指标
                $result['movements'][] = array_merge([
                    "confidence" => "无",
                    "movement_id" => $logicFlowId,
                    "comment" => $movement['info']['comment'],
                    "route_length" => null,
                    "delay_sum"=> null,
                    "free_flow_speed"=>  null,
                    "free_speed"=>  null,
                    "nonsaturation_delay"=>  null,
                    "nonsaturation_stop_frequency"=>  null,
                    "nonsaturation_traj_count"=>  null,
                    "oversaturation_delay"=>  null,
                    "oversaturation_stop_frequency"=>  null,
                    "oversaturation_traj_count"=>  null,
                    "queue_length"=>  null,
                    "speed"=>  null,
                    "spillover_delay"=>  null,
                    "spillover_rate"=>  null,
                    "spillover_stop_frequency"=>  null,
                    "spillover_traj_count"=>  null,
                    "stop_delay"=>  null,
                    "stop_rate"=>  null,
                    "stop_time_cycle"=>  null,
                    "traj_count"=>  null,
                    "twice_stop_rate"=>  null,
                    "weight"=>  null,
                    "stop_delay_flag"=>  null,
                    "queue_length_flag"=>  null,
                    "spillover_rate_flag"=>  null,
                    "stop_rate_flag"=>  null,
                ], $signal[0]);
            }

        }


        return $this->response($result);
    }

    /**
     * 获取绿信比优化方案
     * @param task_id         interger Y 任务ID
     * @param junction_id     string   Y 路口ID
     * @param time_range      string   Y 方案开始结束时间 00:00-09:00
     * @param dates           array    Y 评估/诊断日期
     * @return json
     */
    public function getOriginTimingPlan()
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

        $url = $this->config->item('traj_interface') . '/greensplit/getOriginTimingPlan';
        $ret =  httpPOST($url, $data, 20000, "json");
        $ret = json_decode($ret, true);
        $result = $ret['data'];
        return $this->response($result);
    }

    /**
    * 获取绿信比优化方案
    * @param task_id         interger Y 任务ID
    * @param junction_id     string   Y 路口ID
    * @param time_range      string   Y 方案开始结束时间 00:00-09:00
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
        return $this->response($result);
    }


    // ---------------------   下面的接口已经被废弃 -------------------

    /**
    * 获取绿信比优化方案，这个接口已经废弃
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

    /**
     * 获取有黄灯的配时方案-绿信比优化，这个接口废弃
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

}
