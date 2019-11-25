<?php

namespace Services;

/**
 * Class ExpresswayService
 * @package Services
 */

use Services\DataService;

class ExpresswayService extends BaseService
{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('expressway_model');
        $this->load->model('waymap_model');

        $this->dataService = new DataService();
    }

    public function queryOverview($cityID){

        $juncNames=[];
        if($cityID == 11){
            $juncNames = ["宁洛","栖霞","水吉","长江","郑和","下关","内环","玄武","扬子江","定淮","江东","应天","沪蓉","扬子江","凤台","机场","卡子门","双龙"];
        }
        //TODO 路口过滤
        //查询匝道信息
        $juncInfos  = $this->expressway_model->getQuickRoadSegments($cityID,$juncNames);



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
                "name"=>$j['name'],
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
            if(empty($res['data']['data_list'])){
                return $ret;
            }
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
            $junctionInfos = $juncInfos  = $this->expressway_model->getQuickRoadSegmentsByJunc($cityID,$junctionIDs);
            $juncNameMap = [];
            if(empty($junctionInfos) || empty($junctionInfos['junctions'])){
                return [];
            }
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

        $res = httpPOST($url . '/report/GetExpresswayQuotaDetail', $req, 0, 'json');
        if (!empty($res)) {
            $res = json_decode($res, true);
            $ret = [
                "speed"=>round($res['data']['data_list'][0]['avg_speed']*3.6,2),
                "stop_delay"=>round($res['data']['data_list'][0]['delay'],2),
                "across_time"=>round($res['data']['data_list'][0]['travel_time'],2),
                "type"=>1
            ];
            if($ret['speed']/20 <= 1){
                $ret['type'] = 1;
            }elseif($ret['speed']/20 >= 2){
                $ret['type'] = 2;
            }else{
                $ret['type'] = 3;
            }
            return $ret;
        } else {
            return [];
        }
    }

    public function alarmlist($params) {
    	$city_id = $params['city_id'];
    	$es_data = $this->dataService->call("/expressway/Condition", [
    		'city_id' => $city_id,
    	], "POST", 'json');
    	var_dump($es_data);

    	$link_ids = array_column($es_data['list'], 'link_id');
    	$version =$this->waymap_model->getLastMapVersion();
    	$link_infos = $this->waymap_model->getLinksGeoInfos($link_ids, $version, true);
    	return $link_infos;
    	// $link_infos_map = [];
    	// foreach ($link_info as $link_info) {

    	// }

    	// $ret = [
    	// 	"trafficList" => [],
    	// ];
    	// foreach ($variable as $key => $value) {
    	// 	# code...
    	// }
    }

    public function condition($params) {
    	$city_id = intval($params['city_id']);
    	$es_data = $this->dataService->call("/expressway/Condition", [
    		'city_id' => $city_id,
    	], "POST", 'json');

    	$list = $es_data[2]['list'];
    	// 阈值 35 50，过滤掉50以上的，降低数据量
    	// 拥堵程度 3 > 2 > 1
    	foreach ($list as $key => $value) {
    		$speed = $value['avg_speed'] * 3.6;
    		if ($speed < 30) {
    			$list[$key]['type'] = 3;
    		} elseif ($speed < 50) {
    			$list[$key]['type'] = 2;
    		} else {
    			$list[$key]['type'] = 1;
    			unset($list[$key]);
    		}
    	}
    	$list = array_values($list);
    	$link_ids = array_column($list, 'link_id');
    	$version =$this->waymap_model->getLastMapVersion();
    	$link_infos = $this->waymap_model->getLinksGeoInfos($link_ids, $version, true);
    	$link_geom_map = [];
    	foreach ($link_infos['features'] as $link_info) {
    		if (in_array($link_info['properties']['id'], $link_ids)) {
    			$link_geom_map[$link_info['properties']['id']] = $link_info['geometry'];
    		}
    	}


    	foreach ($list as $key => $value) {
    		if (isset($link_geom_map[$value['link_id']])) {
    			$list[$key]['geom'] = $link_geom_map[$value['link_id']];
    		} else {
    			$list[$key]['geom'] = [
    				"coordinates" => [],
    				"type" => "LineString",
    			];
    		}
    	}
    	return [
    		'trafficList' => $list,
    		'hms' => $es_data[2]['hms'],
    	];
    }
}