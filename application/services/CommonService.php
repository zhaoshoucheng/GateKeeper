<?php
/**
 * 公共接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-22
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class CommonService
 * @property \Waymap_model $waymap_model
 * @property \Common_model $common_model
 */
class CommonService extends BaseService
{
    public $evaluateService;
    public function __construct()
    {
        parent::__construct();

        // load model
        $this->load->model('waymap_model');
        $this->load->model('common_model');
        $this->evaluateService = new EvaluateService();
    }


    /**
     * 获取路口所属行政区域及交叉节点信息
     * @param $params ['city_id']           int    Y 城市ID
     * @param $params ['logic_junction_id'] string Y 路口ID
     * @param $params ['map_version']       string Y 地图版本
     * @return mixed
     */
    public function getJunctionAdAndCross($params)
    {
        $mapVersion = $params['map_version'] ?? '';
        $result = $this->waymap_model->gitJunctionDetail($params['logic_junction_id']
            , $params['city_id']
            , $mapVersion
        );
        if (empty($result['junctions'])) {
            return (object)[];
        }

        $res = [];
        foreach ($result['junctions'] as $k => $v) {
            $junctionName = $v['name'];
            $districtName = $v['district_name'];
            $road1 = $v['road1'];
            $road2 = $v['road2'];
            $res = [
                'logic_junction_id' => $v['logic_junction_id'],
                'junction_name' => $v['name'],
                'lng' => $v['lng'],
                'lat' => $v['lat'],
            ];
        }
        $cityName = $result['city_name'];

        $string = '该路口位于';
        $string .= $cityName . $districtName . '，';
        $string .= '是' . $road1 . '和' . $road2 . '交叉的重要节点路口。';
        $res['desc'] = $string;

        return $res;
    }

