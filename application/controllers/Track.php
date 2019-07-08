<?php
/***************************************************************
# 轨迹类
# user:ningxiangbing@didichuxing.com
# date:2018-04-13
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');
use Services\TimeframescatterService;
use Services\DiagnosisNoTimingService;
use Services\TimingAdaptionAreaService;
class Track extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('track_model');
        $this->load->model('traj_model');
        $this->setTimingType();
        $this->timeframescatterService = new TimeframescatterService();
        $this->dianosisService = new DiagnosisNoTimingService();
        $this->timingAdaptionAreaService = new TimingAdaptionAreaService();
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
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params,
            [
//                'task_id'     => 'min:1',
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
                $this->errmsg = '参数time_point不可为空！';
                return;
            }
        } else {
            if (empty($params['time_range'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数time_range不可为空！';
                return;
            }
            $time_range = array_filter(explode('-', $params['time_range']));
            if (empty($time_range[0]) || empty($time_range[1])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数time_range传递错误！';
                return;
            }
        }

//        $data = [
////            'task_id'         => intval($params['task_id']),
//            'junction_id'     => strip_tags(trim($params['junction_id'])),
//            'flow_id'         => strip_tags(trim($params['flow_id'])),
//            'search_type'     => intval($params['search_type']),
//            'time_point'      => strip_tags(trim($params['time_point'])),
//            'time_range'      => strip_tags(trim($params['time_range'])),
//            'timingType'      => $this->timingType
//        ];
        $data = [
            'city_id' => strip_tags(trim($params['city_id'])),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'dates' => $params['dates'],
            'time_range' => strip_tags(trim($params['time_range'])),
            'flow_id' => strip_tags(trim($params['flow_id'])),
            'timingType' => $this->timingType
        ];

//        $result_data = $this->track_model->getTrackData($data, 'getScatterMtraj');
        $result_data = $this->timeframescatterService->getTrackDataNoTaskId($data);
        if(empty($result_data)){
            return $this->response($result_data);
        }
        $result_data['signal_detail']=[];
        foreach ($result_data['planList'] as $k => $v){

                $result_data['signal_detail']['cycle']=$v['plan']['cycle'];
                $greenLength = 0;
                foreach ($v['list'] as $lk =>$lv){
                    $greenLength += $lv['duration'];
                }
                $result_data['signal_detail']['green_duration']=$greenLength;
                $result_data['signal_detail']['red_duration']=$v['plan']['cycle']-$greenLength;
                break;

        }

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
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params,
            [
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
                $this->errmsg = '参数time_point不可为空！';
                return;
            }
        } else {
            if (empty($params['time_range'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数time_range不可为空！';
                return;
            }
            $time_range = array_filter(explode('-', $params['time_range']));
            if (empty($time_range[0]) || empty($time_range[1])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数time_range传递错误！';
                return;
            }
        }

        $params = [
            'city_id' => $this->input->post("city_id", TRUE),
            'flow_id' => $this->input->post("flow_id", TRUE),
            'time_range' => $this->input->post("time_range", TRUE),
            'junction_id' => $this->input->post("junction_id", TRUE),
            'dates' => $this->input->post("dates", TRUE),
        ];
        $params["time_range"] = strip_tags(trim($params['time_range']));
        if(empty($params["dates"])){
            throw new \Exception("dates 不能为空");
        }
        foreach ($params["dates"] as $key=>$date){

            if (!preg_match('/\d{4,4}\d{1,2}\d{1,2}/ims',$date)){
                throw new \Exception("dates参数格式错误");
            }

            $params["dates"][$key] = substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2);
        }

        //构造配时数据
        // 获取 配时信息 周期 相位差 绿灯开始结束时间
        $timing_data = [
            'junction_id' => $params['junction_id'],
            'dates'       => $this->input->post("dates", TRUE),
            'time_range'  => $params['time_range'],
            'flow_id'     => trim($params['flow_id']),
            'timingType'  => 1
        ];

        //$data = [
        //    'city_id'=>$params['city_id'],
        //    'logic_junction_id'=>$params['junction_id'],
        //    'dates'       => $this->input->post("dates", TRUE),
        //    'time_range'  => $params['time_range'],
        //    'logic_flow_id'=>$params['flow_id'],
        //];
        //$clockShift = $this->timingAdaptionAreaService->getClockShift($data);
        $signalInfo = []; //配时数据
        $signalRange = []; //配时描述数据
        //配时查询
        $timing = $this->timing_model->getFlowTimingInfoForTheTrack($timing_data);
        $clockShift=0;
        //重构配时相关内容
        list($startTime, $endTime) = explode("-", $params['time_range']); 
        if(!empty($timing) && isset($timing['signal'])){
            $formatGreen = [];
            foreach ($timing['signal'] as $sk => $sv){
                $formatGreen[] = [
                    "green_start"    => $sv["start_time"],
                    "green_duration" => $sv["duration"],
                    "yellow"         => $sv["yellow"],
                    "green"          => $sv["green"],
                    "red_clean"      => $sv["red_clearance"],
                ];
            }
            $clockParam = [
                "dates"=>$this->input->post("dates", TRUE),
                    "junction_list"=>[
                        [
                            "junction_id"=>$params['junction_id'],
                            "start_time"=>$startTime,
                            "end_time"=>$endTime,
                            "movement"=>[
                                [
                                    "movement_id"=>$params['flow_id'],
                                    "green"=>$formatGreen,
                                ]
                            ],
                            "cycle"=>$timing['cycle'],
                            "offset"=>$timing['offset'],
                        ],
                    ]
                ];
            $clockShiftInfo = $this->traj_model->getClockShiftCorrect(json_encode($clockParam));
            if(!empty($clockShiftInfo)){
                $clockShift = $clockShiftInfo[0]['clock_shift'] ?? 0;
            }

            $signalInfo['cycle'] = $timing['cycle'];
            $signalInfo['offset'] = $timing['offset'];
            $signalInfo['yellow'] = 3;
            foreach ($timing['signal'] as $sk => $sv){
                $signalInfo['green'][] = $sv;
            }
            $new_offset = ($timing['offset'] + $clockShift) % $timing['cycle'];
            $cycle_start_time = $new_offset;
            $cycle_end_time = $timing['cycle'] + $new_offset;
            $bf_green_end = $cycle_start_time;
            // 剩余时间 默认整个周期
            $surplus_time = $cycle_end_time;

            foreach ($timing['signal'] as $k=>$v) {
                if ($v['state'] == 1) { // 绿灯
                    $green_start = $v['start_time'] + $cycle_start_time;
                    // 当绿灯开始时间 == 周期开始时间
                    if ($green_start == $cycle_start_time) {
                        // 信号灯状态 0 红灯 1绿灯
                        $signalRange[$green_start]['type'] = 1;
                        // 本次绿灯开始时间
                        $signalRange[$green_start]['from'] = $green_start;
                        // 本次绿灯结束时间
                        $signalRange[$green_start]['to'] = $green_start + $v['duration'];
                        // 与上次绿灯结束时间比较 如果大于且小于周期结束时间，则标记红灯 PS:$timing['signal']已按时间正序排列
                    } elseif ($green_start > $bf_green_end && $green_start < $cycle_end_time) {
                        // 信号灯状态 0 红灯 1绿灯
                        $signalRange[$bf_green_end]['type'] = 0;
                        // 红灯开始时间 上次绿灯结束时间
                        $signalRange[$bf_green_end]['from'] = $bf_green_end;
                        // 红灯结束时间 本次绿灯开始时间
                        $signalRange[$bf_green_end]['to'] = $green_start;

                        // 信号灯状态 0 红灯 1绿灯
                        $signalRange[$green_start]['type'] = 1;
                        // 本次绿灯开始时间
                        $signalRange[$green_start]['from'] = $green_start;
                        // 本次绿灯结束时间
                        $signalRange[$green_start]['to'] = $green_start + $v['duration'];
                    }
                    // 更新上一次绿灯结束时间
                    $bf_green_end = $green_start + $v['duration'];

                    // 更新剩余时间
                    $surplus_time = $cycle_end_time - ($green_start + $v['duration']);
                }
            }
            if ($surplus_time > 0) {
                // 信号灯状态 0 红灯 1绿灯
                $signalRange[$bf_green_end]['type'] = 0;
                // 红灯开始时间 上次绿灯结束时间
                $signalRange[$bf_green_end]['from'] = $bf_green_end;
                // 红灯结束时间 本次绿灯开始时间
                $signalRange[$bf_green_end]['to'] = $bf_green_end + $surplus_time;
            }

            if (!empty($signalRange)) {
                $signalRange = array_values($signalRange);
            }

        }
        //轨迹查询
        $result_data = $this->dianosisService->getSpaceTimeDiagram($params);
        $dataList = []; // 轨迹集合
        $info = []; //轨迹描述数据


        //数据合并
        foreach ($result_data as $rk=>$rv){
            $info['comment'] = $rv['flow_label'];
            $dataList = array_merge($dataList,$rv['dataList']);
        }

        // 从路网获取路口flow信息


        //轨迹抽样,考虑前端性能问题,暂时上限200
        if (count($dataList) >200){
            $dataList = array_rand($dataList,200);
        }
        $cycle=0;
        $offset=0;
        if(!empty($signalInfo)){
            $cycle=$signalInfo['cycle'];
            $offset = $signalInfo['offset'];
        }
        foreach ($dataList as $k=>$v){
            $dataList[$k] = $this->timingAdaptionAreaService->getTrajsInOneCycle($v
                , $cycle
                , ($offset + $clockShift) % $cycle);
        }


        $info['id'] = $params['flow_id'];
        $info['x']=[
            'max'=>-999999,
            'min'=>9999999
        ];
        $info['y']=[
            'max'=>-999999,
            'min'=>9999999
        ];
        foreach ($dataList as $dk=>$dv){
            foreach ($dv as $k => $v){
                if($v[0]>$info['x']['max']){
                    $info['x']['max']=$v[0];
                }
                if($v[0]<$info['x']['min']){
                    $info['x']['min']=$v[0];
                }
                if($v[1]>$info['y']['max']){
                    $info['y']['max']=$v[1];
                }
                if($v[1]<$info['y']['min']){
                    $info['y']['min']=$v[1];
                }
            }
        }


        $finalRet = [];
        $finalRet['dataList'] = $dataList;
        $finalRet['info'] = $info;
        $finalRet['signal_info'] = $signalInfo;
        $finalRet['signal_range'] = $signalRange;
        $finalRet['clock_shift'] = $clockShift;

        return $this->response($finalRet);
    }

}
