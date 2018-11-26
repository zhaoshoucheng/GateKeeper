<?php
/**
 * 公共接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-22
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

class CommonService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        // load model
        $this->load->model('waymap_model');
        $this->load->model('common_model');
    }

    /**
     * 获取路口所属行政区域及交叉节点信息
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string Y 路口ID
     * @param $params['map_version']       string Y 地图版本
     * @return mixed
     */
    public function getJunctionAdAndCross($params)
    {
        $result = $this->waymap_model->gitJunctionDetail($params['logic_junction_id']
                                                            , $params['city_id']
                                                            , $params['map_version']
                                                        );
        if (empty($result['junctions'])) {
            return (object)[];
        }

        $res = [];
        foreach ($result['junctions'] as $k=>$v) {
            $junctionName = $v['name'];
            $districtName = $v['district_name'];
            $road1 = $v['road1'] ?? '未知路口';
            $road2 = $v['road2'] ?? '未知路口';
            $res = [
                'logic_junction_id' => $v['logic_junction_id'],
                'junction_name'     => $v['name'],
                'lng'               => $v['lng'],
                'lat'               => $v['lat'],
            ];
        }
        $cityName = $result['city_name'];

        $string = $junctionName . '路口位于';
        $string .= $cityName . $districtName . '，';
        $string .= '是' . $road1 . '和' . $road2 . '交叉的重要节点路口。';
        $res['desc'] = $string;

        return $res;
    }

    /**
     * 获取路口相位信息
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string Y 路口ID
     * @return array
     */
    public function getJunctionMovements($params)
    {
        $flowsInfo = $this->waymap_model->getFlowsInfo($params['logic_junction_id']);

        if (!empty($flowsInfo)) {
            $result = $this->sortByNema($flowsInfo[$params['logic_junction_id']]);
        }

        return $result;
    }

    /**
     * @param $flowInfos array['logic_flow_id' => 'direction']
     * @return array [['flow_id', 'flow_name', 'order']]
     */
    public function sortByNema($flowInfos)
    {
        // 过滤有"掉头"字样的
        $flowInfos = array_filter($flowInfos, function($direction) {
            return strpos($direction, '掉头') === False;
        });

        $scores = [
            '南左' => 8 * 5,
            '北直' => 7 * 5,
            '西左' => 6 * 5,
            '东直' => 5 * 5,
            '北左' => 4 * 5,
            '南直' => 3 * 5,
            '东左' => 2 * 5,
            '西直' => 1 * 5,
        ];

        $ret = [];
        foreach ($flowInfos as $flowId => $direction) {
            $word2 = mb_substr($direction, 0 , 2);
            $order = $scores[$word2] ?? 0;
            $ret[] = [
                'flow_id' => $flowId,
                'flow_name' => $direction,
                'order' => $order,
            ];
        }

        usort($ret, function($a, $b) {
            if ($a['order'] == $b['order']) {
                return 0;
            }
            return ($a['order'] > $b['order']) ? -1 : 1;
        });
        return $ret;
    }

    /**
     * 获取开城城市列表
     * @return mixed
     */
    public function getOpenCityList()
    {
        $res = $this->common_model->getOpenCityList();
        if (!$res || empty($res)) {
            return [];
        }

        foreach ($res as $k => $v) {
            $result[$k] = [
                'areaId'   => $v['city_id'],
                'areaName' => $v['city_name'],
                'level'    => 1,
                'apid'     => -1,
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
                'areaId'   => $k,
                'areaName' => $v,
                'level'    => 1,
                'apid'     => -1,
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
        $where = 'city_id = ' . $cityId;

        $res = $this->common_model->search($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k=>$v) {
            $result[$k] = [
                'areaId'   => $v['id'],
                'areaName' => $v['area_name'],
                'level'    => 1,
                'apid'     => -1,
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
        $where = 'city_id = ' . $cityId . ' and is_delete = 0';

        $res = $this->common_model->search($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k=>$v) {
            $result[$k] = [
                'areaId'   => $v['id'],
                'areaName' => $v['road_name'],
                'level'    => 1,
                'apid'     => -1,
            ];
        }

        return $result;
    }

    /**
     * 根据城市ID获取所有路口
     * @param $cityId long 城市ID
     * @return mixed
     */
    public function getAllJunctionByCityId($cityId)
    {
        // 获取路网全城路口
        $res = $this->waymap_model->getAllCityJunctions($cityId);
        if (!$res) {
            return [];
        }

        foreach ($res as $k=>$v) {
            $result[$k] = [
                'areaId'   => $v['logic_junction_id'],
                'areaName' => $v['name'],
                'level'    => 1,
                'apid'     => -1,
            ];
        }

        return $result;
    }
}
