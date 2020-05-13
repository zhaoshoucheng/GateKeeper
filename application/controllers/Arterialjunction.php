<?php
/***************************************************************
# 干线路口类
# user:niuyufu@didichuxing.com
# date:2018-06-29
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialjunction extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('arterialjunction_model');
        $this->load->model('timing_model');
    }

    /**
     * 获取优化全城路口集合接口
     */
    public function getAllScatsJunctions()
    {
        // 获取参数
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $cityId = intval($params['city_id']);

        // 获取权限
        $commonService = new \Services\CommonService();
        $userPerm = $commonService->mergeUserPermAreaJunction($cityId, $this->userPerm);

        // 获取配时
        $timingModel = new Timing_model();
        
        // 获取普通配时
        $timingModel = new Timing_model();
        $timing = $timingModel->queryTimingStatus(
            [
                'city_id' => $cityId,
                'source' => 0,
            ]
        );
        $hasTiming = [];
        foreach ($timing as $item) {
            if ($item['status'] == 1) {
                $hasTiming[] = $item['logic_junction_id'];
            }
        }
        // 获取scats配时
        $scatsJunctions = $timingModel->getScatsJunctions($cityId);
        $scatsJunctions = array_column($scatsJunctions,"junction_id");
        $interTiming = array_intersect($hasTiming,$scatsJunctions);

        // 求scats与普通配时交集
        $mapHasTiming = array_flip($interTiming);
        
        $waymapModel = new Waymap_model();
        $version = $waymapModel::$lastMapVersion;

        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $waymapModel->getAllCityJunctions($cityId, $version);
        $restrictJuncs = $this->waymap_model->getRestrictJunctionCached($cityId);
        $mapRestrictJuncs = array_flip($restrictJuncs);

        $allCityJunctions = array_filter($allCityJunctions, function($item) use($mapRestrictJuncs){
            if(empty($mapRestrictJuncs)){
                return true;
            }
            if(isset($mapRestrictJuncs[$item['logic_junction_id']])){
                return true;
            }
            return false;
        });

        // 根据权限做一次过滤
        if(!empty($userPerm)){
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            $mapJunctionIds = array_flip($junctionIds);
            if (!empty($junctionIds)) {
                $allCityJunctions = array_filter($allCityJunctions, function($item) use($mapJunctionIds) {
                    if(isset($mapJunctionIds[$item['logic_junction_id']])){
                        return true;
                    }
                    return false;
                });
            }
        }
        $resultData = [];
        $dataList = [];
        foreach ($allCityJunctions as $key => $value) {
            $flag = 0;
            if(isset($mapHasTiming[$value['logic_junction_id']])){
                $flag = 1;
            }
            $dataList[] = array(
                "logic_junction_id" => $value["logic_junction_id"],
                "lng" => $value["lng"],
                "lat" => $value["lat"],
                "name" => $value["name"],
                "timing_status" => $flag,
            );
        }
        $resultData['dataList'] = $dataList;
        $resultData['junctionTotal'] = count($resultData['dataList']);

        // TODO: 城市中心点可以从后台数据库中获取
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
        return $this->response($resultData);
    }

    /**
     * 获取优化全城路口集合接口
     */
    public function getAllJunctions()
    {
        // 获取参数
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }
        $cityId = intval($params['city_id']);

        // 获取权限
        $commonService = new \Services\CommonService();
        $userPerm = $commonService->mergeUserPermAreaJunction($cityId, $this->userPerm);

        // 获取配时
        $timingModel = new Timing_model();
        $timing = $timingModel->queryTimingStatus(
            [
                'city_id' => $cityId,
                'source' => 0,
            ]
        );
        $hasTiming = [];
        foreach ($timing as $item) {
            if ($item['status'] == 1) {
                $hasTiming[] = $item['logic_junction_id'];
            }
        }
        // print_r($hasTiming);exit;

        $waymapModel = new Waymap_model();
        // 获取地图版本
        $version = $waymapModel::$lastMapVersion;

        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $waymapModel->getAllCityJunctions($cityId, $version);
        $restrictJuncs = $this->waymap_model->getRestrictJunctionCached($cityId);
        $mapRestrictJuncs = array_flip($restrictJuncs);

        $allCityJunctions = array_filter($allCityJunctions, function($item) use($mapRestrictJuncs){
            if(empty($mapRestrictJuncs)){
                return true;
            }
            if(isset($mapRestrictJuncs[$item['logic_junction_id']])){
                return true;
            }
            return false;
        });
        
        // 根据权限做一次过滤
        if(!empty($userPerm)){
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            $mapJunctionIds = array_flip($junctionIds);
            if (!empty($junctionIds)) {
                $allCityJunctions = array_filter($allCityJunctions, function($item) use($mapJunctionIds) {
                    if(isset($mapJunctionIds[$item['logic_junction_id']])){
                        return true;
                    }
                    return false;
                });
            }
        }
        $resultData = [];
        $mapHasTiming = array_flip($hasTiming);

        $dataList = [];
        foreach ($allCityJunctions as $key => $value) {
            $flag = 0;
            if(isset($mapHasTiming[$value['logic_junction_id']])){
                $flag = 1;
            }
            $dataList[] = array(
                "logic_junction_id" => $value["logic_junction_id"],
                "lng" => $value["lng"],
                "lat" => $value["lat"],
                "name" => $value["name"],
                "timing_status" => $flag,
            );
        }
        $resultData['dataList'] = $dataList;
        $resultData['junctionTotal'] = count($resultData['dataList']);

        // TODO: 城市中心点可以从后台数据库中获取
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

        return $this->response($resultData);
    }

    /**
     * 获取可连接为干线的路口集合
     */
    public function getAdjJunctions()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'q' => 'nullunable',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $qJson = json_decode($params['q'],true);

        if(empty($qJson["city_id"]) || !($qJson["city_id"]>0)){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The city_id cannot be empty and must be interger.';
            return;
        }
        if(!isset($qJson["map_version"]) || !is_integer($qJson["map_version"]) || !($qJson["map_version"]>-1)){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The map_version cannot be empty and must be interger.';
            return;
        }
        if(empty($qJson["selected_junctionid"]) || !is_string($qJson["selected_junctionid"])){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The selected_junctionid cannot be empty and must be string.';
            return;
        }
        if(empty($qJson["selected_path"]) && !is_array($qJson["selected_path"])){
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The selected_path must be array.';
            return;
        }

        try{
            $data = $this->arterialjunction_model->getAdjJunctions([
                'q' => $qJson,
            ]);
        }catch (\Exception $e){
            com_log_warning('_itstool_Arterialjunction_getAdjJunctions_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }
}
