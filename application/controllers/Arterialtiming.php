<?php
/***************************************************************
# 干线配时类
# user:zhuyewei@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\TimingService;

/**
 * Class Arterialtiming
 *
 * @property \Arterialtiming_model $arterialtiming_model
 */
class Arterialtiming extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('arterialtiming_model');

        $this->timingService = new TimingService();
    }

    /**
     * 获取优化干线路口配时信息
     * http://wiki.intra.xiaojukeji.com/pages/viewpage.action?pageId=124728304#WEB-API%EF%BC%88web%E5%89%8D%E7%AB%AF%E8%B0%83%E7%94%A8%EF%BC%89-%E5%B9%B2%E7%BA%BF%E7%BB%BF%E6%B3%A2%E4%BC%98%E5%8C%96-%E8%8E%B7%E5%8F%96%E5%B9%B2%E7%BA%BF%E8%B7%AF%E5%8F%A3%E9%85%8D%E6%97%B6%E4%BF%A1%E6%81%AF
     *
     */
    public function queryArterialTimingInfo()
    {
        $this->convertJsonToPost();
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params, [
//                'junction_infos'    => 'nullunable',
                'time_point'        => 'nullunable',
//                'dates'             => 'nullunable',
            ]
        );
        if(!$validate['status']){
            return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
        }

        $data = [
            'junction_infos'       => $params['junction_infos'],
            'dates'       => $params['dates'],
            'time_point'       => $params['time_point'],
            'source' => $params['source_type'],
        ];

        $timing = $this->timingService->queryArterialTimingInfo($data);

        return $this->response($timing);
    }

    /**
     * 获取干线优化路口信息详情
     * http://wiki.intra.xiaojukeji.com/pages/viewpage.action?pageId=124728304#WEB-API%EF%BC%88web%E5%89%8D%E7%AB%AF%E8%B0%83%E7%94%A8%EF%BC%89-%E5%B9%B2%E7%BA%BF%E7%BB%BF%E6%B3%A2%E4%BC%98%E5%8C%96-%E8%8E%B7%E5%8F%96%E5%B9%B2%E7%BA%BF%E8%B7%AF%E5%8F%A3%E8%AF%A6%E7%BB%86%E4%BF%A1%E6%81%AF
     */
    public function queryArterialJunctionInfo()
    {
        $this->convertJsonToPost();
        $params = $this->input->post();
        $validate = Validate::make($params, [
                'city_id'               => 'nullunable',
//                'selected_junctionids'  => 'nullunable',
                'map_version'           => 'nullunable',
            ]
        );
        if(!$validate['status']){
            return $this->response(array(), ERR_PARAMETERS, $validate['errmsg']);
        }

        $cityId = $params['city_id'];
        $version = $params['map_version'];
        $selectJunctions = $params['selected_junctionids'];
        if(count($selectJunctions) < 4){
            return $this->response(array(), ERR_PARAMETERS, "路口数不得小于4");
        }
        $ret = $this->arterialtiming_model->getJunctionFlowInfos($cityId,$version,$selectJunctions);
        if(empty($ret)){
            com_log_warning('_itstool_arterialtiming_queryArterialJunctionInfo_getJunctionInfos_empty', 0, '', compact("params","ret"));
            return $this->response(array(), ERR_REQUEST_WAYMAP_API, "路网服务异常");
        }
        $sortJunctions = [];
        foreach ($selectJunctions as $k){
            foreach ($ret['junctions_info'] as $rk => $rv){
                if($k == $rk){
                    $rv['logic_junction_id'] = (string)$rk;
                    $sortJunctions[]=$rv;
                }
            }
        }
        $ret['junctions_info'] = $sortJunctions;
        return $this->response($ret);
    }

    /**
     * 临时方法,下发柳州干线优化配时
     */
    public function UpRoadOptTiming()
    {
        $this->load->model('timing_model');

        $params = file_get_contents("php://input");
        $params = json_decode($params,true);
        $timepoint = $params['time_point'];
        $junctionList = $params['junction_infos'];

        $date = $params['dates'];
        $reqJuncs=[];

        foreach ($junctionList as $j ){
            $ret = $this->timing_model->getTimingDataBatch(array(
                'junction_ids'      => $j['logic_junction_id'],
                'days'              => end($date),
                'start_time'        => $timepoint,
                'end_time'          => $timepoint,
                'source'            => 1
            ));
            $r = self::formatNewCycle($ret[$j['logic_junction_id']][0],$j['cycle'],$j['offset']);
            $reqJuncs[] = $r;
        }
        $resp = $this->timing_model->uploadTimingData($reqJuncs);
        if(empty($resp)){
            return $this->response(array(), ERR_UNKNOWN, "下发失败");
        }

        return $this->response("success");
    }

    public function formatNewCycle($junctionInfo,$newCycle,$newOffset)
    {
        $oldCycle = $junctionInfo['time_plan'][0]['plan_detail']['extra_timing']['cycle'];
        $movementMap = [];

        $newMoveMap = [];
        $finalMove = [];

        $stageMap = [];
        $addMap = [];

        foreach ($junctionInfo['time_plan'][0]['plan_detail']['movement_timing'] as $k => $v){
            $movementMap[$k]=$v[0]['duration'];

            if(($v[0]['duration']+$v[0]['start_time']) == $oldCycle){
                $finalMove[] = $k;
            }
        }

        $change = $newCycle - $oldCycle;

        foreach ($movementMap as $k => $v){
            $newMoveMap[$k] = round($change*$v/$oldCycle);
        }

        foreach ($junctionInfo['time_plan'][0]['plan_detail']['movement_timing'] as $k => $v){
            $junctionInfo['time_plan'][0]['plan_detail']['movement_timing'][$k][0]['duration'] = $v[0]['duration'] + $newMoveMap[$k];
            $addMap[$k] = $newMoveMap[$k];
            $stageMap[$v[0]['start_time']][] = $k;
        }

        ksort($stageMap);
        $stageAdd = [];
        $stageOne = 0;
        foreach ($stageMap as $k => $v){
            $stageOne = $k;
            break;
        }

        foreach ($stageMap as $k => $v){
            if($k == $stageOne){
                foreach ($v as $mid){
                    $stageAdd[$mid] = 0;
                }
                continue;
            }
            foreach ($stageMap as $k2 => $v2){
                if ($k2 > $k){
                    foreach ($v2 as $mid){
                        if(!isset($stageAdd[$mid])){
                            $stageAdd[$mid]=0;
                        }
                        $stageAdd[$mid] += $newMoveMap[$v[0]];
                    }
                }
            }
        }



        foreach ($junctionInfo['time_plan'][0]['plan_detail']['movement_timing'] as $k => $v){
            $junctionInfo['time_plan'][0]['plan_detail']['movement_timing'][$k][0]['start_time'] = $v[0]['start_time']+$stageAdd[$k];
            if(in_array($k,$finalMove) && $v[0]['start_time']+$stageAdd[$k]+$v[0]['duration'] != $newCycle){
                $junctionInfo['time_plan'][0]['plan_detail']['movement_timing'][$k][0]['duration'] = $newCycle-$v[0]['start_time']-$stageAdd[$k];
            }

        }


        $junctionInfo['time_plan'][0]['plan_detail']['extra_timing']['cycle'] = $newCycle;
        $junctionInfo['time_plan'][0]['plan_detail']['extra_timing']['offset'] = $newOffset;



        return $junctionInfo;
    }
}