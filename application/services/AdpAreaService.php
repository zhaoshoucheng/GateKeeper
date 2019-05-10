<?php
/**
 * 信控平台 - 区域相关接口
 *
 * User: lichaoxi_i@didichuxing.com
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class AreaService
 * @package Services
 * @property \Area_model $area_model
 */
class AdpAreaService extends BaseService
{
    /**
     * AreaService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
        $this->load->model('adpArea_model');
        $this->load->model('redis_model');

        $this->load->config('nconf');
    }

    /**
     * 获取区域列表
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function getList($params)
    {

        $cityId = $params['city_id'];

        $areaList = $this->adpArea_model->getAreasByCityId($cityId);
        $areaList = array_map(function($item){
            return [
                'city_id' => $item['city_id'],
                'id' => $item['id'],
                'area_name' => $item['name'],
            ];
        }, $areaList);

        return [
            'list' => $areaList,
        ];
    }

    /**
     * 添加区域
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function addArea($params)
    {
        $cityId      = $params['city_id'];
        $areaName    = $params['area_name'];
        $junctionIds = $params['junction_ids'];

        if (!$this->adpArea_model->areaNameIsUnique($areaName, $cityId)) {
            throw new \Exception('区域名称 ' . $areaName . ' 已经存在', ERR_DATABASE);
        }

        // 创建区域
        $data = [
            'name' => $areaName,
            'city_id' => $cityId,
            'status' => 0,
            'adaptive' => 1,
            'is_upload' => 0,
        ];
        $area_id = $this->adpArea_model->insertArea($data);

        // 创建区域路口关联
        $bRet = $this->updateAreaJunction($area_id, $junctionIds);
        if ($bRet === false) {
            throw new \Exception('插入区域路口失败', ERR_PARAMETERS);
        }

        return $area_id;
    }

    /**
     * 更新区域
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function updateArea($params)
    {
        $areaId      = $params['area_id'];
        $areaName    = $params['area_name'];
        $junctionIds = $params['junction_ids'];

        // 获取区域信息
        $areaInfo = $this->adpArea_model->getAreaByAreaId($areaId);

        if (!$areaInfo || empty($areaInfo)) {
            throw new \Exception('目标区域不存在', ERR_PARAMETERS);
        }

        $areaId = $areaInfo['id'];
        $cityId      = $areaInfo['city_id'];

        $data = [
            'area_name' => $areaName,
        ];

        if (!$this->adpArea_model->areaNameIsUnique($areaName, $cityId, $areaId)) {
            throw new \Exception('区域名称 ' . $areaName . ' 已经存在', ERR_DATABASE);
        }

        // 更新区域信息
        $bRet = $this->updateAreaJunction($areaId, $junctionIds);
        if ($bRet === false) {
            throw new \Exception('插入区域路口失败', ERR_PARAMETERS);
        }

        return $areaId;
    }

    /**
     * 删除区域
     *
     * @param $params
     *
     * @return mixed
     */
    public function deleteArea($params)
    {
        $areaId = $params['area_id'];

        return $this->adpArea_model->deleteArea($areaId);
    }

