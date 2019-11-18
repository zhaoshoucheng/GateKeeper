<?php
/**
 * 信控平台 - 干线相关接口
 *
 * User: lichaoxi_i@didiglobal.com
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class RoadService
 * @package Services
 * @property \Road_model $road_model
 */
class RoadService extends BaseService
{
    /**
     * RoadService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
        $this->load->model('redis_model');
        $this->load->model('road_model');
        $this->load->model('flowDurationV6_model');
        $this->load->model('traj_model');

        $this->load->config('evaluate_conf');
    }

    /**
     * 干线绿波分析
     *
     * @param $params
     *
     * @return array
     */
    public function greenWaveAnalysis($cityID){
        //TODO 暂时写死
        $roadIDs = ["f67d5bc1becbdcf98622b62649a264c5", "12beb023a415e27b0339f1300ba20d25","efcf36c50ab42f2d17c68edc338348dd"];

        //查询干线信息
        $roadInfos = [];
        foreach ($roadIDs as $r){
            $p=[
                'city_id'=>$cityID,
                'road_id'=>$r,
                'show_type'=>0
            ];
            $data = $this->getRoadDetail($p);
            $rinfo  = $this->road_model->getRoadByRoadId($r,"road_name");
            //TODO 默认值
            $roadInfos[] = [
                'road_id'=>$r,
                'road_name'=>$rinfo['road_name'],
                'road_info'=>$data['road_info'],
                'quota_info'=>[
                    'forward_quota'=>[
                        'time'=>0,
                        'speed'=>0,
                        'stop_time_cycle'=>0,
                        'PI'=>20,
                        'level'=>"A"
                    ],
                    'reverse_quota'=>[
                        'time'=>0,
                        'speed'=>0,
                        'stop_time_cycle'=>0,
                        'PI'=>20,
                        'level'=>"A"
                    ],
                ],
            ];
        }



        $url = $this->config->item('its_traj_interface') . '/road/greenwave';

        $query = [
            'road_ids' => $roadIDs,
            'city_id' => (int)$cityID,
        ];
        $ret =  httpPOST($url, $query, 20000, "json");

        $ret = json_decode($ret, true);
        $data = $ret['data'];
        $flowQuota = [];
        //数据处理合并
        if(!isset($data['RoadMap'])){
            return $roadInfos;
        }

        foreach ($data['RoadMap'] as $rk => $rv){
            if(count($rv['forward'])>0){
                $flowQuota[$rk]=[
                    'forward'=>[
                        'time'=>0,
                        'speed'=>array_sum(array_column($rv['forward'],"speed"))/count($rv['forward']),
                        'stop_time_cycle'=>array_sum(array_column($rv['forward'],"stop_time_cycle"))/count($rv['forward']),
                        'PI'=>array_sum(array_column($rv['forward'],"pi"))/count($rv['forward']),
                        'length'=>array_sum(array_column($rv['forward'],"length")),
                        'level'=>"A"
                    ],
                ];
                $flowQuota[$rk]['forward']['time'] = $flowQuota[$rk]['forward']['length']/ $flowQuota[$rk]['forward']['speed'];
            }
            if(count($rv['backward'])>0){
                $flowQuota[$rk]=[
                    'backward'=>[
                        'time'=>0,
                        'speed'=>array_sum(array_column($rv['backward'],"speed"))/count($rv['backward']),
                        'stop_time_cycle'=>array_sum(array_column($rv['backward'],"stop_time_cycle"))/count($rv['backward']),
                        'PI'=>array_sum(array_column($rv['backward'],"pi"))/count($rv['backward']),
                        'length'=>array_sum(array_column($rv['backward'],"length")),
                        'level'=>"A"
                    ]
                ];
                $flowQuota[$rk]['backward']['time'] = $flowQuota[$rk]['backward']['length']/ $flowQuota[$rk]['backward']['speed'];
            }

//            $flowQuota[$rk]=[
//                'forward'=>[
//                    'time'=>0,
//                    'speed'=>array_sum(array_column($rv['forward'],"speed"))/count($rv['forward']),
//                    'stop_time_cycle'=>array_sum(array_column($rv['forward'],"stop_time_cycle"))/count($rv['forward']),
//                    'PI'=>array_sum(array_column($rv['forward'],"pi"))/count($rv['forward']),
//                    'length'=>array_sum(array_column($rv['forward'],"length")),
//                    'level'=>"A"
//                ],
//                'backward'=>[
//                    'time'=>0,
//                    'speed'=>array_sum(array_column($rv['backward'],"speed"))/count($rv['backward']),
//                    'stop_time_cycle'=>array_sum(array_column($rv['backward'],"stop_time_cycle"))/count($rv['backward']),
//                    'PI'=>array_sum(array_column($rv['backward'],"pi"))/count($rv['backward']),
//                    'length'=>array_sum(array_column($rv['backward'],"length")),
//                    'level'=>"A"
//                ]
//            ];

        }

        foreach ($roadInfos as $rk =>$rv){
            $roadInfos[$rk]['quota_info'] = $flowQuota[$rv['road_id']];
        }


        return $roadInfos;

    }

