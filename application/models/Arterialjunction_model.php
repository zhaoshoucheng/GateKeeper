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
            // 获取地图版本
            $data['q']['map_version'] = $this->waymap_model->getLastMapVersion();
            if (empty($data['q']['map_version'])) {
                throw new \Exception("The map_version not found.");
            }
        }

        //检查选择的路口是否可以联通
        $selectJunctions = [];
        foreach($data['q']['selected_path'] as $pk=>$pv){
            $selectJunctions[] = $pv["start_junc_id"];
        }
        $selectJunctions[] = $data['q']['selected_junctionid'];
        if(count($selectJunctions)>=2){
            try{
                $ret = $this->waymap_model->getConnectPath($data['q']['city_id'],$data['q']['map_version'],$selectJunctions);
                if(!isset($ret['forward_path_flows'])){
                    throw new \Exception("!isset(ret['forward_path_flows'])");
                }
            }catch(\Exception $e){
                com_log_warning("getConnectPath", "", "", array("error"=>$e->getMessage()));
                throw new \Exception("当前干线无法联通，请选择其他线路。");
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
