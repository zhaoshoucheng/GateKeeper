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

    public function queryStopDelayList(){

    }

    public function queryQuotaDetail(){

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