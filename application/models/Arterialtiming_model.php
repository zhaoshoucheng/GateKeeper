<?php
/********************************************
# desc:    干线配时数据模型
# author:  zhuyewei@didichuxing.com
# date:    2018-06-29
********************************************/

/**
 * Class Arterialtiming_model
 * @property \Road_model $road_model
 * @property \Timing_model $timing_model
 */
class Arterialtiming_model extends CI_Model
{
    private $tb = '';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('timing_model');
        $this->load->model('waymap_model');
        $this->load->model('road_model');
    }

    public function tmpGetNewJunctionTimingInfos($data,$timePoint,$date,$source)
    {

        $finalRet = [];

        foreach ($data as $dk=>$dv){

            $versionStr = $date."000000";
            $reqdate = substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2);
            $ret  = $this->timing_model->getNewTimngData(array(
                "logic_junction_id"=>$dv['logic_junction_id'],
                'start_time'=>$timePoint.":00",
                'end_time'=>$timePoint.":01",
                'date'=>$reqdate,
                // 'version'=>$versionStr,
                'source'=>$source,
            ));

            if(empty($ret) or empty($ret['schedule'])){
                continue;
            }
            $tod = $ret['schedule'][0]['tod'][0];
            //构造旧格式
            $finalRet[$dv['logic_junction_id']][] = array(
                'date'=>$date,
                'id'=>$ret['signal_id'],
                'junction_id'=>$dv['logic_junction_id'],
                'logic_junction_id'=>$dv['logic_junction_id'],
                'timing_info'=>array(
                    'extra_timing'=>array(
                        'cycle'=>$tod['cycle'],
                        'offset'=>$tod['offset'],
                    ),
                    'tod_start_time'=>$tod['start_time'],
                    'tod_end_time'=>$tod['end_time'],
                    'movement_timing'=>[],
                ),
            );
            if(empty($tod)){
                return $finalRet;
            }
            if ($ret['structure'] == 2){ //stage类型
                $stageLenMap = []; //每个阶段的长度
                $phaseStageMap = [];//记录每个相位所在的阶段
                foreach ($tod['vehicle_phase'] as $tk => $tv){
                    $phaseStageMap[$tv['phase_num']][] = $tv['sequence_num'];
                    $stageLenMap[$tv['sequence_num']] = $tv['end_time']-$tv['start_time'];
                }
                ksort($stageLenMap);

                foreach ($tod['vehicle_phase'] as $tk => $tv){
                    if($tv['flow_info'] == null){
                        continue;
                    }

                   foreach ($tv['flow_info'] as $fk=>$fv){
                       if(empty($dv['flows'])){
                           continue;
                       }
                       if(!in_array($fv['logic_flow_id'],$dv['flows'])){//过滤无用的flow
                            continue;
                       }
                       //找到目标flow
                       if(count($phaseStageMap[$tv['phase_num']])>1 && $phaseStageMap[$tv['phase_num']][1] = $phaseStageMap[$tv['phase_num']][0]+1 ){ //跨阶段
                           //跨阶段的第一段先不用计算
                            if($phaseStageMap[$tv['phase_num']][0] == $tv['sequence_num']){
                                continue;
                            }
                            //跨阶段的第二阶段
                           $startTime=0;
                           foreach ($stageLenMap as $k => $v){
                               if ($k == $tv['sequence_num']-1){
                                   break;
                               }else{
                                   $startTime += $v;
                               }
                           }
                           $tmpMovementTiming = array(
                               'comment'=>$tv['sg_name'],
                               'logic_flow_id'=>$fv['logic_flow_id'],
                               'start_time'=>$startTime,
                               'duration'=>$stageLenMap[$tv['sequence_num']]+$stageLenMap[$tv['sequence_num']-1],
                           );
                           $finalRet[$dv['logic_junction_id']][0]['timing_info']['movement_timing'][] = $tmpMovementTiming;

                       }else{
                           $startTime=0;
                           foreach ($stageLenMap as $k => $v){
                               if ($k == $tv['sequence_num']){
                                   break;
                               }else{
                                   $startTime += $v;
                               }
                           }
                           $tmpMovementTiming = array(
                               'comment'=>$tv['sg_name'],
                               'logic_flow_id'=>$fv['logic_flow_id'],
                               'start_time'=>$startTime,
                               'duration'=>$stageLenMap[$tv['sequence_num']],
                           );
                           $finalRet[$dv['logic_junction_id']][0]['timing_info']['movement_timing'][] = $tmpMovementTiming;
                       }


                   }
                }

            }else{
                foreach ($tod['vehicle_phase'] as $tk => $tv){
                    if (count($tv['flow_info']) == 0){
                        continue;
                    }
                    foreach ($tv['flow_info'] as $fk=>$fv){
                        if(!in_array($fv['logic_flow_id'],$dv['flows'])){//过滤无用的flow
                            continue;
                        }
                        //找到目标flow
                        $tmpMovementTiming = array(
                            'comment'=>$tv['sg_name'],
                            'logic_flow_id'=>$fv['logic_flow_id'],
                            'start_time'=>$tv['start_time'],
                            'duration'=>$tv['end_time']-$tv['start_time'],
                        );
                        $finalRet[$dv['logic_junction_id']][0]['timing_info']['movement_timing'][] = $tmpMovementTiming;

                    }
                }
            }
        }
        return $finalRet;
    }

    public function getJunctionTimingInfos($data,$timePoint,$date)
    {
        //TODO source
        $logicIds = array_column($data,'logic_junction_id');
        $ret = $this->timing_model->getTimingDataBatch(array(
            'junction_ids'      => implode(",",$logicIds),
            'days'              => $date,
            'start_time'        => $timePoint,
            'end_time'          => $timePoint,
            'source'            => 1
        ));
        if(empty($ret)){
            return [];
        }
        foreach ($ret as $j => $value){
            if(empty($value[0]['time_plan'])){
                continue;
            }
            $timePlan = $value[0]['time_plan'][0];//一个时间点肯定只有一份配时
            unset($ret[$j][0]['time_plan']);
            unset($ret[$j][0]['schedule_id']);
            unset($ret[$j][0]['source']);
            unset($ret[$j][0]['start_version']);
            unset($ret[$j][0]['end_version']);
            unset($ret[$j][0]['original_id']);
            unset($ret[$j][0]['create_time']);
            unset($ret[$j][0]['update_time']);
            unset($ret[$j][0]['junction_logic_id']);
            unset($ret[$j][0]['comment']);
            $ret[$j][0]['timing_info'] = self::matchFlow($j,$timePlan,$data);
        }
        return $ret;
    }




    private function matchFlow($logicJunctionId,$oriFlows,$nedFlows)
    {

        $finalRet = [];
        $finalRet['tod_start_time'] = $oriFlows['tod_start_time'];
        $finalRet['tod_end_time'] = $oriFlows['tod_end_time'];
        $finalRet['extra_timing'] = $oriFlows['plan_detail']['extra_timing'];
        $finalRet['extra_timing']['cycle'] = intval($finalRet['extra_timing']['cycle']);
        $finalRet['extra_timing']['offset'] = intval($finalRet['extra_timing']['offset']);

        foreach ($oriFlows['plan_detail']['movement_timing'] as  $mk => $mv){
            foreach ($nedFlows as $nk=>$nv){
                if(!isset($nv['flows'])){
                    continue;
                }
                if($nv['logic_junction_id']!=$logicJunctionId){
                    continue;
                }
                foreach ($mv as $v){
                    foreach ($nv['flows'] as $f){
                        if($v['flow_logic']['logic_flow_id'] == $f){
                            $finalRet['movement_timing'][] = array(
                                'start_time'=>intval($v['start_time']),
                                'duration'=>intval($v['duration']),
                                'logic_flow_id'=>$f,
                                'comment'=>$v['flow_logic']['comment'],
                            );
                        }
                    }
                }

            }
        }
        return $finalRet;
    }

    //获取选中路口的flowIds
    public function getJunctionFlowInfos($cityID,$version,$selectJunctions){
        //从db中获取进出路口
        $forwardJunctions = $selectJunctions;
        $backwardJunctions = array_reverse($selectJunctions);
        $forwardInfo = $this->road_model->getRoadsByJunctionIDs(implode(",",$forwardJunctions));
        if(!empty($forwardInfo["forward_in_junctionid"])){
            $forwardJunctions[0] = $forwardInfo["forward_in_junctionid"];
            $backwardJunctions[count($selectJunctions)-1] = $forwardInfo["backward_out_junctionid"];
            $forwardJunctions[count($selectJunctions)-1] = $forwardInfo["forward_out_junctionid"];
            $backwardJunctions[0] = $forwardInfo["backward_in_junctionid"];
        }
        $backwardInfo = $this->road_model->getRoadsByJunctionIDs(implode(",",$backwardJunctions));
        if(!empty($backwardInfo["forward_in_junctionid"])){
            $forwardJunctions[0] = $forwardInfo["backward_in_junctionid"];
            $backwardJunctions[count($selectJunctions)-1] = $forwardInfo["forward_out_junctionid"];
            $forwardJunctions[count($selectJunctions)-1] = $forwardInfo["backward_out_junctionid"];
            $backwardJunctions[0] = $forwardInfo["forward_in_junctionid"];
        }

        //去掉首尾路口请求原接口数据
//        print_r($forwardJunctions);exit;
        $forwardResult = $this->getJunctionInfos($cityID,$version,$forwardJunctions);
        $junctionsInfo = [];
        $forwardPathFlows = [];
        $backwardPathFlows = [];
        if(!empty($forwardResult['junctions_info'])){
            $junctionsInfo = $forwardResult['junctions_info'];
        }
        if(!empty($forwardResult['forward_path_flows'])){
            $forwardPathFlows = $forwardResult['forward_path_flows'];
        }
        if(!empty($forwardResult['backward_path_flows'])){
            $backwardPathFlows = $forwardResult['backward_path_flows'];
        }
        //正向路口 和 反向路口 不相同 时
        if($forwardJunctions!=array_reverse($backwardJunctions)){
            $backwardResult = $this->getJunctionInfos($cityID,$version,$backwardJunctions);
            if(!empty($backwardResult['forward_path_flows'])){
                $backwardPathFlows = $forwardResult['forward_path_flows'];
            }

            //反向第一个路口对应正向最后一个路口
            $backwardPathFlows[0]['start_junc_id'] = $forwardJunctions[count($forwardJunctions)-1];

            //反向最后一个路口对应正向第一个路口
            $backwardPathFlows[count($forwardJunctions)-1]['end_junc_id'] = $forwardJunctions[0];
            $backwardPathFlows[count($forwardJunctions)-1]['logic_flow']['logic_junction_id'] = $forwardJunctions[0];

            //直接使用正向路径长度
            $backwardPathFlows[0]['length'] = $forwardPathFlows[count($forwardJunctions)-1]['length'];
            $backwardPathFlows[count($forwardJunctions)-1]['length'] = $forwardPathFlows[0]['length'];
        }

        $result['forward_path_flows'] = $forwardPathFlows;
        $result['backward_path_flows'] = $backwardPathFlows;
        if(!isset($junctionsInfo[$forwardJunctions[0]])){
            $junctionsInfo[$forwardJunctions[0]] =[
                "lat"=>"",
                "lng"=>"",
                "logic_junction_id"=>$forwardJunctions[0],
                "name"=>"未知路口",
                "node_ids"=>[],
            ];
        }
        if(!isset($junctionsInfo[$forwardJunctions[count($forwardJunctions)-1]])){
            $junctionsInfo[$forwardJunctions[count($forwardJunctions)-1]] =[
                "lat"=>"",
                "lng"=>"",
                "logic_junction_id"=>$forwardJunctions[count($forwardJunctions)-1],
                "name"=>"未知路口",
                "node_ids"=>[],
            ];
        }
        $result['junctions_info'] = $junctionsInfo;
        return $result;
    }

    public function getJunctionInfos($cityId,$version,$selectJunctions)
    {
        $ret = $this->waymap_model->getConnectPath($cityId,$version,$selectJunctions);
        if(empty($ret)){
            return [];
        }
//        print_r($ret);exit;
        //forwardMap[start][end] = length
        $forwardMap=[];
        $backMap=[];
        foreach ($ret['forward_path_flows'] as $fk => $fv){
            $forwardMap[$fv['start_junc_id']][$fv['end_junc_id']] = $fv['length'];
            $ret['forward_path_flows'][$fk]['ave_length'] = $fv['length'];
            $ret['forward_path_flows'][$fk]['length_warning'] = 0;
        }

        foreach ($ret['backward_path_flows'] as $bk =>$bv){
            if(isset($forwardMap[$bv['end_junc_id']]) && isset($forwardMap[$bv['end_junc_id']][$bv['start_junc_id']])){
                //单行路口,平均值为其中一个
                if(intval($bv['length']) ==0 && intval($forwardMap[$bv['end_junc_id']][$bv['start_junc_id']])!=0){
                    $aveLength = intval($forwardMap[$bv['end_junc_id']][$bv['start_junc_id']]);
                }elseif (intval($bv['length']) !=0 && intval($forwardMap[$bv['end_junc_id']][$bv['start_junc_id']])==0){
                    $aveLength = intval($bv['length']);
                }else{
                    //正向和反向都有
                    $aveLength = (intval($bv['length'])+intval($forwardMap[$bv['end_junc_id']][$bv['start_junc_id']]))/2;
                }


                $ret['backward_path_flows'][$bk]['ave_length'] = ceil($aveLength);
                $backMap[$bv['start_junc_id']][$bv['end_junc_id']] = ceil($aveLength);
                if(abs($bv['length']-$aveLength) > 50 + $aveLength*0.05){
                    $ret['backward_path_flows'][$bk]['length_warning'] = 1;
                }else{
                    $ret['backward_path_flows'][$bk]['length_warning'] = 0;
                }

            }else{
                $ret['backward_path_flows'][$bk]['ave_length'] = $bv['length'];
                $ret['backward_path_flows'][$bk]['length_warning'] = 0;
                $backMap[$bv['start_junc_id']][$bv['end_junc_id']] = $bv['length'];
            }
        }

        foreach ($ret['forward_path_flows'] as $fk =>$fv){
            if(isset($backMap[$fv['end_junc_id']]) && isset($backMap[$fv['end_junc_id']][$fv['start_junc_id']])){
                $aveLength = $backMap[$fv['end_junc_id']][$fv['start_junc_id']];
                $ret['forward_path_flows'][$fk]['ave_length'] = $aveLength;
                if(abs($fv['length']-$aveLength) > 50 + $aveLength*0.05){
                    $ret['forward_path_flows'][$fk]['length_warning'] = 1;
                }

            }
        }

        return $ret;
    }
}

