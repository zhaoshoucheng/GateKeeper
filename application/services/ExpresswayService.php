<?php

namespace Services;

/**
 * Class ExpresswayService
 * @property \Waymap_model $waymap_model
 * @property \Expressway_model $expressway_model
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
        $this->load->model('redis_model');

        $this->dataService = new DataService();
    }

    public function queryOverview($cityID){

        $juncNames=[];
        $skipJunc=[];
        if($cityID == 11){
            $juncNames = ["宁洛","水吉","长江","郑和","下关","内环","玄武","扬子江","定淮","江东","应天","沪蓉","扬子江","凤台","卡子门","栖霞"];
            $skipJunc = ["3888566","3891510","72978335","72978326","72978327","89103480","3852948","4023026","72978339","3870579","3888594","3888593","3888595","3888596","3876608","3897615","3879469","3882371","3885459","3879472","3876614","3885455","3972937","3973026","4023024","4023023","4023025","4005651"];
        }
        //TODO 路口过滤
        //查询匝道信息
        $juncInfos  = $this->expressway_model->getQuickRoadSegments($cityID,$juncNames);



        $ret = [
            'junc_list'=>[],
            'road_list'=>[]
        ];

        foreach ($juncInfos['junctions'] as $j){
            if(in_array($j['junction_id'],$skipJunc)){
                continue;
            }
            $ret['junc_list'][] = [
                "junction_id"=>$j['junction_id'],
                "lng"=>$j['lng'],
                "lat"=>$j['lat'],
                "name"=>$j['name'],
                "type"=>$j['type']
            ];
        }

        foreach ($juncInfos['segments'] as $s){
            if(in_array($s['start_junc_id'],$skipJunc)){
                continue;
            }
            if(in_array($s['end_junc_id'],$skipJunc)){
                continue;
            }
            $ret['road_list'][] = [
                "id"=>$s['segment_id'],
                "start_junc"=>$s['start_junc_id'],
                "end_junc"=>$s['end_junc_id'],
                "length"=>$s['length'],
                "name"=>$s['name'],
                "link_ids"=>$s['link_ids'],
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
            if($ret['speed'] <= 20){
                $ret['type'] = 1;
            }elseif($ret['speed'] <= 40){
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

    	$alarmlist = $this->redis_model->getData('ramp_alarm_history');
    	if (empty($alarmlist)) {
    		return [
	    		"trafficList" => [],
	    	];
    	} else {
    		$alarmlist = json_decode($alarmlist, true);
    	}

    	// 过滤junction
    	$overview = $this->queryOverview($city_id);
    	$ids = [];
    	foreach ($overview['junc_list'] as $value) {
    		if (!empty($value['name'])) {
    			$ids[$value['junction_id']] = $value;
    		}
    	}
    	$list = [];
    	foreach ($alarmlist as $value) {
    		if (isset($ids[$value['ramp_id']])) {
    			$list[] = [
					"start_time"=> $value['start'],
		            "duration_time"=> $value['last'] / 60,
		            "junction_id"=> $value['ramp_id'],
		            "junction_name"=> $ids[$value['ramp_id']]['name'],
		            "lng"=> $ids[$value['ramp_id']]['lng'],
		            "lat"=> $ids[$value['ramp_id']]['lat'],
		            "alarm_comment"=> "过饱和",
		            "alarm_type"=> 1,
    			];
    		}
    	}
    	usort($list, function($a, $b) {
            return ($a['duration_time'] < $b['duration_time']) ? 1 : -1;
        });

    	return [
    		"trafficList" => $list,
    	];
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
    		$speed = round($value['avg_speed'] * 3.6, 2);
    		$list[$key]['avg_speed'] = $speed;
    		if ( $speed < 30 && $speed > 0 ) {
    			$list[$key]['type'] = 1;
    		} elseif ($speed < 50 && $speed > 0 ) {
    			$list[$key]['type'] = 2;
    		} else {
    			$list[$key]['type'] = 3;
    			unset($list[$key]);
    		}
    	}

    	// 过滤link
    	$overview = $this->queryOverview($city_id);
    	$ids = [];
    	foreach ($overview['road_list'] as $value) {
    		$ids = array_merge($ids, explode(',', $value['link_ids']));
    	}
    	// var_dump(count($list));
    	foreach ($list as $key => $value) {
    		if (!in_array($value['link_id'], $ids)) {
    			unset($list[$key]);
    		}
    	}
    	// var_dump(count($list));

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