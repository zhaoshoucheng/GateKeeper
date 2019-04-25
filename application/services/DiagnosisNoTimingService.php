<?php
namespace Services;
use Didi\Cloud\Collection\Collection;

/**
 * Class DiagnosisNoTimingService
 * @package Services
 * @property \DiagnosisNoTiming_model $diagnosisNoTiming_model
 * @property \Traj_model $traj_model
 * @property \Arterialtiming_model $arterialtiming_model
 */
class DiagnosisNoTimingService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('disgnosisnotiming_conf');
        $this->load->config('nconf');
        $this->load->model('diagnosisNoTiming_model');
        $this->load->model('traj_model');
        $this->load->model('arterialtiming_model');
    }

    public function getJunctionQuotaTrend($params)
    {
        $result = [];
        $timePoints = splitTimeDurationToPoints("00:00-23:30");
        $result["junction_question"] = $this->diagnosisNoTiming_model->getJunctionQuotaTrend(
            $params['junction_id'], $timePoints, $params['dates']);
        return $result;
    }


    /**
     * 获取路口指标详情
     * @param $params ['city_id']     string   城市ID
     * @param $params ['junction_id']     string   逻辑路口ID
     * @param $params ['time_point']      string   时间点      必传
     * @param $params ['dates']           string   日期范围     必传
     * @return array
     */
    public function getFlowQuotas($params)
    {
        $timePoints = splitTimeDurationToPoints($params['time_range']);
        $result = [];

        //定义路口问题阈值规则
        $result["junction_question"] = $this->diagnosisNoTiming_model->getJunctionAlarmList(
            $params['junction_id'], $timePoints, $params['dates']);

        //定义指标名称及注释
        $quotaKey = $this->config->item('flow_quota_key');
        $result["flow_quota_all"] = $quotaKey;

        //movements从路网获取方向信息
        $result["movements"] = $this->diagnosisNoTiming_model->getMovementQuota(
            $params['city_id'], $params['junction_id'], $timePoints, $params['dates']);

        $result["junction_id"] = $params["junction_id"];
        return $result;
    }

    /**
     * 获取路口地图底图数据
     * @param $data
     * @return array
     */
    public function getJunctionMapData($params)
    {
        $result = $this->diagnosisNoTiming_model->getJunctionMapData($params);
        return $result;
    }

    public function getSpaceTimeDiagram($params)
    {
        $startTime = $params['time_point'].":00";
        $endTime = date("H:i:s", strtotime(date("Y-m-d")." ".$params['time_point'].":00")+30*60);
        $formatDate = date("Ymd",strtotime($params['date']));

        //获取配时信息:兼容老格式的传参
        $juncData = [[
            "logic_junction_id"=>$params['junction_id'],    //只传一个junc
            "flows"=>[$params['flow_id'],],                 //只传一个flow
        ]];
        $timingInfo = $this->arterialtiming_model->getJunctionTimingInfos($juncData,$endTime,$formatDate);
        //$timingInfo = $this->arterialtiming_model->tmpGetNewJunctionTimingInfos($data,$timePoint,$date[0]);

        //获取当前时段配时信息
        $juncTiming = $timingInfo[$params['junction_id']][0] ?? [];
        if(empty($juncTiming['timing_info']['extra_timing'])){
            return [];
        }
        $cycle = $juncTiming['timing_info']['extra_timing']['cycle'];
        $offset = $juncTiming['timing_info']['extra_timing']['offset'];
        $yellow = 3;
        $formatGreen = [];
        foreach ($juncTiming['timing_info']['movement_timing'] as $item){
            $formatGreen[] = [
                "green_start"=>$item['start_time'],
                "green_duration"=>$item['duration'],
                "yellow"=>0,
                "red_clean"=>0,
            ];
        }
        $formatGreen2 = [];
        foreach ($juncTiming['timing_info']['movement_timing'] as $item){
            $formatGreen2[] = [
                "start_time"=>$item['start_time'],
                "duration"=>$item['duration'],
            ];
        }

        //获取纠偏offset
        $clockParam = [
            "dates"=>[$formatDate],
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
                    "cycle"=>$cycle,
                    "offset"=>$offset,
                ],
            ]
        ];
        $clockShiftInfo = $this->traj_model->getClockShiftCorrect(json_encode($clockParam));
        if(empty($clockShiftInfo)){
            return [];
        }
        $clockShift = $clockShiftInfo[0]['clock_shift'] ?? 0;

        //获取相位信息
        $flowMovement = $this->waymap_model->getFlowMovement($params['city_id'], $params['junction_id'], $params['flow_id']);
        if(empty($flowMovement)){
            return [];
        }
        $trajParam = [
            "junctions"=>[
                [
                    "junction_id"=>$flowMovement['junction_id'],
                    "forward_flow_id"=>$flowMovement['logic_flow_id'],
                    "reverse_flow_id"=>"",
                    "forward_in_links"=>$flowMovement['in_link_ids'],
                    "forward_out_links"=>$flowMovement['out_link_ids'],
                    "reverse_in_links"=>"",
                    "reverse_out_links"=>"",
                    "junction_inner_links"=>$flowMovement['inner_link_ids'],
                    "tod_start_time"=>$startTime,
                    "tod_end_time"=>$endTime,
                    "cycle"=>(string) $cycle,
                    "offset"=>(string) $offset,
                ],
            ],
            "task_id"=>"1", //不能为空
            "time_point"=>$params['time_point'],
            "method"=>"1",
            "map_version"=>"1",//不能为空
            "token"=>"1",//不能为空
            "dates"=>[$formatDate],
        ];
        $trajInfo = $this->traj_model->getSpaceTimeDiagram($trajParam);
        $trajList = $trajInfo['dataList'][0]['forward_traj'] ?? [];
        foreach ($trajList as $key=>$item){
            foreach ($item as $k=>$value){
                $trajList[$key][$k] = [
                    $value[0],                      // 时间秒数         X轴
                    $value[1] * -1                  // 停车线距离值      Y轴
                ];
            }
            //获取把时空图轨迹压缩在一个周期内
            $trajList[$key] = $this->getTrajsInOneCycle($trajList[$key]
                , $cycle
                , ($offset + $clockShift) % $cycle);   //纠偏
        }

        $result = [];
        $result['dataList'] = $trajList;
        $trajs = Collection::make($trajList);
        $result['info'] = [
            "x" => [
                "max" => $trajs->collapse()->column(0)->max(),
                "min" => $trajs->collapse()->column(0)->min(),
            ],
            "y" => [
                "max" => $trajs->collapse()->column(1)->max(),
                "min" => $trajs->collapse()->column(1)->min(),
            ],
        ];
        $result['signal_info'] = [
            'cycle'=>$cycle,
            'offset'=>$offset,
            'tod_end_time'=>$startTime,
            'tod_start_time'=>$endTime,
            'yellow'=>$yellow,
            'green'=>$formatGreen2,
        ];
        return $result;
    }

    public function getScatterDiagram($params)
    {
        $startTime = $params['time_point'].":00";
        $endTime = date("H:i:s", strtotime(date("Y-m-d")." ".$params['time_point'].":00")+30*60);
        $formatDate = date("Ymd",strtotime($params['date']));

        //获取配时信息:兼容老格式的传参
        $juncData = [[
            "logic_junction_id"=>$params['junction_id'],    //只传一个junc
            "flows"=>[$params['flow_id'],],                 //只传一个flow
        ]];
        $timingInfo = $this->arterialtiming_model->getJunctionTimingInfos($juncData,$endTime,$formatDate);
        //$timingInfo = $this->arterialtiming_model->tmpGetNewJunctionTimingInfos($data,$timePoint,$date[0]);

        //获取当前时段配时信息
        $juncTiming = $timingInfo[$params['junction_id']][0] ?? [];
        if(empty($juncTiming['timing_info']['movement_timing'])){
            return [];
        }
        $cycle = $juncTiming['timing_info']['extra_timing']['cycle'];
        $offset = $juncTiming['timing_info']['extra_timing']['offset'];
        $yellow = 3;
        $formatGreen = [];
        foreach ($juncTiming['timing_info']['movement_timing'] as $item){
            $formatGreen[] = [
                "green_start"=>$item['start_time'],
                "green_duration"=>$item['duration'],
                "yellow"=>0,
                "red_clean"=>0,
            ];
        }
        $formatGreen2 = [];
        foreach ($juncTiming['timing_info']['movement_timing'] as $item){
            $formatGreen2[] = [
                "start_time"=>$item['start_time'],
                "duration"=>$item['duration'],
            ];
        }

        //获取纠偏offset
        $clockParam = [
            "dates"=>[$formatDate],
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
                    "cycle"=>$cycle,
                    "offset"=>$offset,
                ],
            ]
        ];

        //获取相位信息
        $flowMovement = $this->waymap_model->getFlowMovement($params['city_id'], $params['junction_id'], $params['flow_id']);
        if(empty($flowMovement)){
            return [];
        }
        $trajParam = [
            "junctions"=>[
                [
                    "junction_id"=>$flowMovement['junction_id'],
                    "forward_flow_id"=>$flowMovement['logic_flow_id'],
                    "reverse_flow_id"=>"",
                    "forward_in_links"=>$flowMovement['in_link_ids'],
                    "forward_out_links"=>$flowMovement['out_link_ids'],
                    "reverse_in_links"=>"",
                    "reverse_out_links"=>"",
                    "junction_inner_links"=>$flowMovement['inner_link_ids'],
                    "tod_start_time"=>$startTime,
                    "tod_end_time"=>$endTime,
                    "cycle"=>(string) $cycle,
                    "offset"=>(string) $offset,
                ],
            ],
            "task_id"=>"1", //不能为空
            "time_point"=>$params['time_point'],
            "method"=>"1",
            "map_version"=>"1",//不能为空
            "token"=>"1",//不能为空
            "dates"=>[$formatDate],
        ];
        $trajInfo = $this->traj_model->getSpaceTimeDiagram($trajParam);
        $trajList = $trajInfo['dataList'][0]['forward_stop_delay'] ?? [];
        $result = [];
        $result['dataList'] = $trajList;
        $trajs = Collection::make($trajList);
        $result['info'] = [
            "x" => [
                "max" => $trajs->column(1)->max(),
                "min" => $trajs->column(1)->min(),
            ],
            "y" => [
                "max" => $trajs->column(0)->max(),
                "min" => $trajs->column(0)->min(),
            ],
        ];
        $result['signal_info'] = [
            'cycle'=>$cycle,
            'offset'=>$offset,
            'tod_end_time'=>$startTime,
            'tod_start_time'=>$endTime,
            'yellow'=>$yellow,
            'green'=>$formatGreen2,
        ];
        return $result;
    }

    private function getTrajsInOneCycle(array $trajs, int $cycleLength, int $offset)
    {
        $trajsCol = Collection::make($trajs);

        //求距离停车线最近距离
        $min = $trajsCol->reduce(function ($a, $b) {
            if ($a == null) {
                return $b;
            }
            if (abs($a[1]) < abs($b[1])) {
                return $a;
            }
            return $b;
        });
        $crossTime = $min[0]; // 该轨迹最小点过路口时间
        $shiftTime = $crossTime - (($crossTime - $offset) % $cycleLength);  //该轨迹最小点过路口时间,时间偏移量
        //$crossTime - $shiftTime 相对时间
        $minTime = $crossTime - $shiftTime - 2 * $cycleLength;    //最小周期设置为2倍周期范围内
        $maxTime = $crossTime - $shiftTime + 1.5 * $cycleLength;  //最大周期设置为1.5倍周期范围内

        $ret = [];
        foreach ($trajs as $traj) {
            $time = $traj[0] - $shiftTime;
            if ($time < $minTime || $time > $maxTime) {
                continue;
            }

            $ret[] = [$time, $traj[1]];
        }
        return $ret;
    }

    public function getJunctionAlarmDataByHour($params) {
        $city_id = $params['city_id'];
        $dates = $params['dates'];
        $res = $this->diagnosisNoTiming_model->getJunctionAlarmDataByHour($city_id, $dates);

        $conf_rule = $this->config->item('conf_rule');
        $frequency_threshold = $conf_rule['frequency_threshold'];
        $alarm_types = $conf_rule['alarm_types'];

        $ret = [];
        foreach (array_keys($alarm_types) as $alarm_type) {
            if ($alarm_type == "is_oversaturation") {
                $key = 'oversaturation_index';
                $name = '过饱和';
            } elseif ($alarm_type == "is_spillover") {
                $key = 'spillover_index';
                $name = '溢流';
            } elseif ($alarm_type == "is_imbalance") {
                $key = 'imbalance_index';
                $name = '失衡';
            }
            $ret[$key]['name'] = $name;
            foreach ($dates as $date) {
                $ret[$key]['index'][$date] = [];
                if (isset($res['all'][$date])) {
                    foreach ($res['all'][$date] as $k => $v) {
                        if($res[$alarm_type][$date][$k] != 0) {
                            $ret[$key]['index'][$date][$k] = round($res[$alarm_type][$date][$k] / $res['all'][$date][$k] * 100, 2) .  '%';
                        }
                    }
                }
            }
        }
        return $ret;
    }

    public function getAllCityJunctionsDiagnoseList($params) {
        $city_id = $params['city_id'];
        $dates = $params['dates'];
        $hour = $params['hour'];
        // es alarm
        $res = $this->diagnosisNoTiming_model->getJunctionAlarmDataByJunction($city_id, $dates, $hour);
        // avg speed and delay
        $rest = $this->diagnosisNoTiming_model->GetJunctionAlarmDataByJunctionAVG($city_id, $dates, $hour);
        // city junctions
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($city_id);
        $junctionsPos = [];
        foreach ($allCityJunctions as $v) {
            $junctionsPos[$v['logic_junction_id']] = $v;
        }

        $ret = [];
        $conf_rule = $this->config->item('conf_rule');
        $frequency_threshold = $conf_rule['frequency_threshold'];
        $alarm_types = $conf_rule['alarm_types'];
        $alarm_quotas = $conf_rule['alarm_quotas'];

        $lngs = 0.0;
        $lats = 0.0;
        $cnt = count($res);
        $is_oversaturation_cnt = 0;
        $is_imbalance_cnt = 0;
        $is_spillover_cnt = 0;

        foreach ($alarm_types as $alarm_type => $detail) {
            ${$detail['cnt']} = 0;
        }

        $ret['dataList'] = [];
        $ret['rankList'] = [];
        $ret['junctionTotal'] = $cnt;
        foreach ($res as $k => $v) {
            if (!isset($junctionsPos[$k])) {
                continue;
            }
            $v['lng'] = $junctionsPos[$k]['lng'];
            $v['lat'] = $junctionsPos[$k]['lat'];
            $v['name'] = $junctionsPos[$k]['name'];
            $lngs += $v['lng'];
            $lats += $v['lat'];

            foreach ($alarm_types as $alarm_type => $detail) {
                if (1.0 * $v[$alarm_type] / $v['count'] > $frequency_threshold) {
                    $v[$alarm_type] = 1;
                    $ret['rankList'][$alarm_map[$alarm_type]['index']][] = [
                        "junction_id"=> $k,
                        "junction_label"=> $v['name'],
                        "value"=> $v['delay'],
                    ];
                   ${$detail['cnt']} ++;
                } else {
                    $v[$alarm_type] = 0;
                }
            }

            $ret['dataList'][] = [
                'logic_junction_id' => $k,
                'name' => $v['name'],
                'lng' => $v['lng'],
                'lat' => $v['lat'],
                'diagnose_detail' => [],
                'info' => [
                    'quota' => [
                        'value' => $v['delay'],
                        "name"=> "平均延误",
                        "unit"=> "秒",
                    ],
                    'question' => $this->getQuestions($v, $alarm_types),
                ],
            ];
        }

        $ret['center'] = [
            'lng' => $lngs / $cnt,
            'lat' => $lats / $cnt,
        ];

        $ret['quotaCount'] = [];
        foreach ($alarm_quotas as $key => $value) {
            $ret['quotaCount'][] = [
                'name' => $value['name'],
                'value' => $rest[$key],
                'unit' => $value['unit'],
            ];
        }

        $ret['count'] = [];
        foreach ($alarm_types as $alarm_type => $detail) {
            $ret['count'][$detail['index']] = [
                "num" => ${$detail['cnt']},
                "name" => $detail['name'],
                "percent" => round(${$detail['cnt']} / $cnt * 100, 2) . '%',
                "other" => round(100 - ${$detail['cnt']} / $cnt * 100, 2) . '%',
            ];
        }

        return $ret;
    }

    private function getQuestions($v, $alarm_types) {
        $ret = [];
        foreach ($alarm_types as $alarm_type => $detail) {
            if ($v[$alarm_type] == 1) {
                $ret[] = $detail['name'];
            }
        }
        if (empty($ret)) {
            $ret[] = '无';
        }
        return $ret;
    }
}
