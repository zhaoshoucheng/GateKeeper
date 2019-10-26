<?php
/**
 * 路口分析报告模块业务逻辑
 */

namespace Services;

class JunctionReportService extends BaseService{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('report_conf');
        $this->load->model('diagnosisNoTiming_model');
        $this->load->model('waymap_model');

    }

    public function queryJuncQuotaDetail($cityID,$logicJunctionID,$startTime,$endTime){
        //查询路网flow信息
        $flowsMovement = $this->waymap_model->getFlowMovement($cityID, $logicJunctionID, "all", 1);
        $flowsMovement = array_map(function ($v) {
            $v = $this->adjustPhase($v);
            return $v;
        }, $flowsMovement);
        $flowPhases = array_column($flowsMovement,"phase_name","logic_flow_id");
        //查询指标详情
        $quotaInfo = $this->diagnosisNoTiming_model->getFlowAllQuotaList($cityID,$logicJunctionID,$startTime,$endTime);

        $ret = [
            "flow_info"=>$flowPhases,
            "quota_info"=>$quotaInfo
        ];
        return $ret;

    }
    /**
     * 修改路口的flow，校准 phase_id 和 phase_name
     *
     * @param $flows
     *
     * @return array
     */
    private function adjustPhase($flow)
    {
        $phaseId = phase_map($flow['in_degree'], $flow['out_degree']);
        $phaseName = phase_name($phaseId);
        $flow['phase_id'] = $phaseId;
        $flow['phase_name'] = $phaseName;
        $flow['sort_key'] = phase_sort_key($flow['in_degree'], $flow['out_degree']);
        return $flow;
    }

    //es数据转换为表格
    public function trans2Chart($flowQuota,$flowInfo){
        $stopTimeChartData =[
            "quotaname"=>"车均停车次数",
            "quotakey"=>"stop_time_cycle",
            "analysis"=>"",
            "flowlist"=>[],
        ];
        $speedChartData =[
            "quotaname"=>"车均行驶速度",
            "quotakey"=>"speed",
            "analysis"=>"",
            "flowlist"=>[],
        ];
        $stopDelayChartData =[
            "quotaname"=>"车均停车延误",
            "quotakey"=>"stop_delay",
            "analysis"=>"",
            "flowlist"=>[],
        ];
        foreach ($flowQuota as $fk => $fv){
            $stopTimeCycleChart = [];
            $speedCycleChart = [];
            $stopDelayCycleChart = [];
            foreach ($fv as $h => $series){
                $stopTimeCycleChart[] = [
                    "x"=>$h,
                    "y"=>round($series['stop_time_cycle']/$series['traj_count'],2)
                ];
                $speedCycleChart[] = [
                    "x"=>$h,
                    "y"=>round($series['speed']/$series['traj_count'],2)
                ];
                $stopDelayCycleChart[] = [
                    "x"=>$h,
                    "y"=>round($series['stop_delay']/$series['traj_count'],2)
                ];
            }
            $stopTimeChartData['flowlist'][]=[
                "logic_flow_id"=>$fk,
                "chart"=>[
                    "title"=>$flowInfo[$fk],
                    "scale_title"=>"次/车",
                    "series"=>[["name"=>"","data"=>$stopTimeCycleChart]],
                ],
            ];
            $speedChartData['flowlist'][]=[
                "logic_flow_id"=>$fk,
                "chart"=>[
                    "title"=>$flowInfo[$fk],
                    "scale_title"=>"km/h",
                    "series"=>[["name"=>"","data"=>$speedCycleChart]]
                ],
            ];
            $stopDelayChartData['flowlist'][]=[
                "logic_flow_id"=>$fk,
                "chart"=>[
                    "title"=>$flowInfo[$fk],
                    "scale_title"=>"S",
                    "series"=>[["name"=>"","data"=>$stopDelayCycleChart]]
                ],
            ];
        }

        $chartDataList=[];
        $chartDataList[]= $stopTimeChartData;
        $chartDataList[]= $speedChartData;
        $chartDataList[]= $stopDelayChartData;

        return $chartDataList;
    }

    //对表格数据进行分析
    public function chartAnalysis($chartData){
        foreach ($chartData as $k=> $v){
            switch ($v['quotakey']){
                case "stop_time_cycle":
                    $maxQuotaFlow = $this->queryMaxQuotaFlow($chartData[$k]['flowlist']);
                    $chartData[$k]["analysis"]="该路口在评估日期内".$maxQuotaFlow['max_flow']."方向的停车次数最大,其中在".$maxQuotaFlow['max_range'][0]."-".end($maxQuotaFlow['max_range'])."时段内的停车次数最大,需重点关注。";
                    break;
                case "speed":
                    $minQuotaFlow = $this->queryMinQuotaFlow($chartData[$k]['flowlist']);
                    $chartData[$k]["analysis"]="该路口在评估日期内".$minQuotaFlow['min_flow']."方向的行驶速度最小,其中在".$minQuotaFlow['min_range'][0]."-".end($minQuotaFlow['min_range'])."时段内的行驶速度最小,需重点关注。";
                    break;
                case "stop_delay":
                    $maxQuotaFlow = $this->queryMaxQuotaFlow($chartData[$k]['flowlist']);
                    $chartData[$k]["analysis"]="该路口在评估日期内".$maxQuotaFlow['max_flow']."方向的停车延误最大,其中在".$maxQuotaFlow['max_range'][0]."-".end($maxQuotaFlow['max_range'])."时段内的停车延误最大,需重点关注。";
                    break;
            }
        }
        return $chartData;
    }

    private function queryMaxQuotaFlow($flowlist){
        $bucket=[];
        foreach ($flowlist as $f => $v){
            foreach ($v['chart']['series'][0]['data'] as $s){
                if(!isset($bucket[$s['x']])){
                    $bucket[$s['x']] = [
                        'flowname'=>$v['chart']['title'],
                        'value'=>0
                    ];
                }
                if ($s['y'] > $bucket[$s['x']]['value']){
                    $bucket[$s['x']] = [
                        'flowname'=>$v['chart']['title'],
                        'value'=>$s['y']
                    ];
                }
            }
        }
        $count=[];
        $maxRange=[];
        $tempRange=[];
        foreach ($bucket as $k=> $v){
            if(!isset( $count[$v['flowname']])){
                $count[$v['flowname']]=0;
            }
            $count[$v['flowname']]+=1;
        }
        $maxFlow = array_keys($count, max($count))[0];
        foreach ($bucket as $k=>$v){
            if($v['flowname'] == $maxFlow){
                $tempRange[] = $k;
            }else{
                if(count($tempRange)>count($maxRange)) {
                    $maxRange = $tempRange;
                    $tempRange=[];
                }
            }
        }
        if(count($tempRange)>0 && count($maxRange)==0){
            $maxRange = $tempRange;
        }

        return ["max_flow"=>$maxFlow,"max_range"=>$maxRange];
    }
    //查询指标最高的flow
    private function queryMinQuotaFlow($flowlist){
        $bucket=[];
        foreach ($flowlist as $f => $v){
            foreach ($v['chart']['series'][0]['data'] as $s){
                if(!isset($bucket[$s['x']])){
                    $bucket[$s['x']] = [
                        'flowname'=>$v['chart']['title'],
                        'value'=>999999
                    ];
                }
                if ($s['y'] < $bucket[$s['x']]['value']){
                    $bucket[$s['x']] = [
                        'flowname'=>$v['chart']['title'],
                        'value'=>$s['y']
                    ];
                }
            }
        }
        $count=[];
        $minRange=[];
        $tempRange=[];
        foreach ($bucket as $k=> $v){
            if(!isset( $count[$v['flowname']])){
                $count[$v['flowname']]=0;
            }
            $count[$v['flowname']]+=1;
        }
        $mixFlow = array_keys($count, min($count))[0];
        foreach ($bucket as $k=>$v){
            if($v['flowname'] == $mixFlow){
                $tempRange[] = $k;
            }else{
                if(count($tempRange)>count($minRange)) {
                    $minRange = $tempRange;
                    $tempRange=[];
                }
            }
        }
        if(count($tempRange)>0 && count($minRange)==0){
            $minRange = $tempRange;
        }

        return ["min_flow"=>$mixFlow,"min_range"=>$minRange];

    }

}