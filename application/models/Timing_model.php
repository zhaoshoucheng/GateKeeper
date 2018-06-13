<?php
/********************************************
# desc:    配时数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-08
********************************************/

class Timing_model extends CI_Model
{
    private $email_to = 'ningxiangbing@didichuxing.com';
    public function __construct()
    {
        parent::__construct();

        $this->load->config('nconf');
    }

    /**
    * 获取路口配时信息
    * @param $data['junction_id'] string   逻辑路口ID
    * @param $data['dates']       array    评估/诊断日期
    * @param $data['time_range']  string   时间段 00:00-00:30
    * @param $data['timingType']  interger 配时数据源 0，全部；1，人工；2，配时反推；3，信号机上报
    * @return array
    */
    public function getJunctionsTimingInfo($data)
    {
        if (count($data) < 1) {
            return [];
        }

        // 获取配时数据
        $timing = $this->getTimingData($data);

        // 对返回数据格式化,返回需要的格式
        if (count($timing >= 1)) {
            $timing = $this->formatTimingData($timing, $data['time_range']);
        } else {
            return [];
        }

        return $timing;
    }

    /**
    * 获取flow_id对应名称的数组，用于匹配相位名称
    * @param $data['junction_id'] string 逻辑路口ID
    * @param $data['dates']       array  评估/诊断日期
    * @param $data['time_range']  string 时间段 00:00-00:30
    * @return array
    */
    public function getFlowIdToName($data)
    {
        if (count($data) < 1) {
            return [];
        }

        // 获取配时数据
        $timing = $this->getTimingData($data);

        // 对返回数据格式化,返回需要的格式
        if (count($timing >= 1)) {
            $timing = $this->formatTimingIdToName($timing);
        } else {
            return [];
        }

        return $timing;
    }

    /**
    * 获取详情页地图底图所需路口配时数据
    * @param $data['junction_id']     string   逻辑路口ID
    * @param $data['dates']           array    评估/诊断任务日期
    * @param $data['time_range']      string   时间段
    * @param $data['time_point']      string   时间点 PS:按时间点查询有此参数 可用于判断按哪种方式查询配时方案
    * @param $data['timingType']      interger 配时来源 1：人工 2：反推
    * @return array
    */
    public function getTimingDataForJunctionMap($data)
    {
        if (empty($data)) {
            return [];
        }
        // 获取配时数据
        $timing = $this->getTimingData($data);
        if (!$timing) {
            return [];
        }

        $result = [];
        if (!isset($data['time_point'])) { // 按方案查询
            $result = $this->formartTimingDataByPlan($timing);
        } else { // 按时间点查询
            $result = $this->formartTimingDataByTimePoint($timing, $data['time_point']);
        }

        if (!empty($result)) {
            $result_data['list'] = $this->formatTimingDataResult($result);
        }

        return $result_data;
    }

    /**
    * 获取某一相位的配时信息
    * @param $data['junction_id'] string   逻辑路口ID
    * @param $data['dates']       array    评估/诊断日期
    * @param $data['time_range']  string   时间段 00:00-00:30
    * @param $data['timingType']  interger 配时来源 1：人工 2：反推
    * @param $data['flow_id']     string   相位ID
    * @return array
    */
    public function getFlowTimingInfoForTheTrack($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];
        // 获取配时数据
        $timing = $this->getTimingData($data);
        if (!$timing || empty($timing['latest_plan']['time_plan'][0]['plan_detail'])) {
            return [];
        }

        $result = $this->formatTimingDataForTrack($timing['latest_plan']['time_plan'][0]['plan_detail'], trim($data['flow_id']));

