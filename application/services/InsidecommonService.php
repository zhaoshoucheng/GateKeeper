<?php
/**
 * 公共接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-22
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

class InsidecommonService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        // load model
        $this->load->model('waymap_model');
        $this->load->model('common_model');
    }

    /**
     * 获取开城城市列表
     * @return mixed
     */
    public function getOpenCityList()
    {
        $table = 'open_city';
        $select = 'city_id, city_name';

        $res = $this->common_model->search($table, $select);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k=>$v) {
            $result[$k] = [
                'areaId'   => (string)$v['city_id'],
                'areaName' => $v['city_name'],
                'level'    => 1,
                'apid'     => '-1',
            ];
        }

        return $result;
    }

    /**
     * 根据城市ID获取所有行政区域
     * @param $cityId long 城市ID
     * @return mixed
     */
    public function getAllAdminAreaByCityId($cityId)
    {
        $res = $this->waymap_model->getDistrictInfo($cityId);
        if (!$res || empty($res['districts'])) {
            return [];
        }

        foreach ($res['districts'] as $k=>$v) {
            $result[$k] = [
                'areaId'   => (string)$k,
                'areaName' => $v,
                'level'    => 2,
                'apid'     => (string)$cityId,
            ];
        }

        $result = array_values($result);

        return $result;
    }

    /**
     * 根据城市ID获取所有自定义区域
     * @param $cityId long 城市ID
     * @return mixed
     */
    public function getAllCustomAreaByCityId($cityId)
    {
        $table = 'area';
        $select = 'id, area_name';
        $where = [
            'city_id'   => $cityId,
            'delete_at' => '1970-01-01 00:00:00',
        ];

        $res = $this->common_model->search($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k=>$v) {
            $result[$k] = [
                'areaId'   => (string)$v['id'],
                'areaName' => $v['area_name'],
                'level'    => 2,
                'apid'     => (string)$cityId,
            ];
        }

        return $result;
    }

    /**
     * 根据城市ID获取所有自定义干线
     * @param $cityId long 城市ID
     * @return mixed
     */
    public function getAllCustomRoadByCityId($cityId)
    {
        $table = 'road';
        $select = 'id, road_name';
        $where = [
            'city_id'   => $cityId,
            'is_delete' => 0,
        ];

        $res = $this->common_model->search($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k=>$v) {
            $result[$k] = [
                'areaId'   => (string)$v['id'],
                'areaName' => $v['road_name'],
                'level'    => 2,
                'apid'     => (string)$cityId,
            ];
        }

        return $result;
    }

    /**
     * 根据城市ID获取所有路口
     * @param $cityId    long 城市ID
     * @param $areaId    int  行政区域ID
     * @return mixed
     */
    public function getAllJunctionByCityId($cityId, $areaId)
    {
        // 获取路网全城路口
        $res = $this->waymap_model->getCityJunctionsByDistricts($cityId, $areaId);
        if (!$res) {
            return [];
        }

        foreach ($res as $k=>$v) {
            $result[$k] = [
                'areaId'   => (string)$v['logic_junction_id'],
                'areaName' => $v['name'],
                'level'    => 3,
                'apid'     => (string)$areaId,
            ];
        }

        return $result;
    }
}
