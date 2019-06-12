<?php

/********************************************
 * # desc:    干线路口数据模型
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-06-29
 ********************************************/

/**
 * Class Arterialjunction_model
 * @property Waymap_model $waymap_model
 */
class Arterialjunction_model extends CI_Model
{
    private $tb = 'junction_index';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            return [];
        }

        $this->load->config('nconf');
        $this->load->model('waymap_model');
        $this->load->model('timing_model');
        $this->load->model('task_model');
    }

    /**
     * 获取全城路口数据
     * @param data['task_id']      interger 任务ID
     * @param data['city_id']      interger 城市ID
     * @return array
     * 转换为json的格式:
     * {"dataList":[{"logic_junction_id":"2017030116_4861479","lng":"117.16051","lat":"36.66729","name":"经十东路-凤山路"}],"junctionTotal":1}
     */
    public function getAllJunctions($data)
    {
        $version = 0;
        if(!empty($data['task_id'])){
            // 获取任务详情
            $task = $this->task_model->getTaskById($data['task_id']);
            if(empty($task["dates"])){
                throw new \Exception("The task not found.");
            }
        }else{
            $tDates = [];
            for ($i=30;$i>0;$i--){
                $tDates[] = date("Y-m-d", strtotime("-".$i." day"));
            }
            $task['dates'] = implode(",",$tDates);
        }

        // 获取配时信息
        $timing = $this->timing_model->queryTimingStatus(
            [
                'city_id' => $data['city_id'],
                'source' => 2,
            ]
        );
        $hasTiming = [];
        foreach ($timing as $one) {
            if ($one['status'] == 1) {
                $hasTiming[] = $one['logic_junction_id'];
            }
        }

        // 获取地图版本
        $version = $this->waymap_model->getLastMapVersion();
        if (empty($version)) {
            throw new \Exception("The map_version not found.");
        }

        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($data['city_id'], $version);
        if (count($allCityJunctions) < 1 || !$allCityJunctions || !is_array($allCityJunctions)) {
            return [];
        }
        $resultData = [];
        $resultData['dataList'] = array_reduce($allCityJunctions, function ($v, $w) use($hasTiming) {
            if (empty($v)) {
                $v = [];
            }
            $v[] = array(
                "logic_junction_id" => $w["logic_junction_id"],
                "lng" => $w["lng"],
                "lat" => $w["lat"],
                "name" => $w["name"],
                "timing_status" => in_array($w['logic_junction_id'], $hasTiming) ? 1 : 0,
            );
            return $v;
        });
        $resultData['junctionTotal'] = count($resultData['dataList']);

        $junctionCenterFunc = function ($dataList) {
            $count_lng = 0;
            $count_lat = 0;
            $qcount = count($dataList);
            foreach ($dataList as $v) {
                $count_lng += $v['lng'];
                $count_lat += $v['lat'];
            }
            return ["lng" => round($count_lng / $qcount, 6), "lat" => round($count_lat / $qcount, 6),];
        };
        $resultData['center'] = $junctionCenterFunc($resultData['dataList']);
        $resultData['map_version'] = $version;
        return $resultData;
    }

    /**
     * 获取可连接为干线的路口集合
     * @param $data['q']['task_id']             interger 任务id
     * @param $data['q']['city_id']             interger 城市id
     * @param $data['q']['map_version']         interger 地图版本
     * @param $data['q']['selected_junctionid'] string   被选路口
     * @param $data['q']['selected_path']       array    已连接路径的junction和links
     * @return array
     */
    public function getAdjJunctions($data)
    {
        if(empty($data['q']['map_version'])){
            // 获取任务详情
            $task = $this->task_model->getTaskById($data['q']['task_id']);
            if(empty($task["dates"])){
                throw new \Exception("The task not found.");
            }
            // 获取地图版本
            $data['q']['map_version'] = $this->waymap_model->getLastMapVersion();
            if (empty($data['q']['map_version'])) {
                throw new \Exception("The map_version not found.");
            }
        }

        $adjJunctions = $this->waymap_model->getConnectionAdjJunctions($data['q']['map_version'], $data['q']['city_id'], $data['q']['selected_junctionid'], $data['q']['selected_path']);

        //获取一个方向的links
        $getDirectionLinks = function ($direction, $qData) {
            if (empty($qData["selected_path"])) {
                return [];
            }
            $selectedPath = $qData["selected_path"];
            if ($direction == -1) {
                krsort($selectedPath);
            }
            return array_reduce($selectedPath, function ($carry, $item) use ($direction) {
                if($direction==1) {
                    $dFlows = !empty($item["links"]) ? explode(",", $item["links"]) : [];
                }else{
                    $dFlows = !empty($item["reverse_links"]) ? explode(",", $item["reverse_links"]) : [];
                }
                $carry = array_merge($carry, $dFlows);
                return $carry;
            }, []);
        };

        //合并多个link的geo信息
        $mergeLinkGeoInfosByLinks = function ($linkArr, $cityId, $mapVersion) {
            try{
                $orginLinksGeoInfos = $this->waymap_model->getLinksGeoInfos($linkArr, $mapVersion);
            }catch (\Exception $e){

            }
            if(empty($orginLinksGeoInfos)){
                return (Object)[];
            }
            return $orginLinksGeoInfos;
        };

        //格式化路口
        $formatJunctions = function ($allCityJunctions, $qData) use ($getDirectionLinks, $mergeLinkGeoInfosByLinks) {
            //线路geo
            $allCityJunctions["path_links"] = implode(',', $getDirectionLinks(1, $qData));   //正向
            $allCityJunctions["reverse_path_links"] = implode(',', $getDirectionLinks(-1, $qData));   //反向

            $allCityJunctions["path_geo"] = $mergeLinkGeoInfosByLinks($getDirectionLinks(1, $qData), $qData['city_id'], $qData['map_version']);   //正向
            $allCityJunctions["reverse_path_geo"] = $mergeLinkGeoInfosByLinks($getDirectionLinks(-1, $qData), $qData['city_id'], $qData['map_version']);   //反向
            $allCityJunctions["map_version"] = $qData['map_version'];   //正向

            $selectedJunc =  ArrGet($qData, 'selected_path', []);
            $lastSelectedJunc = end($selectedJunc);
            $lastSelectedJuncLinks = ArrGet($lastSelectedJunc, 'links', '');
            $lastSelectedJuncRLinks = ArrGet($lastSelectedJunc, 'reverse_links', '');
            $allCityJunctions["last_geo"] = $mergeLinkGeoInfosByLinks(
                explode(',', $lastSelectedJuncLinks),
                $qData['city_id'],
                $qData['map_version']);
            $allCityJunctions["reverse_last_geo"] = $mergeLinkGeoInfosByLinks(
                explode(',', $lastSelectedJuncRLinks),
                $qData['city_id'],
                $qData['map_version']);

            //路口geo
            if (empty($allCityJunctions['adj_junc_paths'])) {
                $allCityJunctions['adj_junc_paths'] = [];
            }
            $connectedJunctions = ArrGet($allCityJunctions,"adj_junc_paths",[]);
            foreach ($connectedJunctions as $jKey=>$jItem){
                if(empty($jItem["links"])){
                    $jItem["links"] = "";
                }
                if(empty($jItem["reverse_links"])){
                    $jItem["reverse_links"] = "";
                }
                $allCityJunctions["adj_junc_paths"][$jKey]["links_geo"] = $mergeLinkGeoInfosByLinks(explode(",",$jItem["links"]), $qData['city_id'], $qData['map_version']);
                $allCityJunctions["adj_junc_paths"][$jKey]["reverse_links_geo"] = $mergeLinkGeoInfosByLinks(explode(",", $jItem["reverse_links"]), $qData['city_id'], $qData['map_version']);
            }
            return $allCityJunctions;
        };

        return $formatJunctions($adjJunctions, $data['q']);
    }
}