        return $result;
    }

    /**
    * 获取flow_id对应名称的数组 用于优化
    * @param $data['junction_id'] string   逻辑路口ID
    * @param $data['dates']       array    评估/诊断日期
    * @param $data['time_range']  string   时间段 00:00-00:30
    * @param $data['timingType']  interger 配时来源 1：人工 2：反推
    * @return array
    */
    public function getFlowIdNameByTimeFrameOptimize($data)
    {
        if (empty($data)) {
            return [];
        }
        // 获取配时数据
        $timing = $this->getTimingData($data);
        if (!$timing) {
            return [];
        }

        echo "<pre>";print_r($timing);
    }

    /**
    * 格式配时数据 返回轨迹所需数据格式
    * @param $data
    * @param $flow_id
    * @return array
    */
    private function formatTimingDataForTrack($data, $flow_id)
    {
        if (empty($data) || empty($flow_id)) {
            return [];
        }

        $res = [];

        if (!isset($data['extra_timing']['cycle']) || !isset($data['extra_timing']['offset'])) {
            return [];
        }

        $res['cycle'] = $data['extra_timing']['cycle'];
        $res['offset'] = $data['extra_timing']['offset'];

        if (empty($data['movement_timing'])) {
            return [];
        }

        foreach ($data['movement_timing'] as $k=>$v) {
            if (trim($v[0]['flow_logic']['logic_flow_id']) == $flow_id) {
                foreach ($v as $kk=>$vv) {
                    if (isset($vv['state']) && isset($vv['start_time']) && isset($vv['duration'])) {
                        $res['signal'][$vv['start_time']]['state'] = $vv['state'];
                        $res['signal'][$vv['start_time']]['start_time'] = $vv['start_time'];
                        $res['signal'][$vv['start_time']]['duration'] = $vv['duration'];
                    }
                }
                $res['comment'] = $v[0]['flow_logic']['comment'];
            }
        }
        if (!empty($res['signal'])) {
            ksort($res['signal']);
        }

        return $res;
    }

    /**
    * 格式配时数据 返回按方案查询 路口地图底图所需数据格式
    * @param $data
    * @return array
    */
    private function formartTimingDataByPlan($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];
        if (!empty($data['latest_plan']['time_plan'][0]['plan_detail']['movement_timing'])) {
            foreach ($data['latest_plan']['time_plan'][0]['plan_detail']['movement_timing'] as $k=>$v) {
                if (!empty($v[0]['flow_logic']['logic_flow_id']) && !empty($v[0]['flow_logic']['comment'])) {
                    $result[$k]['logic_flow_id'] = $v[0]['flow_logic']['logic_flow_id'];
                    $result[$k]['comment'] = $v[0]['flow_logic']['comment'];
                }
            }
        }

        return $result;
    }

    /**
    * 格式配时数据 返回按时间点查询 路口地图底图所需数据格式
    * @param $data
    * @param $time_point 时间点
    */
    private function formartTimingDataByTimePoint($data, $time_point)
    {
        if (empty($data) || empty($time_point)) {
            return [];
        }

        $time_point = strtotime($time_point);
        $result = [];
        if (!empty($data['latest_plan']['time_plan'])) {
            foreach ($data['latest_plan']['time_plan'] as $k=>$v) {
                $st = strtotime($v['tod_start_time']);
                $et = strtotime($v['tod_end_time']);
                if ($time_point >= $st && $time_point < $et && !empty($v['plan_detail']['movement_timing'])) {
                    foreach ($v['plan_detail']['movement_timing'] as $kk=>$vv) {
                        if (!empty($vv[0]['flow_logic']['logic_flow_id']) && !empty($vv[0]['flow_logic']['comment'])) {
                            $result[$kk]['logic_flow_id'] = $vv[0]['flow_logic']['logic_flow_id'];
                            $result[$kk]['comment'] = $vv[0]['flow_logic']['comment'];
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
    * 格式化配时数据，返回地图底图所需格式
    * 每个方向取一，即 东直、东左 取东直且只备注为 东
    * @param $data
    * @return array
    */
    private function formatTimingDataResult($data)
    {
        if (empty($data)) {
            return [];
        }

        $position = ['东'=>1, '西'=>2, '南'=>3, '北'=>4];
        $turn = ['直'=>1, '左'=>2, '右'=>3];
        $phase_position = [];
        $temp_arr = [];
        foreach ($data as $k=>$v) {
            $comment = $v['comment'];
            foreach ($position as $k1=>$v1) {
                foreach ($turn as $k2=>$v2) {
                    if (stristr($comment, $k1.$k2) !== false) {
                        $temp_arr[$k1][str_replace($k1.$k2, $v1.$v2, $comment)]['logic_flow_id'] = $v['logic_flow_id'];
                        $temp_arr[$k1][str_replace($k1.$k2, $v1.$v2, $comment)]['comment'] = $comment;
                    }
                }
            }
        }

        foreach ($temp_arr as $key => &$value) {
            ksort($value);
            reset($value);
            $arr1 = current($value);
            $phase_position[$arr1['logic_flow_id']] = mb_substr($arr1['comment'], 0, 1, "utf-8");
        }

        return $phase_position;
    }

    /**
    * 格式化配时数据 用于返回详情页右侧数据
    * @param $data
    * @param $time_range 任务完整时间段
    * @return array
    */
    private function formatTimingData($data, $time_range)
    {
        $task_time_range = explode('-', $time_range);
        $task_start_time = $task_time_range[0];
        $task_end_time = $task_time_range[1];

        $result = [];
        // 方案总数
        $result['total_plan'] = isset($data['total_plan']) ? $data['total_plan'] : 0;

        if (!empty($data['latest_plan']['time_plan'])) {
            foreach ($data['latest_plan']['time_plan'] as $k=>$v) {
                // 方案列表
                $tod_start_time = $v['tod_start_time'];
                if (strtotime($task_start_time) > strtotime($tod_start_time)) {
                    $tod_start_time = date("H:i:s", strtotime($task_start_time));
                }
                $tod_end_time = $v['tod_end_time'];
                if (strtotime($tod_end_time) > strtotime($task_end_time)) {
                    $tod_end_time = date("H:i:s", strtotime($task_end_time));
                }
                $result['plan_list'][strtotime($tod_start_time)]['id'] = $v['time_plan_id'];
                $result['plan_list'][strtotime($tod_start_time)]['start_time'] = $tod_start_time;
                $result['plan_list'][strtotime($tod_start_time)]['end_time'] = $tod_end_time;

                // 每个方案对应的详情配时详情
                if (isset($v['plan_detail']['extra_timing']['cycle'])
                    && isset($v['plan_detail']['extra_timing']['offset'])) {
                    $result['timing_detail'][$v['time_plan_id']]['cycle'] = $v['plan_detail']['extra_timing']['cycle'];
                    $result['timing_detail'][$v['time_plan_id']]['offset'] = $v['plan_detail']['extra_timing']['offset'];
                }

                if (!empty($v['plan_detail']['movement_timing'])) {
                    foreach ($v['plan_detail']['movement_timing'] as $k1=>$v1) {
                        foreach ($v1 as $key=>$val) {
                            // 信号灯状态 1=绿灯
                            $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['state']
                            = isset($val['state']) ? $val['state'] : 0;
                            // 绿灯开始时间
                            $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['start_time']
                            = isset($val['start_time']) ? $val['start_time'] : 0;
                            // 绿灯结束时间
                            $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['duration']
                            = isset($val['duration']) ? $val['duration'] : 0;
                            // 逻辑flow id
                            $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['logic_flow_id']
                            = isset($val['flow_logic']['logic_flow_id']) ? $val['flow_logic']['logic_flow_id'] : 0;
                            // flow 描述
                            $result['timing_detail'][$v['time_plan_id']]['timing'][$k1+$key]['comment']
                            = isset($val['flow_logic']['comment']) ? $val['flow_logic']['comment'] : '';
                        }
                    }
                }

                if (!empty($result['timing_detail'][$v['time_plan_id']]['timing'])) {
                    $result['timing_detail'][$v['time_plan_id']]['timing']
                    = array_values($result['timing_detail'][$v['time_plan_id']]['timing']);
                }
            }

            // 对方案按时间正序排序
            if (!empty($result['plan_list'])) {
                ksort($result['plan_list']);
                $result['plan_list'] = array_values($result['plan_list']);
            }
        }

        return $result;
    }

    /**
    * 格式化配时数据 返回flow_id=>name结构
    * @param $data
    * @return array
    */
    private function formatTimingIdToName($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];
        if (!empty($data['latest_plan']['time_plan'][0]['plan_detail']['movement_timing'])) {
            foreach ($data['latest_plan']['time_plan'][0]['plan_detail']['movement_timing'] as $v) {
                if (!empty($v[0]['flow_logic']['logic_flow_id']) && !empty($v[0]['flow_logic']['comment'])) {
                    $result[$v[0]['flow_logic']['logic_flow_id']] = $v[0]['flow_logic']['comment'];
                }
            }
        }

        return $result;
    }

    /**
    * 获取配时数据
    * @param $data['junction_id'] string   逻辑路口ID
    * @param $data['dates']       array    评估/诊断日期
    * @param $data['time_range']  string   时间段 00:00-00:30
    * @param $data['timingType']  interger 配时数据源 0，全部；1，人工；2，配时反推；3，信号机上报
    * @return array
    */
    private function getTimingData($data)
    {
        $time_range = array_filter(explode('-', trim($data['time_range'])));
        $this->load->helper('http');

        // 获取配时详情
        $timing_data = [
                        'logic_junction_id' => trim($data['junction_id']),
                        'days'              => trim(implode(',', $data['dates'])),
                        'start_time'        => trim($time_range[0]),
                        'end_time'          => date('H:i', strtotime(trim($time_range[1])) - 60),
                        'source'            => $data['timingType']
                    ];
        try {
            $timing = httpGET(
                $this->config->item('timing_interface') . '/signal-mis/TimingService/queryTimingVersion',
                $timing_data
            );
            $timing = json_decode($timing, true);
            if (isset($timing['errorCode']) && $timing['errorCode'] != 0) {
                $content = "form_data : " . json_encode($timing_data);
                $content .= "<br>interface : " . $this->config->item('timing_interface') . '/signal-mis/TimingService/queryTimingVersion';
                $content .= '<br> result : ' . json_encode($timing);
                sendMail($this->email_to, 'logs: 获取配时数据', $content);
                return [];
            }
        } catch (Exception $e) {
            return [];
        }
        if (isset($timing['data']) && count($timing['data'] >= 1)) {
            return $timing['data'];
        } else {
            sendMail($this->email_to, 'logs: 获取配时数据', 'timing[\'data\'] is null');
            return [];
        }
    }
}