    /**
     * 获取城市干线列表
     *
     * @param $params
     *
     * @return array
     */
    public function getRoadList($params)
    {
        $cityId = $params['city_id'];

        $select = 'road_id, road_name, road_direction';

        return $this->road_model->getRoadsByCityId($cityId, $select);
    }

    /**
     * 由干线自增id获取干线子路口
     *
     * @param $params
     *
     * @return array
     */
    public function getJunctionsByRoadID($params)
    {
        $roadIDs = $params['road_ids'];

        return $this->road_model->getJunctionsByRoadID($roadIDs);
    }

    /**
     * 新增干线
     *
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function addRoad($params)
    {
        $cityId = $params['city_id'];
        $junctionIds = $params['junction_ids'];
        $roadName = $params['road_name'];

        $data = [
            'city_id' => intval($cityId),
            'road_id' => md5(implode(',', $junctionIds) . $roadName),
            'road_name' => strip_tags(trim($roadName)),
            'logic_junction_ids' => implode(',', $junctionIds),
            'user_id' => 0,
        ];

        if (!$this->road_model->roadNameIsUnique($roadName, $cityId)) {
            throw new \Exception('干线名称 ' . $roadName . ' 已经存在', ERR_DATABASE);
        }

        $res = $this->road_model->insertRoad($data);

        if (!$res) {
            throw new \Exception('新增干线失败', ERR_PARAMETERS);
        }

        return $res;
    }

    /**
     * 更新干线
     *
     * @param $params
     *
     * @return bool
     * @throws \Exception
     */
    public function updateRoad($params)
    {
        $cityId = $params['city_id'];
        $roadId = $params['road_id'];
        $junctionIds = $params['junction_ids'];
        $roadName = $params['road_name'];

        $data = [
            'road_name' => strip_tags(trim($roadName)),
            'logic_junction_ids' => implode(',', $junctionIds),
        ];

        if (!$this->road_model->roadNameIsUnique($roadName, $cityId, $roadId)) {
            throw new \Exception('干线名称 ' . $roadName . ' 已经存在', ERR_DATABASE);
        }

        $res = $this->road_model->updateRoad($roadId, $data);

        $this->redis_model->delList('Road_' . $roadId);
        $this->redis_model->delList('Road_extend_' . $roadId);

        if (!$res) {
            throw new \Exception('更新干线失败', ERR_PARAMETERS);
        }

        return $res;
    }