    /**
     * 获取路口相位信息
     * @param $params ['city_id']           int    Y 城市ID
     * @param $params ['logic_junction_id'] string Y 路口ID
     * @return array
     */
    public function getJunctionMovements($params)
    {
        $flowsInfo = $this->waymap_model->getFlowsInfo($params['logic_junction_id']);

        if (!empty($flowsInfo)) {
            $result = $this->sortByNema($flowsInfo[$params['logic_junction_id']]);
        }

        $res = $this->common_model->getTimingMovementNames($params['logic_junction_id']);
        foreach ($result as $key => $item) {
            if (!empty($res[$result[$key]["flow_id"]])) {
                $result[$key]["flow_name"] = $res[$result[$key]["flow_id"]];
            }
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
        $flowInfos = array_filter($flowInfos, function ($direction) {
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
            $word2 = mb_substr($direction, 0, 2);
            $order = $scores[$word2] ?? 0;
            $ret[] = [
                'flow_id' => $flowId,
                'flow_name' => $direction,
                'order' => $order,
            ];
        }

        usort($ret, function ($a, $b) {
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
        $table = 'open_city';
        $select = 'city_id, city_name';

        $res = $this->common_model->search($table, $select);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k => $v) {
            $result[$k] = [
                'areaId' => (string)$v['city_id'],
                'areaName' => $v['city_name'],
                'level' => 1,
                'apid' => '-1',
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

        foreach ($res['districts'] as $k => $v) {
            $result[$k] = [
                'areaId' => (string)$k,
                'areaName' => $v,
                'level' => 2,
                'apid' => (string)$cityId,
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
            'city_id' => $cityId,
            'delete_at' => '1970-01-01 00:00:00',
        ];

        $res = $this->common_model->search($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k => $v) {
            $result[$k] = [
                'areaId' => (string)$v['id'],
                'areaName' => $v['area_name'],
                'level' => 2,
                'apid' => (string)$cityId,
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
            'city_id' => $cityId,
            'is_delete' => 0,
        ];

        $res = $this->common_model->search($table, $select, $where);
        if (!$res) {
            return [];
        }

        $result = [];
        foreach ($res as $k => $v) {
            $result[$k] = [
                'areaId' => (string)$v['id'],
                'areaName' => $v['road_name'],
                'level' => 2,
                'apid' => (string)$cityId,
            ];
        }

        return $result;
    }

    /**
     * 获取v5开城列表
     * @param $cityId long 城市ID
     * @return mixed
     */
    public function getV5DMPCityID()
    {
        return $this->common_model->getV5DMPCityID();
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

        foreach ($res as $k => $v) {
            $result[$k] = [
                'areaId' => (string)$v['logic_junction_id'],
                'areaName' => $v['name'],
                'level' => 3,
                'apid' => (string)$areaId,
            ];
        }

        return $result;
    }

    /**
     * 获取全城命中围栏的点
     * @param $coods
     */
    public function getJunctionInPolygon($cityId,$coods)
    {
        $junctionList = $this->evaluateService->getCityJunctionList(["city_id"=>$cityId]);
        $coodsArr = [];
        foreach (explode(";", $coods) as $cood) {
            list($lng, $lat) = explode(",", $cood);
            $coodsArr[] = ["lng" => $lng, "lat" => $lat];
        }
        $newJunctionList = [];
        foreach ($junctionList["dataList"] as $item){
            if($this->isPointInPolygon($coodsArr, $item)){
                $newJunctionList[] = $item;
            }
        }
        /*foreach ($newJunctionList as $item){
            print_r("INSERT INTO `area_junction_relation` (`id`, `area_id`, `junction_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '55', '".$item["logic_junction_id"]."', '0', '2019-04-15 14:23:08', '2019-04-15 14:23:08', '1970-01-01 00:00:00');");
        }
        exit;*/
        return $newJunctionList;
    }

    /**
     * 验证区域范围
     * @param array $coordArray 区域
     * @param array $point 验证点
     * @return bool
     */
    function isPointInPolygon($coordArray, $point)
    {
        if (!is_array($coordArray) || !is_array($point)) return false;
        $maxY = $maxX = 0;
        $minY = $minX = 9999;
        foreach ($coordArray as $item) {
            if ($item['lng'] > $maxX) $maxX = $item['lng'];
            if ($item['lng'] < $minX) $minX = $item['lng'];
            if ($item['lat'] > $maxY) $maxY = $item['lat'];
            if ($item['lat'] < $minY) $minY = $item['lat'];
            $vertx[] = $item['lng'];
            $verty[] = $item['lat'];
        }
        if ($point['lng'] < $minX || $point['lng'] > $maxX || $point['lat'] < $minY || $point['lat'] > $maxY) {
            return false;
        }

        $c = false;
        $nvert = count($coordArray);
        $testx = $point['lng'];
        $testy = $point['lat'];
        for ($i = 0, $j = $nvert - 1; $i < $nvert; $j = $i++) {
            if ((($verty[$i] > $testy) != ($verty[$j] > $testy))
                && ($testx < ($vertx[$j] - $vertx[$i]) * ($testy - $verty[$i]) / ($verty[$j] - $verty[$i]) + $vertx[$i])
            )
                $c = !$c;
        }
        return $c;
    }

    /*
     * 将用户权限和权限过滤合并
     */
    public function mergeUserPermAreaJunction($cityId, $userPerm)
    {
        if ($userPerm == null) {
            $userPerm = [];
        }

        $wayModel = new \Waymap_model();
        $junctionIds = $wayModel->getRestrictJunction($cityId);
        if (empty($junctionIds)) {
            return $userPerm;
        }

        if (!isset($userPerm['city_id'])) {
            $userPerm['city_id'] = [];
        }
        if (!isset($userPerm['junction_id'])) {
            $userPerm['junction_id'] = [];
        }

        if (in_array($cityId, $userPerm['city_id'])) {
            // 有选择全城，所以junction_id就限制为当前限制区域
            $userPerm['junction_id'] = $junctionIds;
            return $userPerm;
        }

        // 没有选择全城，取交集
        $userPerm['junction_id'] = array_intersect($userPerm['junction_id'], $junctionIds);
        return $userPerm;
    }
}
