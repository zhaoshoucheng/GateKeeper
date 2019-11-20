<?php

namespace Services;

/**
 * Class ExpresswayService
 * @package Services
 */
class ExpresswayService extends BaseService
{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('expressway_model');
    }

    public function queryOverview($cityID){
        //查询匝道信息
//        $juncInfos  = $this->expressway_model->getQuickRoadMovement($cityID);

        //TODO 路口过滤

//        $movements = $juncInfos['movements'];

        //查询干线信息

        $roadInfos = $this->expressway_model->getQuickRoadSegments($cityID);

        $ret = [
            'junc_list'=>[],
            'road_list'=>[]
        ];

        foreach ($roadInfos['junctions'] as $j){
            $ret['junc_list'][] = [
                "junction_id"=>$j['junction_id'],
                "lng"=>$j['lng'],
                "lat"=>$j['lat'],
                "type"=>$j['type']
            ];
        }

        foreach ($roadInfos['segments'] as $s){
            $ret['road_list'][] = [
                "id"=>$s['segment_id'],
                "start_junc"=>$s['start_junc_id'],
                "end_junc"=>$s['end_junc_id'],
                "length"=>$s['length'],
                "name"=>$s['name'],
                "geom"=>$s['gemo']
            ];
        }



        return $ret;
    }

    public function queryStopDelayList(){

    }


}