    /**
     * 删除干线
     *
     * @param $params
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteRoad($params)
    {
        $roadId = $params['road_id'];

        $res = $this->road_model->deleteRoad($roadId);

        $this->redis_model->delList('Road_' . $roadId);
        $this->redis_model->delList('Road_extend_' . $roadId);

        if (!$res) {
            throw new \Exception('删除干线失败', ERR_PARAMETERS);
        }

        return $res;
    }

    /**
     * 获取path的上下游路口
     * 高内聚函数，必须确保质量一般不会轻易动
     *
     * @param $params
     * @return array
     * @throws \Exception
     */
    public function getPathHeadTailJunction($params)
    {
        $cityId = $params["city_id"];
        $junctionIdList = explode(",",$params['junction_ids']);
        $juncMovements = $this->waymap_model->getFlowMovement($cityId, $junctionIdList[0], 'all', 1);
        //上游路口
        $up_road_degree = [];
        foreach ($juncMovements as $item) {
            if ($item['junction_id'] == $junctionIdList[0] &&
                $item['downstream_junction_id'] == $junctionIdList[1]
            ) {
                $absDiff = abs(floatval($item['in_degree']) - floatval($item['out_degree']));
                if($absDiff>180){
                    $absDiff = 360-$absDiff;
                }
                $up_road_degree[$item['upstream_junction_id']] = $absDiff;
            }
        }
        if (!empty($up_road_degree)) {
            asort($up_road_degree); //按照键值进行升序排序
            array_unshift($junctionIdList, (string)key($up_road_degree));   //头部插入
        } else {
            //throw new \Exception('路网数据有误', ERR_ROAD_MAPINFO_FAILED);
        }

        $juncMovements = $this->waymap_model->getFlowMovement($cityId, $junctionIdList[sizeof($junctionIdList) - 1], 'all', 1);
        //下游路口
        $down_road_degree = [];
        foreach ($juncMovements as $item) {
            if ($item['junction_id'] == $junctionIdList[sizeof($junctionIdList) - 1] &&
                $item['upstream_junction_id'] == $junctionIdList[sizeof($junctionIdList) - 2]) {

                $absDiff = abs(floatval($item['in_degree']) - floatval($item['out_degree']));
                if($absDiff>180){
                    $absDiff = 360-$absDiff;
                }
                //print_r($absDiff);
                //print_r($item['downstream_junction_id']);
                $down_road_degree[$item['downstream_junction_id']] = $absDiff;
            }
        }
        //exit;
        if (!empty($down_road_degree)) {
            asort($down_road_degree);
            array_push($junctionIdList, (string)key($down_road_degree));    //尾部插入
        } else {
            //throw new \Exception('路网数据有误', ERR_ROAD_MAPINFO_FAILED);
        }

        return $junctionIdList;
    }

    /**
     * 获取全城全部路口详情
     *
     * @param $params ['city_id'] int 城市ID
     *
     * @return array
     */
    public function getAllRoadDetail($params)
    {
        $cityId = $params['city_id'];
        $show_type = $params['show_type'];
        $force = $params['force'] ?? 0 ;
        $pre_key = $show_type ? 'Road_extend_' : 'Road_';

        $select = 'id, road_id, logic_junction_ids, road_name, road_direction';

        $roadList = $this->road_model->getRoadsByCityId($cityId, $select);
        $results = [];
        foreach ($roadList as $item) {
            $roadId = $item['road_id'];
            $res = $this->redis_model->getData($pre_key . $roadId);
            if ($force) {
                $res = [];
            }
            if($item['id']!=1143){
                // continue;
            }
            // $res = [];
            if (!$res) {
                $data = [
                    'city_id' => $cityId,
                    'road_id' => $roadId,
                    'show_type' => $show_type,
                ];
                try {
                    $res = $this->getRoadDetail($data);
                } catch (\Exception $e) {
                    $res = [];
                }
                // 将数据刷新到 Redis
                $this->redis_model->setEx($pre_key . $roadId, json_encode($res), 86400);
            } else {
                $res = json_decode($res, true);
            }
            $res['road'] = $item;
            $res['road_id'] = $item['id'];
            $results[] = $res;
        }

        return $results;
    }

