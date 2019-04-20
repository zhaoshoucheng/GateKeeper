<?php
/********************************************
# desc:    干线配时数据模型
# author:  zhuyewei@didichuxing.com
# date:    2018-06-29
********************************************/

/**
 * Class Arterialtiming_model
 * @property \Road_model $road_model
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
        //去掉首尾路口请求原接口数据
        $result = $this->getJunctionInfos($cityID,$version,array_slice($selectJunctions,1,count($selectJunctions)-2));
        //追加flow信息
        $forwardInJunctionID = $backwardOutJunctionID = $selectJunctions[0];
        $secondJunctionID = $selectJunctions[1];
        $thirdJunctionID = $selectJunctions[2] ?? "";
        $lastButTwoJunctionID = $selectJunctions[count($selectJunctions)-3] ?? "";
        $lastPreJunctionID = $selectJunctions[count($selectJunctions)-2];
        $forwardOutJunctionID = $backwardInJunctionID = $selectJunctions[count($selectJunctions)-1];

        //从db中获取进出路口
        $forwardInfo = $this->road_model->getRoadsByRoadID(md5(implode(",",array_slice($selectJunctions,1,count($selectJunctions)-2))));
        if(!empty($forwardInfo["forward_in_junctionid"])){
            $forwardInJunctionID = $forwardInfo["forward_in_junctionid"];
            $backwardOutJunctionID = $forwardInfo["backward_out_junctionid"];
            $forwardOutJunctionID = $forwardInfo["forward_out_junctionid"];
            $backwardInJunctionID = $forwardInfo["backward_in_junctionid"];
        }
        $backwardInfo = $this->road_model->getRoadsByRoadID(md5(implode(",",array_reverse(array_slice($selectJunctions,1,count($selectJunctions)-2)))));
        if(!empty($backwardInfo["forward_in_junctionid"])){
            $forwardInJunctionID = $backwardInfo["backward_in_junctionid"];
            $backwardOutJunctionID = $backwardInfo["forward_out_junctionid"];
            $forwardOutJunctionID = $backwardInfo["backward_out_junctionid"];
            $backwardInJunctionID = $backwardInfo["forward_in_junctionid"];
        }

        //正向追加第一个路口
        $firstForwardFlows = [];
        $juncMovements = $this->waymap_model->getFlowMovement($cityID, $secondJunctionID, 'all', 1);
        foreach ($juncMovements as $item) {
            if ($item['junction_id'] == $secondJunctionID
                && $item['downstream_junction_id'] == $thirdJunctionID
                && $item['upstream_junction_id'] == $forwardInJunctionID) {
                $firstForwardFlows = $item;
                break;
            }
        }

        //正向追加最后一个路口
        $lastForwardFlows = [];
        $juncMovements = $this->waymap_model->getFlowMovement($cityID, $lastPreJunctionID, 'all', 1);
        foreach ($juncMovements as $item) {
            if ($item['junction_id'] == $lastPreJunctionID
                && $item['downstream_junction_id'] == $forwardOutJunctionID
                && $item['upstream_junction_id'] == $lastButTwoJunctionID) {
                $lastForwardFlows = $item;
                break;
            }
        }

        //反向追加第一个路口
        $firstBackwardFlows = [];
        $juncMovements = $this->waymap_model->getFlowMovement($cityID, $lastPreJunctionID, 'all', 1);
        foreach ($juncMovements as $item) {
            if ($item['junction_id'] == $lastPreJunctionID
                && $item['upstream_junction_id'] == $backwardInJunctionID
                && $item['downstream_junction_id'] == $lastButTwoJunctionID) {
                $firstBackwardFlows = $item;
                break;
            }
        }

        //反向追加最后一个路口
        $lastBackwardFlows = [];
        $juncMovements = $this->waymap_model->getFlowMovement($cityID, $secondJunctionID, 'all', 1);
        foreach ($juncMovements as $item) {
            if ($item['junction_id'] == $secondJunctionID
                && $item['upstream_junction_id'] == $thirdJunctionID
                && $item['downstream_junction_id'] == $backwardOutJunctionID) {
                $lastBackwardFlows = $item;
                break;
            }
        }

        //求ave_length平均值
        $firstFlowLength = (ArrGet($firstForwardFlows,"in_link_length",0)
            +ArrGet($lastBackwardFlows,"out_link_length",0))/2;
        $lastFlowLength = (ArrGet($lastForwardFlows,"in_link_length",0)
                +ArrGet($firstBackwardFlows,"out_link_length",0))/2;

        //追加正向首尾路口到$result['forward_path_flows']
        $newForwardFlow = [];
        $newForwardFlow[] = [
            "start_junc_id"=>$forwardInJunctionID,
            "end_junc_id"=>$secondJunctionID,
            "path_links"=>$firstForwardFlows["in_link_ids"]??"",
            "length"=>$firstFlowLength,
            "logic_flow"=>[
                "logic_junction_id"=>$secondJunctionID,
                "logic_flow_id"=>$firstForwardFlows["logic_flow_id"]??"",
                "inlinks"=>$firstForwardFlows["in_link_ids"]??"",
                "outlinks"=>$firstForwardFlows["out_link_ids"]??"",
                "inner_link_ids"=>$firstForwardFlows["inner_link_ids"]??"",
            ],
            "ave_length"=>$firstFlowLength,
            "length_warning"=>0,
        ];
        foreach ($result['forward_path_flows'] as $item){
            $newForwardFlow[] = $item;
        }
        $newForwardFlow[] = [
            "start_junc_id"=>$lastPreJunctionID,
            "end_junc_id"=>$forwardOutJunctionID,
            "path_links"=>$lastForwardFlows["in_link_ids"]??"",
            "length"=>$lastFlowLength,
            "logic_flow"=>[
                "logic_junction_id"=>$forwardOutJunctionID,
                "logic_flow_id"=>$lastForwardFlows["logic_flow_id"]??"",
                "inlinks"=>$lastForwardFlows["in_link_ids"]??"",
                "outlinks"=>$lastForwardFlows["out_link_ids"]??"",
                "inner_link_ids"=>$lastForwardFlows["inner_link_ids"]??"",
            ],
            "ave_length"=>$lastFlowLength,
            "length_warning"=>0,

        ];

        //追加反向首尾路口 到$result['backward_path_flows']
        $newBackwardFlow = [];
        $newBackwardFlow[] = [
            "start_junc_id"=>$forwardOutJunctionID,
            "end_junc_id"=>$lastPreJunctionID,
            "path_links"=>$firstBackwardFlows["out_link_ids"]??"",
            "length"=>$lastFlowLength,
            "logic_flow"=>[
                "logic_junction_id"=>$lastPreJunctionID,
                "logic_flow_id"=>$firstBackwardFlows["logic_flow_id"]??"",
                "inlinks"=>$firstBackwardFlows["in_link_ids"]??"",
                "outlinks"=>$firstBackwardFlows["out_link_ids"]??"",
                "inner_link_ids"=>$firstBackwardFlows["inner_link_ids"]??"",
            ],
            "ave_length"=>$lastFlowLength,
            "length_warning"=>0,
        ];
        foreach ($result['backward_path_flows'] as $item){
            $newBackwardFlow[] = $item;
        }
        $newBackwardFlow[] = [
            "start_junc_id"=>$secondJunctionID,
            "end_junc_id"=>$forwardInJunctionID,
            "path_links"=>$lastBackwardFlows["out_link_ids"]??"",
            "length"=>$firstFlowLength,
            "logic_flow"=>[
                "logic_junction_id"=>$forwardInJunctionID,
                "logic_flow_id"=>$lastBackwardFlows["logic_flow_id"]??"",
                "inlinks"=>$lastBackwardFlows["in_link_ids"]??"",
                "outlinks"=>$lastBackwardFlows["out_link_ids"]??"",
                "inner_link_ids"=>$lastBackwardFlows["inner_link_ids"]??"",
            ],
            "ave_length"=>$firstFlowLength,
            "length_warning"=>0,
        ];

        //追加首尾路口到$result['junctions_info']
        $newJunctionInfo = [];
        $newJunctionInfo[$selectJunctions[0]] = ["name"=>"未知路口","lng"=>"","lat"=>"","node_ids"=>[],];
        foreach ($result['junctions_info'] as $junctionID=>$item){
            $newJunctionInfo[$junctionID] = $item;
        }
        $newJunctionInfo[$selectJunctions[count($selectJunctions)-1]] = ["name"=>"未知路口","lng"=>"","lat"=>"","node_ids"=>[],];

        $result['junctions_info'] = $newJunctionInfo;
        $result['forward_path_flows'] = $newForwardFlow;
        $result['backward_path_flows'] = $newBackwardFlow;
        return $result;
    }

    public function getJunctionInfos($cityId,$version,$selectJunctions)
    {
        $ret = $this->waymap_model->getConnectPath($cityId,$version,$selectJunctions);
        if(empty($ret)){
            return [];
        }
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

