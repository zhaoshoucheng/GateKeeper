<?php
/********************************************
# desc:    干线配时数据模型
# author:  zhuyewei@didichuxing.com
# date:    2018-06-29
********************************************/

class Arterialtiming_model extends CI_Model
{
    private $tb = '';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('timing_model');
        $this->load->model('waymap_model');
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


        foreach ($ret as $j => $value){
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
        $finalRet['extra_timing'] = $oriFlows['plan_detail']['extra_timing'];

        foreach ($oriFlows['plan_detail']['movement_timing'] as  $mk => $mv){
            foreach ($nedFlows as $nk=>$nv){
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
                //正向和反向都有
                $aveLength = (intval($bv['length'])+intval($forwardMap[$bv['end_junc_id']][$bv['start_junc_id']]))/2;

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