    /**
     * 获取干线详情
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getRoadDetail($params)
    {
        $cityId = $params['city_id'];
        $roadId = $params['road_id'];
        $showType = $params['show_type'];
        $roadInfo = $this->road_model->getRoadByRoadId($roadId, 'logic_junction_ids');
        if($showType){
            $junctionIdList = $this->getPathHeadTailJunction(["city_id"=>$cityId,
                "junction_ids"=>$roadInfo['logic_junction_ids']]);
        }else{
            $junctionIdList = explode(",",$roadInfo['logic_junction_ids']);
        }
        $maxWaymapVersion = $this->waymap_model->getLastMapVersion();
        $res = $this->waymap_model->getConnectPath($cityId, $maxWaymapVersion, $junctionIdList);
        if (!$res || empty($res['junctions_info']) || empty($res['forward_path_flows']) || empty($res['backward_path_flows'])) {
            throw new \Exception('路网数据有误', ERR_ROAD_MAPINFO_FAILED);
        }

        $junctionList = $res['junctions_info'];
        $forwardPathFlows = $res['forward_path_flows'];
        $backwardPathFlows = $res['backward_path_flows'];

        $getStartEndJunctionIdKeyCallback = function ($item) {
            return $item['start_junc_id'] . '-' . $item['end_junc_id'];
        };
        $getEndStartJunctionIdKeyCallback = function ($item) {
            return $item['end_junc_id'] . '-' . $item['start_junc_id'];
        };
        $getFirstItemCallback = function ($item) {
            return is_array($item) ? current($item) : $item;
        };

        $forwardPathFlowsCollection = Collection::make($forwardPathFlows)->groupBy($getStartEndJunctionIdKeyCallback, $getFirstItemCallback);
        $backwardPathFlowsCollection = Collection::make($backwardPathFlows)->groupBy($getEndStartJunctionIdKeyCallback, $getFirstItemCallback);

        $roadInfo = [];

        foreach ($forwardPathFlowsCollection as $key => $item) {

            $forwardGeo = $this->waymap_model->getLinksGeoInfos($item['path_links'], $maxWaymapVersion);

            $roadInfo[$key] = [
                'start_junc_id' => $item['start_junc_id'],
                'end_junc_id' => $item['end_junc_id'],
                'links' => $item['path_links'],
                'forward_geo' => $forwardGeo,
            ];
        }

        foreach ($backwardPathFlowsCollection as $key => $item) {
            if (isset($roadInfo[$key])) {
                $roadInfo[$key]['reverse_geo'] = [];
            }
        }

        $junctionCollection = Collection::make($junctionList);

        $junctionsInfo = [];

        foreach ($junctionIdList as $id) {
            $junctionsInfo[$id] = [
                'logic_junction_id' => $id,
                'junction_name' => $junctionList[$id]['name'] ?? '未知路口',
                'lng' => $junctionList[$id]['lng'] ?? 0,
                'lat' => $junctionList[$id]['lat'] ?? 0,
                'node_ids' => $junctionList[$id]['node_ids'] ?? [],
            ];
        }

        $center = [
            'lng' => $junctionCollection->avg('lng'),
            'lat' => $junctionCollection->avg('lat'),
        ];

        $junctionsInfo = array_values($junctionsInfo);

        return [
            'road_info' => array_values($roadInfo),
            'junctions_info' => $junctionsInfo,
            'center' => $center,
            'map_version' => $maxWaymapVersion,
        ];
    }


    /*
     * 干线评估表格
     * */
    public function comparisonTable($params){
        $roadId = $params['road_id'];
        $cityId = $params['city_id'];
        $baseStartDate = $params['base_start_date'];
        $baseEndDate = $params['base_end_date'];
        $timePoint = $params['time_point'];

        // 获取干线路口数据
        $select = 'road_name, logic_junction_ids';
        $roadInfo = $this->road_model->getRoadByRoadId($roadId, $select);

        // 获取干线数据失败
        if (!$roadInfo) {
            throw new \Exception('获取干线信息失败');
        }

        $roadName = $roadInfo['road_name'];

        $junctionIdList = explode(',', $roadInfo['logic_junction_ids']);

        // 最新路网版本
        $newMapVersion = $this->waymap_model->getLastMapVersion();

        // 调用路网接口获取干线路口信息
        $roadConnect = $this->waymap_model->getConnectPath($cityId, $newMapVersion, $junctionIdList);

        $reqData  = [
            'city_id'=>(int)$cityId,
            'road_id'=>$roadId,
            'hours'=>[$timePoint],
            'dates'=>dateRange($baseStartDate, $baseEndDate)
        ];
        $retData  = $this->traj_model->getRoadQuotaInfo($reqData);

        //数据合并处理

        $roadQuotaInfo = [];

        $roadQuotaMap=[];
        foreach ($retData['hits']['hits'] as $v){
            $dt = $v['_source']['dt'];
            $logicFlowID = $v['_source']['logic_flow_id'];
            if(!isset($roadQuotaMap[$dt])){
                $roadQuotaMap[$dt] = [];
            }
            if(!isset($roadQuotaMap[$dt][$logicFlowID])){
                $roadQuotaMap[$dt][$logicFlowID] = [];
            }
            //因为请求的时候只有一个时间点
            $roadQuotaMap[$dt][$logicFlowID] = [
                'speed'=> $v['_source']['speed'] * 3.6,
                'stop_delay'=>$v['_source']['stop_delay'],
                'stop_time_cycle'=>$v['_source']['stop_delay'],
                'time'=>0
            ];
        }

        foreach (dateRange($baseStartDate, $baseEndDate) as $dk => $dt){
            $roadQuotaInfo[] = [
                'date'=>$dt,
                'quota_info'=>[]
            ];
            //填充正向指标数据
            foreach ($roadConnect['forward_path_flows'] as $v){
                $roadQuotaInfo[$dk]['quota_info'][] = [
                    "logic_flow_id"=>$v['logic_flow']['logic_flow_id'],
                    "start_junc_id"=>$v['start_junc_id'],
                    "end_junc_id"=>$v['end_junc_id'],
                    "forward_time"=>$v['length']/$roadQuotaMap[$dt][$v['logic_flow']['logic_flow_id']]['speed'],
                    "forward_stop_delay"=>$roadQuotaMap[$dt][$v['logic_flow']['logic_flow_id']]['stop_delay'],
                    "forward_speed"=>$roadQuotaMap[$dt][$v['logic_flow']['logic_flow_id']]['speed'],
                    "forward_stop_time_cycle"=>$roadQuotaMap[$dt][$v['logic_flow']['logic_flow_id']]['stop_time_cycle'],
                    "backward_time"=>0,
                    "backward_stop_delay"=>0,
                    "backward_speed"=>0,
                    "backward_stop_time_cycle"=>0,
                ];
            }
            //填充反向指标数据
            foreach (array_reverse($roadConnect['backward_path_flows']) as $backkey => $backv){
                if(!isset($roadQuotaMap[$dt][$backv['logic_flow']['logic_flow_id']])){
                    continue;
                }
                $roadQuotaInfo[$dk]['quota_info'][$backkey]['backward_stop_delay'] = $roadQuotaMap[$dt][$backv['logic_flow']['logic_flow_id']]['stop_delay'];
                $roadQuotaInfo[$dk]['quota_info'][$backkey]['backward_speed'] = $roadQuotaMap[$dt][$backv['logic_flow']['logic_flow_id']]['speed'];
                $roadQuotaInfo[$dk]['quota_info'][$backkey]['backward_stop_time_cycle'] = $roadQuotaMap[$dt][$backv['logic_flow']['logic_flow_id']]['stop_time_cycle'];
                $roadQuotaInfo[$dk]['quota_info'][$backkey]['backward_time'] = $backv['length']/$roadQuotaMap[$dt][$backv['logic_flow']['logic_flow_id']]['speed'];
            }
        }

        //计算平均值或求和
        $svgFuc = function($r){
            $length = count($r['quota_info']);
            if($length>0){
                $r['sum_avg']=[
                    "forward_time"=>array_sum(array_column($r['quota_info'],'forward_time')),
                    "forward_stop_delay"=>array_sum(array_column($r['quota_info'],'forward_stop_delay'))/$length,
                    "forward_speed"=>array_sum(array_column($r['quota_info'],'forward_speed'))/$length,
                    "forward_stop_time_cycle"=>array_sum(array_column($r['quota_info'],'forward_stop_time_cycle'))/$length,
                    "backward_time"=>array_sum(array_column($r['quota_info'],'backward_time')),
                    "backward_stop_delay"=>array_sum(array_column($r['quota_info'],'backward_stop_delay'))/$length,
                    "backward_speed"=> array_sum(array_column($r['quota_info'],'backward_speed'))/$length,
                    "backward_stop_time_cycle"=> array_sum(array_column($r['quota_info'],'backward_stop_time_cycle'))/$length,
                ];
            }

            return $r;
        };

        $roadQuotaInfo = array_map($svgFuc,$roadQuotaInfo);

        return $roadQuotaInfo;

    }

