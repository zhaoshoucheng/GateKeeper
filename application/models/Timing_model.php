<?php
/********************************************
# desc:    配时数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-08
********************************************/

class Timing_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('nconf');
        $this->load->model("waymap_model");
        $this->load->helper('http');
    }

    public function getJuncReleaseHistory($junctionID,$startTime,$endTime){
        //debug
        // $startTime = "2019-08-22 11:13:06";
        // $junctionID = "2017030116_5125334";
        $this->load->helper('http');
        $urlStr = sprintf($this->config->item('signal_timing_releasehistory'),$junctionID,$startTime,$endTime);
        $pData = ["logic_junction_id"=>$junctionID,"start_time"=>$startTime,"end_time"=>$endTime,];
        $timing = httpPOST($this->config->item('signal_timing_releasehistory'),$pData);
        $timing = json_decode($timing, true);
        if (isset($timing['errorCode']) && $timing['errorCode'] != 0) {
            throw new \Exception('获取配时详情失败: ' . $timing['errorMsg'], ERR_DEFAULT);
        }
        if (isset($timing['data']) && count($timing['data']) >= 1) {
            return $timing['data'];
        } else {
            return [];
        }
    }

    public function getJuncTimingHistory($junctionID,$startTime,$endTime){
        $this->db = $this->load->database('default', true);
        $res = $this->db->select("*")
            ->from("adapt_timing_history")
            ->where('logic_junction_id', $junctionID)
            ->where('time_point >=',$startTime)
            ->where('time_point <=',$endTime)
            ->order_by('time_point desc')
            ->get();
        $result = $res instanceof CI_DB_result ? $res->result_array() : $res;
        return $result;
    }

    public function getScatsJunctions($cityId){
        $this->db = $this->load->database('traffic_timing_solve', true);
        $res = $this->db->select("DISTINCT(junction_id)")
            ->from("scats_phase_mapping")
            ->where('city_id', $cityId)
            ->get();
        $result = $res instanceof CI_DB_result ? $res->result_array() : $res;
        return $result;
    }
    
    /**
     * 获取路口配时时间方案
     * @param $data['junction_id'] string   逻辑路口ID
     * @param $data['dates']       array    评估/诊断日期
     * @param $data['time_range']  string   时间段 00:00-00:30
     * @return array
     */
    public function getOptimizeTiming($data)
    {
        if (empty($data)) {
            return [];
        }

        // 获取配时数据
//        $timing = $this->getTimingData($data);
        $timing = $this->getNewTimingInfo($data);


        // 对返回数据格式化,返回需要的格式
        if (count($timing) >= 1) {
            $timing = $this->formatTimingDataByOptimize($timing, $data['time_range']);
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
        $timing = $this->getNewTimingInfo($data);
        if(!empty($timing)){
            $flowId2Name = $this->formatTimingIdToName($timing);
        }
        if (count($flowId2Name) == 0) {
            $info32 = $this->waymap_model->getFlowInfo32(trim($data['junction_id']));
            $flowId2Name = array_column($info32,"phase_name","logic_flow_id");
        }
        return $flowId2Name;
    }

    /**
     * 获取详情页地图底图所需路口配时数据
     * @param $data['junction_id']     string   逻辑路口ID
     * @param $data['dates']           array    评估/诊断任务日期
     * @param $data['time_range']      string   时间段
     * @param $data['time_point']      string   时间点 PS:按时间点查询有此参数 可用于判断按哪种方式查询配时方案
     * @param $data['timingType']      int      配时来源 1：人工 2：反推
     * @return array
     */
    public function getTimingDataForJunctionMap($data)
    {
        if (empty($data)) {
            return [];
        }
        // 获取配时数据
//        $timing = $this->getTimingData($data);

        $timing = $this->getNewTimingInfo($data);
        if (!$timing) {
            return [];
        }

        $result = [];
        if (!isset($data['time_point'])) { // 按方案查询
            $result = $this->formartTimingDataByPlan($timing);
        } else { // 按时间点查询
            $result = $this->formartTimingDataByTimePoint($timing, $data['time_point']);
        }

        $result_data['list'] = [];
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
     * @param $data['timingType']  int      配时来源 1：人工 2：反推
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
//        $timing = $this->getTimingData($data);
        $timing = $this->getNewTimingInfo($data);
        if (!$timing || empty($timing['latest_plan']['time_plan'][0]['plan_detail'])) {
            return [];
        }

        $result = $this->formatTimingDataForTrack($timing['latest_plan']['time_plan'][0]['plan_detail'], trim($data['flow_id']));

        return $result;
    }

    /**
     * 获取某一相位的配时信息并计算出所有相位最大配时周期
     * @param $data['junction_id'] string   逻辑路口ID
     * @param $data['dates']       array    评估/诊断日期
     * @param $data['time_range']  string   时间段 00:00-00:30
     * @param $data['timingType']  int      配时来源 1：人工 2：反推
     * @param $data['flow_id']     string   相位ID
     * @return array
     */
    public function gitFlowTimingByOptimizeScatter($data)
    {
        if (empty($data) || !isset($data['flow_id'])) {
            return [];
        }

        $result = [];
        // 获取配时数据
//        $timing = $this->getTimingData($data);
        $timing = $this->getNewTimingInfo($data);
        if (!empty($timing)) {
            $result = $this->formatTimingDataByOptimizeScatter($timing, $data['flow_id']);
        } else {

            $info32 = $this->waymap_model->getFlowInfo32(trim($data['junction_id']));
            $flowId2Name = array_column($info32,"phase_name","logic_flow_id");
            $flowId = trim($data['flow_id']);
            $result = [
                "info" =>[
                    'logic_flow_id' => $flowId,
                    'comment' => isset($flowId2Name[$flowId]) ? $flowId2Name[$flowId] : "",
                ],
                "maxCycle" => 0,
                "planList" => [],
            ];
        }
        return $result;
    }

    /**
     * 获取带有黄灯的配时信息接口--绿信比优化配时方案展示
     * @param $data['junction_id'] string   逻辑路口ID
     * @param $data['dates']       array    评估/诊断日期
     * @param $data['time_range']  string   时间段 00:00-00:30
     * @param $data['yellowLight'] int      黄灯时长
     * @param $data['timingType']  int      配时来源 1：人工 2：反推
     */
    public function getTimingPlan($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];
        // 获取配时数据
//        $timing = $this->getTimingData($data);
        $timing = $this->getNewTimingInfo($data);
        if (!empty($timing)) {
            $result = $this->formatTimingDataByOptimizeSplit($timing);
        }
        return $result;
    }

    /**
     * 格式化配时数据，返回绿信比优化所需数据结构
     * @param $data        array    配时数据
     * @return array
     */
    private function formatTimingDataByOptimizeSplit($data)
    {
        $result = [];

        if (empty($data['latest_plan']['time_plan']) || $data['total_plan'] < 1) {
            return [];
        }
        foreach ($data['latest_plan']['time_plan'] as $k=>$v) {
            $result[$k]['plan'] = [
                'comment'    => $v['comment'],
                'start_time' => $v['tod_start_time'],
                'end_time'   => $v['tod_end_time'],
                'cycle'      => $v['plan_detail']['extra_timing']['cycle'],
                'offset'     => $v['plan_detail']['extra_timing']['offset'],
            ];
            foreach ($v['plan_detail']['movement_timing'] as $kk=>$vv) {
                foreach ($vv as $kkk=>$vvv) {
                    $result[$k]['movements'][$vvv['flow_logic']['logic_flow_id']]['info'] = [
                        'logic_flow_id' => $vvv['flow_logic']['logic_flow_id'],
                        'comment'       => $vvv['flow_logic']['comment'],
                    ];
                    $result[$k]['movements'][$vvv['flow_logic']['logic_flow_id']]['signal'][$kkk] = [
                        'g_start_time'  => intval($vvv['start_time']),
                        'g_duration'    => intval($vvv['duration']),
                        'green'    => intval($vvv['green']),
                        'yellow'    => intval($vvv['yellow']),
                        'red_clearance'    => intval($vvv['red_clearance']),
                        'green_flash'    => intval($vvv['green_flash']),
                        // 兼容老的前端格式，新版使用yellow字段
                        'yellowLight' => intval($vvv['yellow']),
                    ];
                }
            }

            if (!empty($result[$k]['movements'])) {
                $result[$k]['movements'] = array_values($result[$k]['movements']);
            }
        }

        return $result;
    }

    /**
     * 格式化配时数据，返回时段优化散点图所需数据
     * @param $data   array  配时数据
     * @param $flowId string 相位ID
     * @return array
     */
    private function formatTimingDataByOptimizeScatter($data, $flowId)
    {
        if (empty($data['latest_plan']['time_plan']) || $data['total_plan'] < 1) {
            return [];
        }

        // 路口所有相位最大周期
        $maxCycle = 0;
        foreach ($data['latest_plan']['time_plan'] as $k=>$v) {
            if ($maxCycle < $v['plan_detail']['extra_timing']['cycle']) {
                $maxCycle = $v['plan_detail']['extra_timing']['cycle'];
            }

            foreach ($v['plan_detail']['movement_timing'] as $kk=>$vv) {
                foreach ($vv as $kkk=>$vvv) {
                    if ($flowId == $vvv['flow_logic']['logic_flow_id']) {
                        $result['planList'][strtotime($v['tod_end_time'])]['plan'] = [
                            'start_time' => $v['tod_start_time'],
                            'end_time'   => $v['tod_end_time'],
                            'comment'    => $v['comment'],
                            'cycle'      => $v['plan_detail']['extra_timing']['cycle'],
                            'offset'     => $v['plan_detail']['extra_timing']['offset'],
                        ];
                        $result['planList'][strtotime($v['tod_end_time'])]['list'][$kkk] = [
                            'state'         => $vvv['state'],
                            'start_time'    => $vvv['start_time'],
                            'duration'      => $vvv['duration'],
                        ];
                        $result['info'] = [
                            'logic_flow_id' => $vvv['flow_logic']['logic_flow_id'],
                            'comment'       => $vvv['flow_logic']['comment'],
                        ];
                    }
                }
            }
        }

        if (!empty($result['planList'])) {
            ksort($result['planList']);
            $result['planList'] = array_values($result['planList']);
        }

        $result['maxCycle'] = $maxCycle;

        return $result;
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
                        $res['signal'][$vv['start_time']]['green'] = $vv['green'];
                        $res['signal'][$vv['start_time']]['yellow'] = $vv['yellow'];
                        $res['signal'][$vv['start_time']]['red_clearance'] = $vv['red_clearance'];
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

        if (empty($data['latest_plan']['time_plan']) || $data['total_plan'] < 1) {
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

        if (empty($data['latest_plan']['time_plan']) || $data['total_plan'] < 1) {
            return [];
        }

        $time_point = strtotime($time_point);
        $result = [];
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
        $turn = ['直'=>1, '左'=>2, '右'=>3, '掉头'=>4];
        $phase_position = [];
        $temp_arr = [];
        foreach ($data as $k=>$v) {
            // 暂时只取备注的前两个字，因为有的备注是 西南口向东直行 类似这样的.
            $comment = mb_substr($v['comment'], 0, 2, "utf-8");
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
     * 格式化配时数据 用于单点时段优化所需
     * @param $data
     * @param $timeRange strign Y 任务完整时间段
     * @return array
     */
    private function formatTimingDataByOptimize($data, $timeRange)
    {
        if (empty($data['latest_plan']['time_plan']) || $data['total_plan'] < 1) {
            return [];
        }
        // 从data中抽取方案
        $tempTiming = [];
        foreach ($data['latest_plan']['time_plan'] as $v) {

            $tempTiming[strtotime($v['tod_start_time'])]['start'] = $v['tod_start_time'];
            $tempTiming[strtotime($v['tod_start_time'])]['end'] = $v['tod_end_time'];
            $tempTiming[strtotime($v['tod_start_time'])]['name'] = str_replace('方案', '', $v['comment']);
        }
        if (!empty($tempTiming)) {
            ksort($tempTiming);
            $tempTiming = array_values($tempTiming);
        }

        // 补全时段 PS：可能会存在某一个时间段没有配时方案导致时间段不连续，需要补全
        $timeRangeArr = explode('-', $timeRange);
        // 最终的时间点
        $lastTime = strtotime($timeRangeArr[1]);

        $count = count($tempTiming);
        $resultTiming = [];
        for ($i = 0; $i < $count; $i++) {
            $resultTiming[strtotime($tempTiming[$i]['end'])] = [
                    'start'   => $tempTiming[$i]['start'],
                    'end'     => $tempTiming[$i]['end'],
                    'comment' => $tempTiming[$i]['name']
                ];
            if (isset($tempTiming[$i+1]) && strtotime($tempTiming[$i]['end']) < $lastTime
                && strtotime($tempTiming[$i]['end']) < strtotime($tempTiming[$i+1]['start'])
            ) {
                $resultTiming[strtotime($tempTiming[$i+1]['start'])] = [
                    'start'   => $tempTiming[$i]['end'],
                    'end'     => $tempTiming[$i+1]['start'],
                    'comment' => '方案未知'
                ];
            }
        }

        foreach ($resultTiming as $key => $value) {
            if ($value['start'] > $value['end']) {
                unset($resultTiming[$key]);
                $resultTiming[strtotime("24:00:00")] = [
                    'start'   => $value['start'],
                    'end'     => "24:00:00",
                    'comment' => $value['comment']
                ];
                $resultTiming[strtotime($value['end'])] = [
                    'start'   => "00:00:00",
                    'end'     => $value['end'],
                    'comment' => $value['comment']
                ];
            }
        }

        if (!empty($resultTiming)) {
            ksort($resultTiming);
            $resultTiming = array_values($resultTiming);
        }

        return $resultTiming;

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

        if (empty($data['latest_plan']['time_plan']) || $data['total_plan'] < 1) {
            return [];
        }

        $result = [];
        foreach ($data['latest_plan']['time_plan'] as $k=>$v) {
            foreach ($v['plan_detail']['movement_timing'] as $kk=>$vv) {
                foreach ($vv as $vvv) {
                    $result[$vvv['flow_logic']['logic_flow_id']] = $vvv['flow_logic']['comment'];
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
     * @param $data['timingType']  int      配时数据源 0，全部；1，人工；2，配时反推；3，信号机上报
     * @return array
     */
    public function getTimingData($data)
    {
        $time_range = array_filter(explode('-', trim($data['time_range'])));
        $this->load->helper('http');
        if ($time_range[1]=="24:00"){
            $time_range[1]="23:59";
        }
        // 获取配时详情
        $timing_data = [
                        'logic_junction_id' => trim($data['junction_id']),
                        'days'              => trim(implode(',', $data['dates'])),
                        'start_time'        => trim($time_range[0]),
                        'end_time'          => date('H:i', strtotime(trim($time_range[1])) - 60),
                        'source'            => $data['timingType']
                    ];
        $timing = httpGET(
            $this->config->item('timing_interface') . '/TimingService/queryTimingVersion',
            $timing_data
        );
        $timing = json_decode($timing, true);
        if (isset($timing['errorCode']) && $timing['errorCode'] != 0) {
            throw new \Exception('获取配时详情失败: ' . $timing['errorMsg'], ERR_DEFAULT);
        }
        if (isset($timing['data']) && count($timing['data']) >= 1) {
            return $timing['data'];
        } else {
            return [];
        }
    }

    //获取新版本配时并格式化成旧版本格式
    public function getNewTimingInfo($data){
        $info32 = $this->waymap_model->getFlowInfo32(trim($data['junction_id']));
        // print_r($info32);exit;
        $flowMap = array_column($info32,"phase_name","logic_flow_id");
        //flow信息替换
        $time_range = array_filter(explode('-', trim($data['time_range'])));
        $this->load->helper('http');
        $date = $data['dates'][0];
        $reqdate = substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2);
        // 获取配时详情
        $timing_data = [
            'logic_junction_ids' => trim($data['junction_id']),
            'start_time'        => trim($time_range[0]).":00",
            'end_time'          => trim($time_range[1]).":00",
            'source'            => '2,1',
            'date'              => $reqdate,
            'format'          => 3,

        ];
        $timing = httpGET(
            $this->config->item('signal_timing_url') ,
            $timing_data
        );
        $timing = json_decode($timing, true);
        if ($timing['errno'] !=0){
            return [];
        }
        //格式化为新接口格式
        $finalData = [
            'latest_plan'=>[
                'logic_junction_id'=>$data['junction_id'],
                'time_plan'=>[],
            ],
            'total_plan'=>0,
        ];

        if(!isset($timing['data'][0]['schedules']) || count($timing['data'][0]['schedules'])==0){
            return [];
        }

        foreach ($timing['data'][0]['schedules'][0]['tods'] as $tk=>$tv){
            if($tv['end_time']=="00:00:00"){
                $tv['end_time']="24:00:00";
            }
            $plan = [
                'tod_end_time'=> $tv['end_time'],
                'tod_start_time'=> $tv['start_time'],
                'time_plan_id'=>1,
                'plan_id'=>$tv['plan']['id'],
                'plan_detail'=>[
                    'extra_timing'=>[
                        'cycle'=>$tv['plan']['cycle'],
                        'offset'=>$tv['plan']['offset'],
                    ],
                    'movement_timing'=>[],
                ],
            ];

            if (!empty($tv['plan']['movements'])) {
                foreach ($tv['plan']['movements'] as $mk => $mv) {
                    foreach ($mv['sub_phases'] as $sk => $sv) {
                        $plan['comment'] = $tv['plan']['id'];
                        $comment = "";
                        if (isset($flowMap[$mv['id']]) && $flowMap[$mv['id']] != '') {
                            $comment = $flowMap[$mv['id']];
                        }
                        if ($comment == "") {
                            $comment = "非机动车";
                        }

                        $plan['plan_detail']['movement_timing'][$mk][] = [
                            "movement_id" => $mk,
                            'start_time' => $sv['start_time'],
                            'duration' => $sv['green'] + $sv['yellow'] + $sv['red_clearance'] + $sv['green_flash'],
                            'green'    => $sv['green'],
                            'yellow'   => $sv['yellow'],
                            'red_clearance'=> $sv['red_clearance'],
                            'green_flash'=> $sv['green_flash'],
                            'state' => 1,
                            'flow_logic' => [
                                'comment' => $comment,
                                'logic_flow_id' => $mv['id'],
                            ],
                        ];
                    }

                }
            }

            $finalData['latest_plan']['time_plan'][] = $plan;
        }
        $finalData['total_plan'] = count($finalData['latest_plan']['time_plan']);
        return $finalData;
    }


    /**
     * 批量获取路口配时数据(http://wiki.intra.xiaojukeji.com/pages/viewpage.action?pageId=125744261#id-配时API-根据获取多数据源配时信息)
     * @param $data['junction_ids']
     * @param $data['days']
     * @param $data['start_time']
     * @param $data['end_time']
     * @param $data['version']
     * @param $data['source']
     * @return array
     */
    public function getTimingDataBatchBak($data)
    {

        $this->load->helper('http');

        // 获取配时详情
        $timing_data = [
            'junction_ids'      => trim($data['junction_ids']),
            'days'              => trim($data['days']),
            'start_time'        => $data['start_time'],
            'end_time'          => $data['end_time'],
            'source'            => $data['source']
        ];
        try {
            $timing = httpGET(
                $this->config->item('timing_interface') . '/TimingService/queryTimingVersionBatch',
                $timing_data
            );
            $timing = json_decode($timing, true);
            if (isset($timing['errorCode']) && $timing['errorCode'] != 0) {
                return [];
            }
        } catch (Exception $e) {
            return [];
        }

        return $timing['data'];
    }


    //新版本配时
    public function getNewTimngData($data)
    {
        $this->load->helper('http');
        // 获取配时详情
        $timing_data = [
            'logic_junction_ids'      => $data['logic_junction_id'],
            'source'              => $data['source'],
            'start_time'        => $data['start_time'],
            'end_time'          => $data['end_time'],
            'date'            => $data['date'],
            'format' => 1,
        ];
        try {
            $timing = httpGET(
                $this->config->item('signal_timing_url'),
                $timing_data
            );
            $timing = json_decode($timing, true);
            if (isset($timing['errno']) && $timing['errno'] != 0) {
                return [];
            }
        } catch (Exception $e) {
            return [];
        }

        return $timing['data'][0];


    }

    // 获取配时信息，单个路口或者批量
    public function getNewTiming($data) {
        // 获取配时详情
        $timing_data = [
            'logic_junction_ids'      => isset($data['logic_junction_id']) ? $data['logic_junction_id'] : $data['logic_junction_ids'],
            'source'            => $data['source'],
            'start_time'        => $data['start_time'],
            'end_time'          => $data['end_time'],
            'date'              => $data['date'],
            'format'            => $data['format'],
        ];
        try {
            $timing = httpGET(
                $this->config->item('signal_timing_url'),
                $timing_data
            );
            $timing = json_decode($timing, true);
            if (isset($timing['errno']) && $timing['errno'] != 0) {
                return [];
            }
        } catch (Exception $e) {
            return [];
        }

        return isset($data['logic_junction_id']) ? $timing['data'][0] : $timing['data'];
    }

    public function getTimingDataBatch($data)
    {
        $this->load->helper('http');

        // 获取配时详情
        $timing_data = [
            'junction_ids'      => trim($data['junction_ids']),
            'days'              => trim($data['days']),
            'start_time'        => $data['start_time'],
            'end_time'          => $data['end_time'],
            'source'            => $data['source']
        ];
        try {
            $timing = httpGET(
                $this->config->item('timing_interface') . '/TimingService/queryTimingVersionBatch',
                $timing_data
            );
            $timing = json_decode($timing, true);
            if (isset($timing['errorCode']) && $timing['errorCode'] != 0) {
                return [];
            }
        } catch (Exception $e) {
            return [];
        }

        return $timing['data'];
    }

    /**
     * 临时需求,上传优化后的配时
     */
    public function uploadTimingData($data)
    {
        $authorization = "Authorization: Basic ".base64_encode("test:1234");
        $res = httpPOST('http://10.148.28.204:8001/xinkong/profile/static/release',$data,0,'json',array($authorization));

        return $res;

    }

    // 查询路口配时状态
    public function queryTimingStatus($data)
    {
        // $authorization = "Authorization: Basic ".base64_encode("test:1234");
        $timing = httpPOST($this->config->item('signal_timing_status_url'), $data, 0, 'json');
        $timing = json_decode($timing, true);
        if (isset($timing['errno']) && $timing['errno'] != 0) {
            return [];
        }
        if (empty($timing['data'])) {
            return [];
        }
        return $timing['data'];
    }
    

    // 查询海信路口
    public function getHaixinJunctionID($spotID) 
    {
        //查询海信平台通道号，并生成映射map
        $this->timing_db = $this->load->database('traffic_timing_solve', true);
        $res = $this->timing_db
            ->from("signaller_ext")
            ->where('signaller_id', $spotID)
            ->where('city_id', "38")
            ->get();
        $spotArr = $res instanceof CI_DB_result ? $res->result_array() : $res;
        if(empty($spotArr)){
            return "";
        }
        return $spotArr[0]["logic_junction_id"];
    }

    // 查询海信路口
    public function getHaixinSpotID($data) 
    {
        //查询海信平台通道号，并生成映射map
        $this->timing_db = $this->load->database('traffic_timing_solve', true);
        $res = $this->timing_db
            ->from("haixin_channel_map")
            ->where('junction_id', $data["logic_junction_id"])
            ->get();
        $spotArr = $res instanceof CI_DB_result ? $res->result_array() : $res;
        if(empty($spotArr)){
            return "";
        }
        return $spotArr[0]["spot_id"];
    }

    // 查询路口通道号
    public function queryFlowChannel($data) 
    {
        //查询海信平台通道号，并生成映射map
        $this->timing_db = $this->load->database('traffic_timing_solve', true);
        $res = $this->timing_db
            ->from("haixin_channel_map")
            ->where('junction_id', $data["logic_junction_id"])
            ->get();
        $channelMap = $res instanceof CI_DB_result ? $res->result_array() : $res;
        if(empty($channelMap)){
            return [];
        }
        $hxcMap = [];
        foreach ($channelMap as $key => $value) {
            $hxcMap[$value["sg_num"]] = $value["hx_num"];
        }

        $timing = httpGET($this->config->item('signal_timing_flowchannel'), $data);
        $timing = json_decode($timing, true);
        if (isset($timing['errno']) && $timing['errno'] != 0) {
            return [];
        }
        if (empty($timing['data'])) {
            return [];
        }
        $flowChannelMap = [];
        foreach($timing['data'] as $dv){
            if(isset($hxcMap[$dv["sg_num"]])){
                $flowChannelMap[$dv["logic_flow_id"]] = $hxcMap[$dv["sg_num"]];
            }
        }
        return $flowChannelMap;
    }


}