    /**
     * 获取指定区域详情
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getAreaDetail($params)
    {
        $areaId = $params['area_id'];

        $areaInfo = $this->adpArea_model->getAreaByAreaId($areaId);

        if (!$areaInfo) {
            throw new \Exception(' 目标区域不存在', ERR_PARAMETERS);
        }

        $areaJunctionList = $this->adpArea_model->getAreaJunctions($areaId);
        $junction_ids = array_column($areaJunctionList, 'junction_id');

        $areaJunctionList = $this->adpArea_model->getJunctions($junction_ids);
        $logic_junction_ids = array_map(function($item){
            return $item['logic_id'];
        },$areaJunctionList);

        $junctionInfoList = $this->waymap_model->getJunctionInfo(implode(',', $logic_junction_ids));


        $lngs = array_column($junctionInfoList, 'lng');
        $lats = array_column($junctionInfoList, 'lat');

        // 取小数后6位
        $round = function ($item) {
            return round($item, 6);
        };

        $centerLat = round(array_sum($lngs) / count($lngs), 6);
        $centerLng = round(array_sum($lats) / count($lats), 6);

        return [
            'center_lat' => $centerLat,
            'center_lng' => $centerLng,
            'area_id' => $areaId,
            'area_name' => $areaInfo['name'] ?? '',
            'junction_list' => $junctionInfoList,
        ];
    }

    private function updateAreaJunction($area_id, $newJunctionIds) {
        $areaInfo = $this->adpArea_model->getAreaByAreaId($area_id);

        if (!$areaInfo) {
            throw new \Exception(' 目标区域不存在', ERR_PARAMETERS);
        }

        $areaJunctionList = $this->adpArea_model->getAreaJunctions($area_id);
        $junction_ids = array_column($areaJunctionList, 'junction_id');

        if (empty($junction_ids)) {
            $oldJunctionIds = [];
        } else {
            $areaJunctionList = $this->adpArea_model->getJunctions($junction_ids);
            $oldJunctionIds = array_map(function($item){
                return $item['logic_id'];
            },$areaJunctionList);
        }

        $shouldDeleted = array_diff($oldJunctionIds, $newJunctionIds);
        $shouldCreated = array_diff($newJunctionIds, $oldJunctionIds);

        // only delete map
        if (!empty($shouldDeleted)) {
            $t = $this->adpArea_model->getJunctionsByLogic($shouldDeleted);
            $t = array_map(function($item){
                return $item['id'];
            },$t);
            $this->adpArea_model->deleteAreaJunctions($area_id, $t);
        }
        // insert map junction flow
        if (!empty($shouldCreated)) {
            return $this->insertAreaJunctions($area_id, $shouldCreated);
        }
        return true;
    }

    private function insertAreaJunctions($area_id, $shouldCreated) {
        try {
            $relates = [];
            $flows = [];
            foreach ($shouldCreated as $logic_junction_id) {
                $junction_info = $this->adpArea_model->getJunctionByLogicId($logic_junction_id);
                if (! empty($junction_info)) {
                    // 已存在，只更新relate
                    $id = $junction_info[0]['id'];
                    $relates[] = [
                        'junction_id' => $id,
                        'area_id' => $area_id,
                    ];
                } else {
                    // 不存在，插入junction，获得id，更新relate和flow
                    $junction = $this->waymap_model->getJunctionDetail($logic_junction_id);
                    // $version = $this->waymap_model->getJunctionVersion($logic_junction_id);
                    $version =  '2019022518';
                    $main_node_id = $this->waymap_model->getLogicMaps(array($logic_junction_id), $version);
                    $id = $this->adpArea_model->insertJunction([
                        'name' => $junction['name'],
                        'logic_id' => $logic_junction_id,
                        'logic_junction_id' => $logic_junction_id,
                        'type' => intval($junction['is_complex']) + 1,
                        'node_id' => $main_node_id,
                        'map_version' => $version,
                    ]);
                    $relates[] = [
                        'junction_id' => $id,
                        'area_id' => $area_id,
                    ];
                    $junctionFlows = $this->waymap_model->flowsByJunction($logic_junction_id, $version);
                    $junctionFlows = array_map(function($item) use($id, $version, $logic_junction_id){
                        return [
                            'junction_id' => $id,
                            'map_version' => $version,
                            'logic_id' => $logic_junction_id,
                            'inlink_id' => $item['inlink'],
                            'outlink_id' => $item['outlink'],
                            'logic_flow_id' => $item['logic_flow_id'],
                        ];
                    }, $junctionFlows);
                    $flows = array_merge($flows, $junctionFlows);
                }
            }
            $this->adpArea_model->insertRelates($relates);
            if (!empty($flows)) {
                $this->adpArea_model->insertFlows($flows);
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    // /**
    //  * 获取城市全部区域详情
    //  * @param $params['city_id'] int 城市ID
    //  * @return array
    //  * @throws \Exception
    //  */
    // public function getCityAreaDetail($params)
    // {
    //     $cityId = $params['city_id'];

    //     // 获取城市全部区域信息
    //     $areaList       = $this->area_model->getAreasByCityId($cityId, 'id, area_name');
    //     if (empty($areaList)) {
    //         return (object)[];
    //     }
    //     $areaCollection = Collection::make($areaList);

    //     // 获取区域ID
    //     $areaIdList = $areaCollection->column('id')->get();

    //     // 获取 ID 和 名称的映射
    //     $areaIdToNameList = $areaCollection->column('area_name', 'id')->get();

    //     // 获取全部区域路口映射
    //     $areaJunctionList       = $this->area_model->getAreaJunctionsByAreaIds($areaIdList);
    //     $areaJunctionCollection = Collection::make($areaJunctionList);

    //     // 从路网获取路口信息
    //     $junctionIds    = $areaJunctionCollection->implode('junction_id', ',');
    //     $junctionList   = $this->waymap_model->getJunctionInfo($junctionIds);
    //     $junctionIdList = array_column($junctionList, null, 'logic_junction_id');

    //     $areaIdJunctionList = $areaJunctionCollection
    //         ->groupBy('area_id', function ($item) {
    //             return array_column($item, 'junction_id');
    //         })->krsort();

    //     $results = [];

    //     foreach ($areaIdJunctionList as $areaId => $junctionIds) {

    //         $junctionCollection = Collection::make([]);

    //         foreach ($junctionIds as $id) {
    //             $junctionCollection[] = $junctionIdList[$id] ?? '';
    //         }

    //         $results[] = [
    //             'area_id' => $areaId,
    //             'area_name' => $areaIdToNameList[$areaId] ?? '',
    //             'center_lat' => $junctionCollection->avg('lat'),
    //             'center_lng' => $junctionCollection->avg('lng'),
    //             'junction_list' => $junctionCollection,
    //         ];
    //     }

    //     return $results;
    // }
}