<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/21
 * Time: 上午10:57
 */

namespace Services;


use Didi\Cloud\Collection\Collection;

/**
 * Class EvaluateService
 * @package Services
 * @property \Realtime_model       $realtime_model
 * @property \FlowDurationV6_model $flowDurationV6_model
 */
class EvaluateService extends BaseService
{
    protected $helperService;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('redis_model');
        $this->load->model('waymap_model');
        $this->load->model('realtime_model');
        $this->load->model('flowDurationV6_model');

        $this->load->config('evaluate_conf');
        $this->load->config('realtime_conf');

        $this->helperService = new HelperService();
    }

    /**
     * 获取全城路口列表
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getCityJunctionList($params,$userPerm=[])
    {
        $cityId = $params['city_id'];

        if(!empty($userPerm)){
            $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
            $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
            if(in_array($cityId,$cityIds)){
                $junctionIds = [];
            }
            if(!in_array($cityId,$cityIds) && empty($junctionIds)){
                return [];
            }
        }

        $collectionMap = function ($junction) {
            return [
                'logic_junction_id' => $junction['logic_junction_id'],
                'junction_name' => $junction['name'],
                'lng' => $junction['lng'],
                'lat' => $junction['lat'],
            ];
        };

        $result = $this->waymap_model->getAllCityJunctions($cityId);
        $restrictJuncs = $this->waymap_model->getRestrictJunctionCached($cityId);
        $result = array_filter($result, function($item) use($restrictJuncs){
            if (in_array($item['logic_junction_id'], $restrictJuncs)) {
                return true;
            }
            return false;
        });
        
        $junctionCollection = Collection::make($result)->map($collectionMap);
        $dataList = $junctionCollection->get();
        foreach ($dataList as $key=>$item){
            if(!empty($junctionIds) && !in_array($item["logic_junction_id"],$junctionIds)){
                unset($dataList[$key]);
                continue;
            }
        }
        $dataList = array_values($dataList);
        $lngs = array_filter(array_column($dataList, 'lng'));
        $lats = array_filter(array_column($dataList, 'lat'));
        $lng = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
        $lat = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));
        return [
            'dataList' => $dataList,
            'center' => [
                'lng' => $lng,
                'lat' => $lat,
            ],
        ];
    }

    /**
     * 获取指标列表
     *
     * @return array
     */
    public function getQuotaList()
    {
        $realTimeQuota = $this->config->item('real_time_quota');

        $dataList = [];

        foreach ($realTimeQuota as $key => $value) {
            $dataList[] = [
                'name' => $value['name'],
                'key' => $key,
                'unit' => $value['unit'],
            ];
        }

        return compact('dataList');
    }

    /**
     * 获取相位（方向）列表
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getDirectionList($params)
    {
        $junctionId = $params['junction_id'];

        $result = $this->waymap_model->getFlowsInfo($junctionId);

        $result = $result[$junctionId] ?? [];

        $dataList = [];

        foreach ($result as $key => $value) {
            $dataList[] = [
                'logic_flow_id' => $key,
                'flow_name' => $value,
            ];
        }

        return compact('dataList');
    }

    /**
     * 获取路口指标排序列表
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['quota_key']  string Y 指标KEY
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @param $userPerm array N 用户权限点
     * @return array
     * @throws \Exception
     */
    public function getJunctionQuotaSortList($params, $userPerm=[])
    {
        $cityId   = $params['city_id'];

        $cityIds = !empty($userPerm['city_id']) ? $userPerm['city_id'] : [];
        $junctionIds = !empty($userPerm['junction_id']) ? $userPerm['junction_id'] : [];
        if(in_array($cityId,$cityIds)){
            $junctionIds = [];
        }

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');
        $quotaKey = $quotaConf[$params['quota_key']]['escolumn'];

        // 获取最近时间
        $hour = $this->helperService->getLastestHour($cityId);
        $dayTime = date('Y-m-d') . ' ' . $hour;

        //轨迹数
        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }

        // es所需data
        if(empty($junctionIds)){
            $esData = [
                "source"    => "signal_control",
                "cityId"    => $cityId,
                'requestId' => get_traceid(),
                "dayTime"   => $dayTime,
                "trailNum"  => $trajNum,
                "andOperations" => [
                    "cityId"   => "eq",
                    "dayTime"  => "eq",
                    "trailNum" => 'gte',
                ],
                "quotaRequest" => [
                    "groupField" => "junctionId",
                    "asc"        => "false",
                    "limit"      => 100,
                ],
            ];
            if ($params['quota_key'] == 'stop_delay') {
                $esData['quotaRequest']['quotas'] = 'sum_' . $quotaKey . '*trailNum, sum_trailNum';
                $esData['quotaRequest']['orderField'] = "weight_avg";
                $esData['quotaRequest']['quotaType'] = "weight_avg";
                $esQuotaKey = 'weight_avg'; // es接口返回的字段名
            } else {
                $esData['quotaRequest']['quotas'] = 'avg_' . $quotaKey;
                $esData['quotaRequest']['orderField'] = 'avg_' . $quotaKey;
                $esQuotaKey = 'avg_' . $quotaKey; // es接口返回的字段名
            }

            if (!empty($junctionIds)) {
                $esData['junctionId'] = implode(",",$junctionIds);
                $esData["andOperations"]['junctionId'] = 'in';
            }
            $esRes = $this->realtime_model->searchQuota($esData);
            if (!$esRes) {
                return [];
            }
        }else{
            $chunkJunctionIds = array_chunk($junctionIds,1000);
            $tmpRes = [];
            foreach ($chunkJunctionIds as $Jids){
                $esData = [
                    "source"    => "signal_control",
                    "cityId"    => $cityId,
                    'requestId' => get_traceid(),
                    "dayTime"   => $dayTime,
                    "trailNum"  => $trajNum,
                    "andOperations" => [
                        "cityId"   => "eq",
                        "dayTime"  => "eq",
                        "trailNum" => 'gte',
                    ],
                    "quotaRequest" => [
                        "groupField" => "junctionId",
                        "asc"        => "false",
                        "limit"      => 100,
                    ],
                ];
                if ($params['quota_key'] == 'stop_delay') {
                    $esData['quotaRequest']['quotas'] = 'sum_' . $quotaKey . '*trailNum, sum_trailNum';
                    $esData['quotaRequest']['orderField'] = "weight_avg";
                    $esData['quotaRequest']['quotaType'] = "weight_avg";
                    $esQuotaKey = 'weight_avg'; // es接口返回的字段名
                } else {
                    $esData['quotaRequest']['quotas'] = 'avg_' . $quotaKey;
                    $esData['quotaRequest']['orderField'] = 'avg_' . $quotaKey;
                    $esQuotaKey = 'avg_' . $quotaKey; // es接口返回的字段名
                }

                $esData['junctionId'] = implode(",",$Jids);
                $esData["andOperations"]['junctionId'] = 'in';

                $searchRes = $this->realtime_model->searchQuota($esData);
                if (!empty($searchRes['result']['quotaResults'])) {
                    $tmpRes = array_merge($tmpRes,$searchRes['result']['quotaResults']);
                }
            }
            $esRes = [];
            uasort($tmpRes,function ($a,$b) use($esQuotaKey) {
                $aValue = !empty($a["quotaMap"][$esQuotaKey]) ? $a["quotaMap"][$esQuotaKey] : 0;
                $bValue = !empty($b["quotaMap"][$esQuotaKey]) ? $b["quotaMap"][$esQuotaKey] : 0;
                if ($aValue==$bValue) return 0;
                return ($aValue<$bValue)?1:-1;
            });
            $esRes['result']['quotaResults'] = array_values($tmpRes);
        }

        $data = array_column($esRes['result']['quotaResults'], 'quotaMap');

        $result = [];

        // 所需查询路口名称的路口ID串
        $junctionIds = implode(',', array_unique(array_column($data, 'junctionId')));

        // 获取路口信息
        $junctionsInfo  = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');

        foreach ($data as $k => $val) {
            $result['dataList'][$k] = [
                'logic_junction_id' => $val['junctionId'],
                'junction_name' => $junctionIdName[$val['junctionId']] ?? '未知路口',
                'quota_value' => $val[$esQuotaKey],
            ];
        }

        // 返回数据：指标信息
        $result['quota_info'] = [
            'name' => $quotaConf[$params['quota_key']]['name'],
            'key' => $params['quota_key'],
            'unit' => $quotaConf[$params['quota_key']]['unit'],
        ];

        return $result;
    }

    /**
     * 获取指标趋势图
     * @param $params['city_id']     int    Y 城市ID
     * @param $params['quota_key']   string Y 指标KEY
     * @param $params['date']        string N 日期 yyyy-mm-dd 不传默认当天
     * @param $params['time_point']  string N 时间 HH:ii:ss
     * @param $params['junction_id'] string Y 路口ID
     * @param $params['flow_id']     string Y 相位ID
     * @return array
     * @throws \Exception
     */
    public function getQuotaTrend($params)
    {
        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');
        // 比如stop_rate现在对应的es的新字段是oneStopRatioUp+multiStopRatioUp
        $esQuotaKey = explode('+', $quotaConf[$params['quota_key']]['escolumn']);

        $data = $this->realtime_model->getQuotaByFlowId($params);
        $result = [];

        $result['dataList'] = array_map(function ($val) use ($params, $esQuotaKey) {
            $value = 0;
            foreach ($esQuotaKey as $k=>$v) {
                $value += $val[$v];
            }

            return [
                // 指标值 Y轴
                $value,
                // 时间点 X轴
                date('H:i:s', strtotime($val['dayTime'])),
            ];
        }, $data);

        // 聚合成1分钟1个点
        $result['dataList'] = $this->filterSplitPoint($result['dataList']);


        // 返回数据：指标信息
        $result['quota_info'] = [
            'name' => $quotaConf[$params['quota_key']]['name'],
            'key' => $params['quota_key'],
            'unit' => $quotaConf[$params['quota_key']]['unit'],
        ];

        return $result;
    }

    // 1 过滤，一分钟只返回一个点
    // 2 填充，如果这一分钟没有点，就用0填充
    // dataList已经按照升序排列了
    private function filterSplitPoint($dataList)
    {
        $minutes = [];

        $start = strtotime(date("Y-m-d 00:00:00", time()));
        $end = strtotime(date("Y-m-d H:i:s", time()));
        while ($start < $end) {
            $minute = date("H:i", $start);
            $minutes[$minute] = 0;
            $start = $start + 60;
        }

        foreach ($dataList as $item) {
            $minute = substr($item[1], 0, 5);
            if (isset($minutes[$minute])) {
                $minutes[$minute] = $item[0];
            }
        }

        $ret = [];
        foreach ($minutes as $minute => $val) {
            $ret[] = [$val, $minute . ":00"];
        }
        return $ret;
    }

    /**
     * 获取路口地图数据
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getJunctionMapData($params)
    {
        $logicJunctionId = $params['junction_id'];

        $result = [];

        // 获取最新路网版本 在全部路网版本中取最新的
        $newMapVersion = $this->waymap_model->getLastMapVersion();

        // 获取路口所有相位
        $allFlows = $this->waymap_model->getFlowsInfo($logicJunctionId);

        if (empty($allFlows)) {
            return [];
        }

        // 获取路网路口各相位坐标
        if (empty($allFlows[$logicJunctionId])) {
            return [];
        }

        $ret = $this->waymap_model->getJunctionFlowLngLat($newMapVersion, $logicJunctionId, array_keys($allFlows[$logicJunctionId]));

        foreach ($ret as $k => $v) {
            if (!empty($allFlows[$logicJunctionId][$v['logic_flow_id']])) {
                $result['dataList'][$k]['logic_flow_id'] = $v['logic_flow_id'];
                $result['dataList'][$k]['flow_label']    = $allFlows[$logicJunctionId][$v['logic_flow_id']];
                $result['dataList'][$k]['lng']           = $v['flows'][0][0];
                $result['dataList'][$k]['lat']           = $v['flows'][0][1];
            }
        }
        // 获取路口中心坐标
        $result['center']       = '';
        $centerData['logic_id'] = $logicJunctionId;
        $center                 = $this->waymap_model->getJunctionCenterCoords($logicJunctionId);

        $result['center']      = $center;
        $result['map_version'] = $newMapVersion;

        if (!empty($result['dataList'])) {
            $result['dataList'] = array_values($result['dataList']);
        }

        return $result;
    }


    // 按照半个小时，每半个小时都需要有值，否则用0进行替换
    private function fillEmptyToZero($items)
    {
        $all = [];
        for ($i = 0; $i < 48; $i++) {
            $hour = sprintf('%02d', $i / 2);
            $minute = sprintf('%02d', $i % 2 * 30);
            $hour = "{$hour}:{$minute}";

            $isExist = false;
            foreach ($items as $item) {
                if ($item[1] == $hour) {
                    $isExist = true;
                    $all[] = $item;
                    break;
                }
            }

            if (!$isExist) {
                $all[] = [0, $hour];
            }
        }

        return $all;
    }


    // 评估指标计算
    public function quotaEvaluate($params)
    {
        $cityId          = $params['city_id'];
        $quotaKey        = $params['quota_key'];
        $logicJunctionId = $params['logic_junction_id'];
        $dates        = [$params['date']];

        $func = function ($v) use($quotaKey) {
            return $v[$quotaKey];
        };

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');
        
        // 饱和度计算是几个指标综合计算出来的
        if ($params['quota_key'] == "saturation") {
            $quotaKey = "queue_length, stop_delay, twice_stop_rate";
            $func = function($v) {
                return round(max($v['queue_length'] / 150.0, $v['stop_delay']/ 80.0, $v['twice_stop_rate']),4);
            };
        }

        $select = 'logic_junction_id, logic_flow_id, date, hour, ' . $quotaKey;
        $data = $this->flowDurationV6_model->getQuotaEvaluateCompare(
            $cityId, $logicJunctionId, "", $dates, '', '', $select, 'detail'
        );

        if ($params['quota_key'] == "saturation") {
            $quotaKey = "saturation";
        }

        if (!$data) {
            return [];
        }

        $result = [];


        // 平均对比数组
        $result['data']     = [];

        foreach ($data as $k => $v) {
            $logicFlowId = $v['logic_flow_id'];
            if (!isset($result['data'][$logicFlowId])) {
                $result['data'][$logicFlowId] = [];
            }

            $val = $func($v);
            $result['data'][$logicFlowId][] = [
                $val, $v['hour']
            ];
        }

        // 获取路口信息
        $junctionsInfo  = $this->waymap_model->getJunctionInfo($logicJunctionId);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');

        // 获取路口相位信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($logicJunctionId);
        if (isset($flowsInfo[$logicJunctionId])) {
            $result['flowsInfo'] = $flowsInfo[$logicJunctionId];
        }


        foreach ($result['data'] as $flowId => $vals) {
            $result['data'][$flowId] = $this->fillEmptyToZero($vals);
        }
        // 基本信息
        $result['info'] = [
            'junction_name' => $junctionIdName[$logicJunctionId] ?? '',
            'quota_name' => $quotaConf[$quotaKey]['name'],
            'quota_unit' => $quotaConf[$quotaKey]['unit'],
        ];
        return $result;
    }

    /**
     * 指标评估对比
     *
     * @param $params $params['city_id']         interger Y 城市ID
     *                $params['junction_id']     string   Y 路口ID
     *                $params['quota_key']       string   Y 指标KEY
     *                $params['flow_id']         string   Y 相位ID
     *                $params['base_time']       array    Y 基准时间 [1532880000, 1532966400, 1533052800] 日期时间戳
     *                $params['evaluate_time']   array    Y 评估时间 有可能会有多个评估时间段 [[1532880000, 1532880000]]
     *                $params['base_time_start_end']       array Y 基准时间 开始、结束时间 用于返回数据
     *                $params['evaluate_time_start_end']   array Y 评估时间 开始、结束时间 用于返回数据
     *
     * @return array
     * @throws \Exception
     */
    public function quotaEvaluateCompare($params)
    {
        $cityId          = $params['city_id'];
        $quotaKey        = $params['quota_key'];
        $logicJunctionId = $params['junction_id'];
        $logicFlowId     = $params['flow_id'];
        $evaluateTime    = $params['evaluate_time'];
        $baseTime        = $params['base_time'];

        // 合并所有需要查询的日期
        $dates = Collection::make($evaluateTime)
            ->collapse()
            ->merge($baseTime)
            ->unique()
            ->map(function ($item) {
                return date('Y-m-d', $item);
            })->get();


        if ($logicFlowId == 9999) {
            $select  = 'logic_junction_id, date, hour';
            $groupBy = 'logic_junction_id, hour, date';

            $data = $this->flowDurationV6_model->getQuotaEvaluateCompare($cityId, $logicJunctionId, '', $dates, $groupBy, $quotaKey, $select);
        } else {
            $select = 'logic_junction_id, logic_flow_id, date, hour, ' . $quotaKey;
            $data = $this->flowDurationV6_model->getQuotaEvaluateCompare($cityId, $logicJunctionId, $logicFlowId, $dates, '', '', $select, 'detail');
        }

        if (!$data) {
            return [];
        }

        $result = [];

        // 基准日期
        $baseDate = array_map(function ($val) {
            return date('Y-m-d', $val);
        }, $baseTime);

        // 评估日期
        $evaluateDate = [];
        foreach ($params['evaluate_time'] as $k => $v) {
            $evaluateDate[$k] = array_map(function ($val) {
                return date('Y-m-d', $val);
            }, $v);
        }

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');

        // 平均对比数组
        $avgArr             = [];
        $result['base']     = [];
        $result['evaluate'] = [];
        $result['average']  = [];

        foreach ($data as $k => $v) {
            $date = date('Y-m-d', strtotime($v['date']));

            // 组织基准时间数据
            if (in_array($date, $baseDate, true)) {
                $result['base'][$date][strtotime($v['hour'])] = [
                    // 指标值
                    $quotaConf[$params['quota_key']]['round']($v[$quotaKey]),
                    // 时间
                    $v['hour'],
                ];

                $avgArr['average']['base'][strtotime($v['hour'])][$date] = [
                    'hour' => $v['hour'],
                    'value' => $v[$quotaKey],
                ];
            }

            // 组织评估时间数据
            foreach ($evaluateDate as $kk => $vv) {
                if (in_array($date, $vv, true)) {
                    $result['evaluate'][$kk + 1][$date][strtotime($v['hour'])]        = [
                        // 指标值
                        $quotaConf[$quotaKey]['round']($v[$quotaKey]),
                        // 时间
                        $v['hour'],
                    ];
                    $avgArr['average']['evaluate'][$kk][strtotime($v['hour'])][$date] = [
                        'hour' => $v['hour'],
                        'value' => $v[$quotaKey],
                    ];
                }
            }
        }

        // 处理基准平均值
        if (!empty($avgArr['average']['base'])) {
            ksort($avgArr['average']['base']);
            $result['average']['base'] = array_map(function ($val) use ($quotaConf, $params) {
                $tempData  = array_column($val, 'value');
                $tempSum   = array_sum($tempData);
                $tempCount = count($val);
                list($hour) = array_unique(array_column($val, 'hour'));
                return [
                    // 指标平均值
                    $quotaConf[$params['quota_key']]['round']($tempSum / $tempCount),
                    // 时间
                    $hour,
                ];
            }, $avgArr['average']['base']);
            $result['average']['base'] = array_values($result['average']['base']);
        }
        // 处理评估平均值
        if (!empty($avgArr['average']['evaluate'])) {
            foreach ($avgArr['average']['evaluate'] as $k => $v) {
                ksort($v);
                $result['average']['evaluate'][$k + 1] = array_map(function ($val) use ($quotaConf, $params) {
                    $tempData  = array_column($val, 'value');
                    $tempSum   = array_sum($tempData);
                    $tempCount = count($val);
                    list($hour) = array_unique(array_column($val, 'hour'));
                    return [
                        // 指标平均值
                        $quotaConf[$params['quota_key']]['round']($tempSum / $tempCount),
                        // 时间
                        $hour,
                    ];
                }, $v);
                $result['average']['evaluate'][$k + 1] = array_values($result['average']['evaluate'][$k + 1]);
            }
        }

        // 排序、去除key
        if (!empty($result['base'])) {
            foreach ($result['base'] as $k => $v) {
                ksort($result['base'][$k]);
                $result['base'][$k] = array_values($result['base'][$k]);
            }

            // 补全基准日期
            foreach ($baseDate as $v) {
                if (!array_key_exists($v, $result['base'])) {
                    $result['base'][$v] = [];
                }

            }
        }

        if (!empty($result['evaluate'])) {
            foreach ($result['evaluate'] as $k => $v) {
                foreach ($v as $kk => $vv) {
                    ksort($result['evaluate'][$k][$kk]);
                    $result['evaluate'][$k][$kk] = array_values($result['evaluate'][$k][$kk]);
                }
            }

            // 补全评估日期
            foreach ($evaluateDate as $k => $v) {
                foreach ($v as $vv) {
                    if (empty($result['evaluate'][$k + 1])) {
                        $result['evaluate'][$k + 1] = [];
                    }
                    if (!empty($result['evaluate'][$k + 1])
                        && !array_key_exists($vv, $result['evaluate'][$k + 1])) {
                        $result['evaluate'][$k + 1][$vv] = [];
                    }
                    if (empty($result['average']['evaluate'][$k + 1])) {
                        $result['average']['evaluate'][$k + 1] = [];
                    }
                }
            }
        }

        // 获取路口信息
        $junctionsInfo  = $this->waymap_model->getJunctionInfo($logicJunctionId);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');

        // 获取路口相位信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($logicJunctionId);
        // 将所有方向放入路口相位信息中
        $flowsInfo[$logicJunctionId]['9999'] = '所有方向';

        $result = $this->fillZeroResult($result);

        // 基本信息
        $result['info'] = [
            'junction_name' => $junctionIdName[$logicJunctionId] ?? '',
            'quota_name' => $quotaConf[$quotaKey]['name'],
            'quota_unit' => $quotaConf[$quotaKey]['unit'],
            'base_time' => $params['base_time_start_end'],
            'evaluate_time' => $params['evaluate_time_start_end'],
            'direction' => $flowsInfo[$logicJunctionId][$logicFlowId] ?? '',
        ];

        $downloadId = md5(json_encode($result));

        // 将ID返回前端以供下载使用
        $result['info']['download_id'] = $downloadId;

        // 缓存数据
        $this->redis_model->setComparisonDownloadData($downloadId, $result);

        return $result;
    }


    // 将result指标空的地方全部填充为0，这个和PM对过了
    private function fillZeroResult($result)
    {
        if (isset($result['average']['base'])) {
            $result['average']['base'] = $this->fillEmptyToZero($result['average']['base']);
        }

        if (isset($result['average']['evaluate'])) {
            foreach ($result['average']['evaluate'] as $id => $items) {
                $result['average']['evaluate'][$id] = $this->fillEmptyToZero($result['average']['evaluate'][$id]);
            }
        }

        if (isset($result['base'])) {
            foreach ($result['base'] as $date => $val) {
                $result['base'][$date] = $this->fillEmptyToZero($val);
            }
        }

        if (isset($result['evaluate'])) {
            foreach ($result['evaluate'] as $id => $items) {
                foreach ($result['evaluate'][$id] as $date => $val) {
                    $result['evaluate'][$id][$date] = $this->fillEmptyToZero($val);
                }
            }
        }

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
        $key = $this->config->item('quota_evaluate_key_prefix') . $params['download_id'];

        if (!$this->redis_model->getData($key)) {
            throw new \Exception('请先评估再下载', ERR_DEFAULT);
        }

        return [
            'download_url' => '/api/evaluate/download?download_id=' . $params['download_id'],
        ];
    }

    public function getExcelArrayOther($data)
    {
        $timeArray = hourRange();

        $table = [];

        $table[] = $timeArray;
        array_unshift($table[0], "日期-时间");

        $data = array_map(function ($value) {
            return array_column($value, 0, 1);
        }, $data);

        foreach ($data as $key => $value) {
            $column   = [];
            $column[] = $key;
            foreach ($timeArray as $item) {
                $column[] = $value[$item] ?? '-';
            }
            $table[] = $column;
        }

        return $table;
    }


    // 下载评估的指标excel
    public function downloadQuotaEvaluate($params, $data)
    {
        $fileName = "{$data['info']['junction_name']}_{$data['info']['quota_name']}_" . date('Ymd');

        $objPHPExcel = new \PHPExcel();
        $objSheet    = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        $detailParams = [
            ['指标名', $data['info']['quota_name']],
            ['方向', "所有方向"],
            ['评估时间', $params['date']],
        ];

        $detailParams[] = ['指标单位', $data['info']['quota_unit']];

        $objSheet->mergeCells('A1:F1');
        $objSheet->setCellValue('A1', $fileName);
        $objSheet->fromArray($detailParams, null, 'A4');

        $styles = $this->config->item('excel_style');
        $objSheet->getStyle('A1')->applyFromArray($styles['title']);
        $rows_idx = count($detailParams) + 3;
        $objSheet->getStyle("A4:A{$rows_idx}")->getFont()->setSize(12)->setBold(true);

        $line = 6 + count($detailParams);

        // 输出头部
        $table = hourRange();
        array_unshift($table, "方向");
        $objSheet->fromArray($table, null, 'A' . $line);
        $cols_cnt   = count($table) - 1;
        $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $line)->applyFromArray($styles['header']);

        $line++;

        $flowsInfo = $data['flowsInfo'];
        // 输出内容
        foreach ($data['data'] as $flowId => $datum) {
            $table = [];
            $table[] = $flowsInfo[$flowId];
            foreach ($datum as $item) {
                $table[] = $item[0];
            }

            $objSheet->fromArray($table, null, 'A' . $line, true);
            $objSheet->getStyle("A{$line}:A{$line}")->applyFromArray($styles['header']);

            $line++;
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

    /**
     * 评估数据下载地址
     *
     * @param $params
     *
     * @throws \Exception
     * @throws \PHPExcel_Exception
     */
    public function download($params)
    {

        $downloadId = $params['download_id'];

        $data = $this->redis_model->getComparisonDownloadData($downloadId);

        if (!$data) {
            throw new \Exception('请先评估再下载', ERR_DEFAULT);
        }

        $fileName = "{$data['info']['junction_name']}_{$data['info']['quota_name']}_" . date('Ymd');

        $objPHPExcel = new \PHPExcel();
        $objSheet    = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('数据');

        $detailParams = [
            ['指标名', $data['info']['quota_name']],
            ['方向', $data['info']['direction']],
            ['基准时间', implode(' ~ ', $data['info']['base_time'])],
        ];
        foreach ($data['info']['evaluate_time'] as $key => $item) {
            $detailParams[] = ['评估时间' . ($key + 1), implode(' ~ ', $item)];
        }

        $detailParams[] = ['指标单位', $data['info']['quota_unit']];

        $objSheet->mergeCells('A1:F1');
        $objSheet->setCellValue('A1', $fileName);
        $objSheet->fromArray($detailParams, null, 'A4');

        $styles = $this->config->item('excel_style');
        $objSheet->getStyle('A1')->applyFromArray($styles['title']);
        $rows_idx = count($detailParams) + 3;
        $objSheet->getStyle("A4:A{$rows_idx}")->getFont()->setSize(12)->setBold(true);

        $line = 6 + count($detailParams);

        if (!empty($data['base'])) {

            $table = $this->getExcelArrayOther($data['base']);

            $objSheet->fromArray($table, null, 'A' . $line);

            $rows_cnt   = count($table);
            $cols_cnt   = count($table[0]) - 1;
            $rows_index = $rows_cnt + $line - 1;

            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $rows_index)->applyFromArray($styles['content']);
            $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
            $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $line)->applyFromArray($styles['header']);

            $line += ($rows_cnt + 2);
        }

        if (!empty($data['evaluate'])) {

            foreach ($data['evaluate'] as $datum) {
                $table = $this->getExcelArrayOther($datum);

                $objSheet->fromArray($table, null, 'A' . $line);

                $rows_cnt   = count($table);
                $cols_cnt   = count($table[0]) - 1;
                $rows_index = $rows_cnt + $line - 1;
                $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $rows_index)->applyFromArray($styles['content']);
                $objSheet->getStyle("A{$line}:A{$rows_index}")->applyFromArray($styles['header']);
                $objSheet->getStyle("A{$line}:" . intToChr($cols_cnt) . $line)->applyFromArray($styles['header']);

                $line += ($rows_cnt + 2);
            }
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
}