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
        $juncInfos  = $this->expressway_model->getQuickRoadSegments($cityID);

        //TODO 路口过滤

//        $movements = $juncInfos['movements'];

        //查询干线信息

//        $roadInfos = $this->expressway_model->getQuickRoadMovement($cityID);

        $ret = [
            'junc_list'=>[],
            'road_list'=>[]
        ];

        foreach ($juncInfos['junctions'] as $j){
            $ret['junc_list'][] = [
                "junction_id"=>$j['junction_id'],
                "lng"=>$j['lng'],
                "lat"=>$j['lat'],
                "type"=>$j['type']
            ];
        }

        foreach ($juncInfos['segments'] as $s){
            $ret['road_list'][] = [
                "id"=>$s['segment_id'],
                "start_junc"=>$s['start_junc_id'],
                "end_junc"=>$s['end_junc_id'],
                "length"=>$s['length'],
                "name"=>$s['name'],
                "geom"=>$s['geom']
            ];
        }



        return $ret;
    }

    public function queryStopDelayList($cityID){
        $req = [
            'city_id' => (int)$cityID,
            'upstream_id'=>"",
            'downstream_id'=>"",
            'hms'=>date("Y-m-d H:i:s",strtotime("-5 minute")),

        ];

        $url = $this->config->item('data_service_interface');
//        $url = "http://127.0.0.1:8093";
        $res = httpPOST($url . '/report/GetExpresswayQuota', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);

            $ret = [];

            foreach ($res['data']['data_list'] as $v){
                $ret[] = [
                    "time"=>$res['data']['hms'],
                    "junction_id"=>$v['downstream_ramp'],
                    "junction_name"=>"",
                    "stop_delay"=>round($v['delay'],2),
                    "quota_unit"=>"秒"
                ];
            }

            $junctionIDs = array_column($ret,'junction_id');
            $junctionInfos = $juncInfos  = $this->expressway_model->getQuickRoadSegments($cityID,$junctionIDs);
            $juncNameMap = [];
            foreach ($junctionInfos['junctions'] as $j){
                $juncNameMap[$j['junction_id']] = $j['name'];
            }
            foreach ($ret as $rk => $rv){
                if(isset($juncNameMap[$rv['junction_id']])){
                    $ret[$rk]['junction_name']=$juncNameMap[$rv['junction_id']];
                }

            }

            return $ret;
        } else {
            return [];
        }
    }

    public function queryQuotaDetail($params){
        $req = [
            'city_id' => (int)$params['city_id'],
            'upstream_id'=>"",
            'downstream_id'=>$params['end_junc_id'],
            'hms'=>$params['time'],
        ];

        $url = $this->config->item('data_service_interface');
//        $url = "http://127.0.0.1:8093";
        $res = httpPOST($url . '/report/GetExpresswayQuotaDetail', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            return [
                "speed"=>round($res['data']['data_list'][0]['avg_speed']*3.6,2),
                "stop_delay"=>round($res['data']['data_list'][0]['delay'],2),
                "across_time"=>round($res['data']['data_list'][0]['travel_time'],2),
                "type"=>1
            ];
        } else {
            return [];
        }
    }


}