    /**
     * 干线评估
     *
     * @param $params
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function comparison($params)
    {
        $roadId = $params['road_id'];
        $cityId = $params['city_id'];
        $direction = $params['direction'];
        $quotaKey = $params['quota_key'];
        $baseStartDate = $params['base_start_date'];
        $baseEndDate = $params['base_end_date'];
        $evaluateStartDate = $params['evaluate_start_date'];
        $evaluateEndDate = $params['evaluate_end_date'];

        // 指标算法映射
        $methods = [
            'stop_time_cycle' => 'round(sum(stop_time_cycle), 2) as stop_time_cycle',
            'stop_delay' => 'round(sum(stop_delay), 2) as stop_delay',
            'speed' => 'round(avg(speed) * 3.6, 2) as speed',
            'time' => '',
        ];

        $nameMaps = $this->config->item('road_map');

        // 获取指标单位
        $units = array_column($this->config->item('road'), 'unit', 'key');

        // 如果指标不在映射数组中，返回空数组
        if (!isset($methods[$quotaKey])) {
            throw new \Exception('查找的指标不存在');
        }

        // 获取干线路口数据
        $select = 'road_name, logic_junction_ids';
        $roadInfo = $this->road_model->getRoadByRoadId($roadId, $select);

        // 获取干线数据失败
        if (!$roadInfo) {
            throw new \Exception('获取干线信息失败');
        }

        $roadName = $roadInfo['road_name'];

        $junctionIdList = explode(',', $roadInfo['logic_junction_ids']);

        // 最新路网版本
        $newMapVersion = $this->waymap_model->getLastMapVersion();

        // 调用路网接口获取干线路口信息
        $res = $this->waymap_model->getConnectPath($cityId, $newMapVersion, $junctionIdList);

        // 根据参数决定获取数据指定方向的 flow 集合
        $dataKey = $direction == 1 ? 'forward_path_flows' : 'backward_path_flows';

        // 路网数据没有该方向
        if (!isset($res[$dataKey])) {
            throw new \Exception('该方向没有数据');
        }

        // 生成指定时间范围内的 基准日期集合数组，评估日期集合数组
        $baseDates = dateRange($baseStartDate, $baseEndDate);
        $evaluateDates = dateRange($evaluateStartDate, $evaluateEndDate);

        // 生成 00:00 - 23:30 间的 粒度为 30 分钟的时间集合数组
        $hours = hourRange();

        $flowIdList = array_map(function ($item) {
            return $item['logic_flow']['logic_flow_id'] ?? '';
        }, $res[$dataKey]);

        $flowLength = [];
        // 在查找通行时间时，构建 CASE THEN SQL 语句
        if ($quotaKey == 'time') {

            $timeCaseWhen = 'round(sum(CASE WHEN speed = 0 THEN 0 ';

            // 获取每个 flow 的长度
            foreach ($res[$dataKey] as $item) {
                if (isset($item['logic_flow']['logic_flow_id']) && $item['logic_flow']['logic_flow_id'] != '') {

                    $timeCaseWhen .= 'WHEN logic_flow_id = \'' . $item['logic_flow']['logic_flow_id']
                        . '\' THEN ' . $item['length'] . ' / speed ';
                    $flowLength[] = [
                        'flowid' => $item['logic_flow']['logic_flow_id'],
                        'length' => $item['length'],
                    ];
                }
            }

            $timeCaseWhen .= 'ELSE 0 END), 2) time';

            $methods['time'] = $timeCaseWhen;
        }

        $select = 'date, hour, ' . $methods[$quotaKey];
        $dates = array_merge($baseDates, $evaluateDates);

        // 获取数据源集合
        $result = $this->flowDurationV6_model->getJunctionByCityId($dates, $flowIdList, $cityId, $quotaKey, $flowLength, $select);
        if (!$result) {
            return [];
        }

        // 将数据按照 日期（基准 和 评估）进行分组的键名函数
        $baseOrEvaluateCallback = function ($item) use ($baseDates) {
            return in_array($item['date'], $baseDates) ? 'base' : 'evaluate';
        };

        // 数据分组后，将每组数据进行处理的函数
        $groupByItemFormatCallback = function ($item) use ($hours) {
            $hourToNull  = array_combine($hours, array_fill(0, 48, null));
            $item        = array_column($item, 'quota_value', 'hour');
            $hourToValue = array_merge($hourToNull, $item);

            $result = [];

            foreach ($hourToValue as $hour => $value) {
                $result[] = [$hour, $value];
            }

            return $result;
        };

        // 数据处理
        $result = Collection::make($result)
            ->groupBy([$baseOrEvaluateCallback, 'date'], $groupByItemFormatCallback)
            ->get();

        //数据排序
        $base = $result["base"] ?? [];
        $sorter = [];
        foreach ($base as $date=>$bdata){
            $sorter[$date] = strtotime($date);
        }
        array_multisort($sorter,SORT_NUMERIC,SORT_ASC,$base);
        $result["base"] = $base;


        $result['info'] = [
            'road_name' => $roadName,
            'quota_name' => $nameMaps[$quotaKey] ?? '',
            'quota_unit' => $units[$quotaKey] ?? '',
            'direction' => $direction == 1 ? '正向' : '反向',
            'base_time' => [$baseStartDate, $baseEndDate],
            'evaluate_time' => [$evaluateStartDate, $evaluateEndDate],
        ];

        $jsonResult = json_encode($result);

        $downloadId = md5($jsonResult);

        $result['info']['download_id'] = $downloadId;

        $this->redis_model->setComparisonDownloadData($downloadId, $result);

        return $result;
    }

    /**
     * 获取评估数据下载链接
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function downloadEvaluateData($params)
    {
        $downloadId = $params['download_id'];

        $key = $this->config->item('quota_evaluate_key_prefix') . $downloadId;

        if (!$this->redis_model->getData($key)) {
            throw new \Exception('请先评估再下载', ERR_PARAMETERS);
        }

        return [
            'download_url' => $this->config->item('road_download_url_prefix') . $params['download_id'],
        ];
    }

    /**
     * @param $params
     *
     * @throws \Exception
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function download($params)
    {
        $downloadId = $params['download_id'];

        $data = $this->redis_model->getComparisonDownloadData($downloadId);

        if (!$data) {
            throw new \Exception('请先评估再下载', ERR_PARAMETERS);
        }

        $excelStyle = $this->config->item('excel_style');

        $fileName = "{$data['info']['road_name']}_" . date('Ymd');

        $objPHPExcel = new \PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        $detailParams = [
            ['指标名', $data['info']['quota_name']],
            ['方向', $data['info']['direction'] ?? ''],
            ['基准时间', implode(' ~ ', $data['info']['base_time'])],
            ['评估时间', implode(' ~ ', $data['info']['evaluate_time'])],
            ['指标单位', $data['info']['quota_unit']],
        ];

        $objSheet->mergeCells('A1:F1');
        $objSheet->setCellValue('A1', $fileName);
        $objSheet->fromArray($detailParams, null, 'A4');

        $objSheet->getStyle('A1')->applyFromArray($excelStyle['title']);
        $rows_idx = count($detailParams) + 3;
        $objSheet->getStyle("A4:A{$rows_idx}")->getFont()->setSize(12)->setBold(true);

        $line = 6 + count($detailParams);

        if (!empty($data['base'])) {

            $table = getExcelArray($data['base']);

            $objSheet->fromArray($table, null, 'A' . $line);

            $rows_cnt = count($table);
            $cols_cnt = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;

            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $rows_index)->applyFromArray($excelStyle['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($excelStyle['header']);
            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $line)->applyFromArray($excelStyle['header']);

            $line += ($rows_cnt + 2);
        }

        if (!empty($data['evaluate'])) {

            $table = getExcelArray($data['evaluate']);

            $objSheet->fromArray($table, null, 'A' . $line);

            $rows_cnt = count($table);
            $cols_cnt = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;
            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $rows_index)->applyFromArray($excelStyle['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($excelStyle['header']);
            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $line)->applyFromArray($excelStyle['header']);
        }

        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);

        header('Content-Type: application/x-xls;');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $fileName . '.xls');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: 0'); // Date in the past
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        ob_end_clean();
        $objWriter->save('php://output');
        exit();
    }

    public function cityRoadsOutter($params) {
        $city_id = $params['city_id'];
        $road_infos = $this->road_model->getRoadsByCityId($city_id);
        $logic_junction_ids = [];
        foreach ($road_infos as $key => $road_info) {
            $road_infos[$key]['logic_junction_ids'] = explode(',', $road_info['logic_junction_ids']);
            $logic_junction_ids = array_merge($logic_junction_ids, $road_infos[$key]['logic_junction_ids']);
        }
        $logic_junction_ids = array_unique($logic_junction_ids);
        $junction_infos = $this->waymap_model->getJunctionInfo(implode(',', $logic_junction_ids));
        $junction_infos_map = [];
        foreach ($junction_infos as $junction_info) {
            $junction_infos_map[$junction_info['logic_junction_id']] = $junction_info;
        }

        $data = [];
        foreach ($road_infos as $road_info) {
            $lngs = [];
            $lats = [];
            foreach ($road_info['logic_junction_ids'] as $logic_junction_id) {
                if (isset($junction_infos_map[$logic_junction_id])) {
                    $lngs[] = $junction_infos_map[$logic_junction_id]['lng'];
                    $lats[] = $junction_infos_map[$logic_junction_id]['lat'];
                }
            }
            if (empty($lngs) or empty($lats)) {
                continue;
            }
            $lblng = min($lngs) - 0.00050;
            $lblat = min($lats) - 0.00050;
            $rtlng = max($lngs) + 0.00050;
            $rtlat = max($lats) + 0.00050;

            $data[$road_info['road_id']] = [
                'lblng' => $lblng,
                'lblat' => $lblat,
                'rtlng' => $rtlng,
                'rtlat' => $rtlat,
            ];
        }
        return $data;
    }
}
