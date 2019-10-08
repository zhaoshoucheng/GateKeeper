<?php

/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午4:22
 */
class Realtime_model extends CI_Model
{
    // es interface addr
    private $esUrl = '';
    private $newEsUrl = '';
    private $engine = '';
    private $quotaCityIds = [];

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        // load config
        $this->load->config('nconf');
        $this->load->config('evaluate_conf');
        $this->load->config('realtime_conf');
        $this->esUrl = $this->config->item('es_interface');
        $this->newEsUrl = $this->config->item('new_es_interface');
        $this->engine = $this->config->item('data_engine');
        //$this->quotaCityIds = $this->config->item('quota_v2_city_ids');

        // load model
        $this->load->model('redis_model');
        $this->load->model('common_model');
        $this->load->model('waymap_model');

    }

    /**
     * ES诊断明细查询方法
     * @param $data      array es查询条件数组
     * @param $scroll    bool  是否需要轮循 默认需要 true
     * @return array
     */
    public function searchDetail($data, $scroll = true)
    {
        $this->quotaCityIds = $this->common_model->getV5DMPCityID();

        $baseUrl = $this->esUrl;
        if(!empty($data["cityId"]) && in_array($data["cityId"],$this->quotaCityIds)){
            $baseUrl = $this->newEsUrl;
        }
        $resData = [];
        $result = httpPOST($baseUrl . '/estimate/diagnosis/queryIndices', $data, 0, 'json');

        if (!$result) {
            throw new \Exception('调用es接口 queryIndices 失败！', ERR_DEFAULT);
        }
        $result = json_decode($result, true);

        if ($scroll) {
            if ($result['code'] == '000000') {  // 000000:还有数据可查询 400001:查询完成
                $resData = $result['result']['diagnosisIndices'];
                $data['scrollsId'] = $result['result']['scrollsId'];
                $resData = array_merge($resData, $this->searchDetail($data));
            }

            if ($result['code'] == '400001') {
                $resData = array_merge($resData, $result['result']['diagnosisIndices']);
            }
        } else {
            $resData = $result['result']['diagnosisIndices'];
        }

        if ($result['code'] != '000000' && $result['code'] != '400001') {
            throw new \Exception($result['message'], ERR_DEFAULT);
        }

        return $resData;
    }

    /**
     * ES诊断指标查询方法 avg sum 等
     * @param $data array es查询条件数组
     * @return array
     */
    public function searchQuota($data)
    {
        $this->quotaCityIds = $this->common_model->getV5DMPCityID();

        $baseUrl = $this->esUrl;
        if(!empty($data["cityId"]) && in_array($data["cityId"],$this->quotaCityIds)){
            $baseUrl = $this->newEsUrl;
        }
        $queryUrl = $baseUrl . '/estimate/diagnosis/queryQuota';
        $result = httpPOST($queryUrl, $data, 9000, 'json');
        if (!$result) {
            com_log_warning('searchQuota_result_invalid', 0, "", compact("queryUrl","data","result"));
            throw new \Exception('调用es接口 queryIndices 失败！', ERR_DEFAULT);
        }
        $result = json_decode($result, true);

        if ($result['code'] != '000000' && $result['code'] != '400001') {
            com_log_warning('searchQuota_result_errcode', 0, "", compact("queryUrl","data","result"));
            throw new \Exception($result['message'], ERR_DEFAULT);
        }
        return $result;
    }

    /**
     * 获得指定城市实时表的最新 hour
     *
     * @param $cityId
     *
     * @return array
     * @throws Exception
     */
    public function getLastestHour($cityId)
    {
        $data = [
            'source' => 'signal_control', // 调用方
            'cityId' => $cityId,          // 城市ID
            'requestId' => get_traceid(),    // trace id
            'timestamp' => strtotime(date('Y-m-d')) * 1000, // 当天0点(yyyy-mm-dd 00:00:00)毫秒时间戳
            'andOperations' => [
                'cityId' => 'eq', // cityId相等
                'timestamp' => 'gte' // 大于等于当天开始时间
            ],
            'quotaRequest' => [
                "groupField" => 'dayTime',
                "limit" => 1,
                "orderField" => "max_timestamp",
                "asc" => false,
                "quotas" => "max_timestamp",
            ],
        ];
        $res = $this->searchQuota($data);
        if (empty($res['result']['quotaResults'][0]['quotaMap'])) {
            throw new \Exception('获取实时数据最新批次hour失败！', ERR_DEFAULT);
        }

        $lastHour = date('H:i:s', strtotime($res['result']['quotaResults'][0]['quotaMap']['dayTime']));
        return $lastHour;
    }

    /**
     * 平均延误曲线图
     * @param $cityId int    城市ID
     * @param $date   string 日期 yyyy-mm-dd
     * @param $hour   string 时间 HH:ii:ss
     * @param $junctionIds   array 路口Id
     * @return array
     */
    public function avgStopdelay($cityId, $date, $hour, $junctionIds=[])
    {

        $data = [
            "source" => "signal_control",
            "cityId" => $cityId,
            'requestId' => get_traceid(),
            "dayTime" => $date . ' ' . $hour,
            "andOperations" => [
                "cityId" => "eq",
                "dayTime" => "eq",
            ],
            "quotaRequest" => [
                "quotaType" => "weight_avg",
                "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
                "groupField" => "dayTime",
            ],
        ];
        if (!empty($junctionIds)) {
            $data['junctionId'] = implode(",",$junctionIds);
            $data["andOperations"]['junctionId'] = 'in';
        }

        $esRes = $this->searchQuota($data);
        if (empty($esRes['result']['quotaResults'])) {
            return [];
        }
        $result = [];
        if(!empty($esRes['result']['quotaResults'])){
            foreach ($esRes['result']['quotaResults'] as $k => $v) {
                $result = [
                    'avg_stop_delay' => $v['quotaMap']['weight_avg'],
                    'hour' => date('H:i:s', strtotime($v['quotaMap']['dayTime'])),
                ];
            }
        }
        return $result;
    }

    /**
     * 平均延误曲线图
     * @param $cityId int    城市ID
     * @param $date   string 日期 yyyy-mm-dd
     * @param $hour   string 时间 HH:ii:ss
     * @param $junctionIds   array 路口Id
     * @return array
     */
    public function avgStopdelayByJunctionId($cityId, $date, $hour, $junctionIds=[])
    {
        $chunkJunctionIds = array_chunk($junctionIds,500);
        $tmpRs = [];
        foreach ($chunkJunctionIds as $ids){
            $data = [
                "source" => "signal_control",
                "cityId" => $cityId,
                'requestId' => get_traceid(),
                "dayTime" => $date . ' ' . $hour,
                "andOperations" => [
                    "cityId" => "eq",
                    "dayTime" => "eq",
                ],
                "quotaRequest" => [
                    "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
                    "groupField" => "dayTime",
                ],
            ];
            $data['junctionId'] = implode(",",$ids);
            $data["andOperations"]['junctionId'] = 'in';
            if(count($ids)==0){
                return [];
            }
            $esRes = $this->searchQuota($data);
            if (empty($esRes['result']['quotaResults'])) {
                //return [];
            }
            if(!empty($esRes['result']['quotaResults'])){
                foreach ($esRes['result']['quotaResults'] as $k => $v) {
                    $hour=date('H:i:s', strtotime($v['quotaMap']['dayTime']));
                    if(!empty($tmpRs[$hour])){
                        $tmpRs[$hour]['sum_stopDelayUp*trailNum']+=$tmpRs[$hour]['sum_stopDelayUp*trailNum'];
                        $tmpRs[$hour]['sum_trailNum']+=$tmpRs[$hour]['sum_trailNum'];
                    }else{
                        $tmpRs[$hour] = [
                            'sum_stopDelayUp*trailNum' => $v['quotaMap']['sum_stopDelayUp*trailNum'],
                            'sum_trailNum' => $v['quotaMap']['sum_trailNum'],
                        ];
                    }
                }
            }
        }

        $result = [];
        foreach ($tmpRs as $hour=>$item){
            $result = [
                'avg_stop_delay' => $item['sum_stopDelayUp*trailNum']/$item['sum_trailNum'],
                'hour' => $hour,
            ];
        }
        return $result;
    }

    /**
     * 获取区域指标平均值
     * @param $cityId      int    城市ID
     * @param $junctionIds string 区域路口ID串
     * @param $dayTime     string 时间 yyyy-mm-dd HH:ii:ss
     * @param $quotaKey    string 指标KEY
     * @return array
     */
    public function getEsAreaQuotaValue($cityId, $junctionIds, $dayTime, $quotaKey)
    {
        $esData = [
            'source' => 'signal_control',
            'cityId' => $cityId,
            'junctionId' => $junctionIds,
            'dayTime' => $dayTime,
            'requestId' => get_traceid(),
            'andOperations' => [
                'junctionId' => 'in',
                'cityId' => 'eq',
                'dayTime' => 'eq',
            ],
            'quotaRequest' => [
                "groupField" => "dayTime",
                "quotaType" => "weight_avg",
                "quotas" => "sum_{$quotaKey}*trailNum, sum_trailNum",
            ],
        ];
        $res = $this->searchQuota($esData);
        if (!empty($res['result']['quotaResults'])) {
            list($quotaValueInfo) = $res['result']['quotaResults'];
        }

        return [
            date('H:i:s', strtotime($dayTime)) => [
                'value' => $quotaValueInfo['quotaMap']['weight_avg'] ?? 0,
                'hour' => date('H:i:s', strtotime($quotaValueInfo['quotaMap']['dayTime'] ?? "")),
            ]
        ];
    }

    public function getRedisAreaQuotaValueCurve($areaId, $quotaKey){
        $areaQuotaInfoKey = sprintf("itstool_area_quotainfo_%s_%s_%s",date("Y-m-d"),$areaId,$quotaKey);
        $list = $this->redis_model->lrange($areaQuotaInfoKey);
        $newList = [];
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $tmp = json_decode($val,true);
                if($tmp["hour"]!="08:00:00"){
                    $newList[] = $tmp;
                }
            }
        }
        return $newList;
    }

    /**
     * 获取区域指标平均值
     * @param $cityId      int    城市ID
     * @param $junctionIds string 区域路口ID串
     * @param $date        string 时间 yyyy-mm-dd
     * @param $quotaKey    string 指标KEY
     * @return array
     */
    public function getEsAreaQuotaValueCurve($cityId, $junctionIds, $date, $quotaKey)
    {
        $esData = [
            'source' => 'signal_control',
            'cityId' => $cityId,
            'junctionId' => $junctionIds,
            'requestId' => get_traceid(),
            'andOperations' => [
                'junctionId' => 'in',
                'cityId' => 'eq',
                'timestamp' => 'range',
            ],
            'quotaRequest' => [
                "groupField" => "dayTime",
                "quotaType" => "weight_avg",
                "quotas" => "sum_{$quotaKey}*trailNum, sum_trailNum",
                "orderField" => 'dayTime',
                "asc" => true,
            ],
        ];

        $result = [];
        // 因为一次性获取全天的数据会影响集群性能，会被禁止，所有要分断进行获取 y m d h i s
        $nowHour = date('H') + 1;
        for ($i = 0; $i < $nowHour; $i += 3) {
            $sTime = strtotime($i . ':00') * 1000;
            $eTime = strtotime(($i + 3) . ':00') * 1000;
            if ($i == 21) {
                $eTime = strtotime('23:59:59') * 1000;
            }
            $esData['timestamp'] = "[{$sTime}, {$eTime}]";
            $res = $this->searchQuota($esData);
            if (!empty($res['result']['quotaResults'])) {
                foreach ($res['result']['quotaResults'] as $k => $v) {
                    $hour = date('H:i:s', strtotime($v['quotaMap']['dayTime']));
                    $result[$hour] = [
                        'value' => $v['quotaMap']['weight_avg'],
                        'hour' => $hour,
                    ];
                }
            }
        }
        if (!empty($result)) {
            $result = array_values($result);
        }
        return $result;
    }

    /**
     * 获取实时指标路口数据（开放平台）
     * @param $cityId int    城市ID
     * @param $date   string 日期 yyyy-mm-dd
     * @param $hour   string 时间 HH:ii:ss
     * @param $junctionIds   array 路口数组
     * @return array
     */
    public function getRealTimeJunctionsQuota($cityId, $date, $hour, $junctionIds=[],$trajNum=5)
    {
        if($cityId==175){
            $trajNum = 1;
        }
        $data = [
            'source' => 'signal_control', // 调用方
            'cityId' => $cityId,          // 城市ID
            'requestId' => get_traceid(),    // trace id
            'trailNum' => $trajNum,
            'dayTime' => $date . " " . $hour,
            'andOperations' => [
                'cityId' => 'eq',  // cityId相等
                'trailNum' => 'gte', // 轨迹数大于等于5
                'dayTime' => 'eq',  // 等于hour
            ],
            'limit' => 5000,
        ];
        if (!empty($junctionIds)) {
            $data['junctionId'] = implode(",",$junctionIds);
            $data["andOperations"]['junctionId'] = 'in';
        }
        $realTimeEsData = $this->searchDetail($data);
        return $realTimeEsData;
    }

    /**
     * 获取实时指标路口数据（概览页路口列表）
     * @param $cityId int    城市ID
     * @param $date   string 日期 yyyy-mm-dd
     * @param $hour   string 时间 HH:ii:ss
     * @param $junctionIds   array 路口数组
     * @return array
     */
    public function getRealTimeJunctions($cityId, $date, $hour, $junctionIds=[])
    {
        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }
        $data = [
            'source' => 'signal_control', // 调用方
            'cityId' => $cityId,          // 城市ID
            'requestId' => get_traceid(),    // trace id
            'trailNum' => $trajNum,
            'dayTime' => $date . " " . $hour,
            'andOperations' => [
                'cityId' => 'eq',  // cityId相等
                'trailNum' => 'gte', // 轨迹数大于等于5
                'dayTime' => 'eq',  // 等于hour
            ],
            'limit' => 5000,
        ];
        if (!empty($junctionIds)) {
            $data['junctionId'] = implode(",",$junctionIds);
            $data["andOperations"]['junctionId'] = 'in';
        }
        $realTimeEsData = $this->searchDetail($data);
        $result = [];
        foreach ($realTimeEsData as $k => $v) {
            $result[$k] = [
                'logic_junction_id' => $v['junctionId'],
                'hour' => date('H:i:s', strtotime($v['dayTime'])),
                'logic_flow_id' => $v['movementId'],
                'stop_time_cycle' => $v['avgStopNumUp'],
                'spillover_rate' => $v['spilloverRateDown'],
                'queue_length' => $v['queueLengthUp'],
                'stop_delay' => $v['stopDelayUp'],
                'stop_rate' => ($v['oneStopRatioUp'] + $v['multiStopRatioUp']),
                'twice_stop_rate' => $v['multiStopRatioUp'],
                'speed' => $v['avgSpeedUp'],
                'free_flow_speed' => $v['freeFlowSpeedUp'],
                'traj_count' => $v['trailNum'],
            ];
        }

        return $result;
    }

    /**
     * 根据 flow id 集合获取相应数据
     *
     * @param        $cityId
     * @param        $hour
     * @param        $logicJunctionId
     * @param        $logicFlowId
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getFlowsInFlowIds($cityId, $hour, $logicJunctionId, $logicFlowId)
    {
        $date = date('Y-m-d');

        $flowIds = implode(',', $logicFlowId);

        $data = [
            'source' => 'signal_control', // 调用方
            'cityId' => $cityId,          // 城市ID
            'requestId' => get_traceid(),    // trace id
            'junctionId' => $logicJunctionId,
            'dayTime' => $date . " " . $hour,
            'movementId' => "{$flowIds}",
            'andOperations' => [
                'cityId' => 'eq',
                'junctionId' => 'eq',
                'dayTime' => 'eq',
                'movementId' => 'in',
            ],
            'limit' => 5000,
        ];
        $realTimeEsData = $this->searchDetail($data);

        return $realTimeEsData;
    }

    public function getJunctionQuotaCurve($params){
        $timestamp = strtotime($params['date'] . ' 00:00:00') * 1000;
        $dayTime = $params['date'] . ' 00:00:00';
        $quotaKey = camelize($params['quota_key']);
        $data = [
            'source' => 'signal_control',   // 调用方
            'cityId' => $params['city_id'], // 城市ID
            'requestId' => get_traceid(),      // trace id
            'timestamp' => $timestamp,
            'dayTime'=> $dayTime,
            'junctionId' => $params['junction_id'],
            'trailNum' => 0,
            'andOperations' => [
                'cityId' => 'eq',  // cityId相等
                'timestamp' => 'gte', // 大于等于当天开始时间
                'dayTime' => 'gte', // 大于等于当天开始时间
                'junctionId' => 'eq',
                'trailNum' => 'gte',
            ],
            "quotaRequest" => [
                'quotaType' => 'weight_avg',
                'quotas' => 'sum_'.$quotaKey.'*trailNum,sum_trailNum',
                'groupField' => 'dayTime',
                'orderField' => 'dayTime',
                'asc' => 'true',
            ],
        ];

        //特殊设置
        if (in_array($params['quota_key'],["stop_delay","avg_speed_up","one_stop_ratio_up","traffic_jam_index_up","travel_time_up"])) {
            $esData['quotaRequest']['quotas'] = 'sum_' . $quotaKey . '*trailNum, sum_trailNum';
            $esData['quotaRequest']['quotaType'] = "weight_avg";
        } elseif(in_array($params['quota_key'],["spillover_rate_up"])) {
            $esData['quotaRequest']['quotas'] = 'max_' . $quotaKey;
            $esData['quotaRequest']['quotaType'] = "max";
        } else {
            $esData['quotaRequest']['quotas'] = 'avg_' . $quotaKey;
            $esData['quotaRequest']['quotaType'] = "avg";
        }
        $realTimeEsData = $this->searchQuota($data);
        return $realTimeEsData;
    }


    /*
'{ 
    "source": "signal_control",
    "cityId": "38",
    "requestId": "f4eed5d87edc4caaa969f1c697a511c2",
    "dayTime": "2019-09-29 00:00:00",
    "junctionId": "2019080918_3849656",
    "trailNum": 0,
    "andOperations": {
        "cityId": "eq",
        "dayTime": "gte",
        "junctionId": "eq",
        "trailNum": "gte"
    },
    "quotaRequest": {
        "quotaType": "weight_avg",
        "quotas": "sum_stopDelayUp*trailNum,sum_trailNum",
        "groupField": "dayTime",
        "orderField": "dayTime",
        "asc": "false"
    }
}'


    */

    /**
     * 获取指标趋势图
     * @param $params ['city_id']      int    Y 城市ID
     * @param $params ['date']         string N 日期 yyyy-mm-dd 不传默认当天
     * @param $params ['junction_id']  string Y 路口ID
     * @param $params ['flow_id']      string Y 相位ID
     * @return array
     * @throws Exception
     */
    public function getQuotaByFlowId($params,$cached=false)
    {
        $timestamp = strtotime($params['date'] . ' 00:00:00') * 1000;
        $data = [
            'source' => 'signal_control',   // 调用方
            'cityId' => $params['city_id'], // 城市ID
            'requestId' => get_traceid(),      // trace id
            'timestamp' => $timestamp,
            'junctionId' => $params['junction_id'],
            'movementId' => $params['flow_id'],
            'andOperations' => [
                'cityId' => 'eq',  // cityId相等
                'timestamp' => 'gte', // 大于等于当天开始时间
                'junctionId' => 'eq',
                'movementId' => 'eq',
            ],
            'limit' => 5000,
            "orderOperations" => [
                [
                    'orderField' => 'dayTime',
                    'orderType' => 'ASC',
                ],
            ],
        ];

        $redis_key = 'getQuotaByFlowId_' . md5(json_encode($data));
        $result = $cached ? $this->redis_model->getData($redis_key) : [];
        if (!$result) {
            $realTimeEsData = $this->searchDetail($data);
            if($cached){
                $this->redis_model->setEx($redis_key, json_encode($realTimeEsData), 120);
            }
            return $realTimeEsData;
        }
        return json_decode($result, true);
    }

    /**
     * 通过路口获取延误top20
     * @param  $cityId    int    城市ID
     * @param  $date      string 日期 yyyy-mm-dd
     * @param  $hour      string 时间 HH:ii:ss
     * @param  $pagesize  int    获取数量
     * @param  $junctionIds  array    路口id数组
     * @return array
     * @throws Exception
     */
    public function getTopStopDelayByJunctionId($cityId, $date, $hour, $pagesize, $junctionIds = [])
    {
        $dayTime = $date . ' ' . $hour;

        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }

        $tmpRs = [];
        $chunkJunctionIds=array_chunk($junctionIds,1000);
        foreach ($chunkJunctionIds as $Jids){
            $data = [
                "source" => "signal_control",
                "cityId" => $cityId,
                'requestId' => get_traceid(),
                "dayTime" => $dayTime,
                "trailNum" => $trajNum,
                "andOperations" => [
                    "cityId" => "eq",
                    "dayTime" => "eq",
                    "trailNum" => 'gte',
                ],
                "quotaRequest" => [
                    "quotaType" => "weight_avg",
                    "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
                    "groupField" => "junctionId",
                    "orderField" => "weight_avg",
                    "asc" => "false",
                    "limit" => $pagesize,
                ],
            ];
            if (!empty($Jids)) {
                $data['junctionId'] = implode(",",$Jids);
                $data["andOperations"]['junctionId'] = 'in';
            }

            $esRes = $this->searchQuota($data);
            if (!empty($esRes['result']['quotaResults'])) {
                $tmpRs = array_merge($tmpRs,$esRes['result']['quotaResults']);
            }
        }

        uasort($tmpRs,function ($a,$b) {
            $aValue = !empty($a["quotaMap"]["weight_avg"]) ? $a["quotaMap"]["weight_avg"] : 0;
            $bValue = !empty($b["quotaMap"]["weight_avg"]) ? $b["quotaMap"]["weight_avg"] : 0;
            if ($aValue==$bValue) return 0;
            return ($aValue<$bValue)?1:-1;
        });
        return $tmpRs;
    }

    /**
     * 延误top20
     * @param  $cityId    int    城市ID
     * @param  $date      string 日期 yyyy-mm-dd
     * @param  $hour      string 时间 HH:ii:ss
     * @param  $pagesize  int    获取数量
     * @param  $junctionIds  array    路口id数组
     * @return array
     * @throws Exception
     */
    public function getTopStopDelay($cityId, $date, $hour, $pagesize, $junctionIds = [])
    {
        if(!empty($junctionIds)){
            return $this->getTopStopDelayByJunctionId($cityId,$date,$hour,$pagesize,$junctionIds);
        }

        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }

        $dayTime = $date . ' ' . $hour;
        $data = [
            "source" => "signal_control",
            "cityId" => $cityId,
            'requestId' => get_traceid(),
            "dayTime" => $dayTime,
            "trailNum" => $trajNum,
            "andOperations" => [
                "cityId" => "eq",
                "dayTime" => "eq",
                "trailNum" => 'gte',
            ],
            "quotaRequest" => [
                "quotaType" => "weight_avg",
                "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
                "groupField" => "junctionId",
                "orderField" => "weight_avg",
                "asc" => "false",
                "limit" => $pagesize,
            ],
        ];

        $esRes = $this->searchQuota($data);
        if (empty($esRes['result']['quotaResults'])) {
            return [];
        }

        return $esRes['result']['quotaResults'];
    }

    /**
     * 停车top20 通过路口id
     * @param  $cityId    int    城市ID
     * @param  $date      string 日期 yyyy-mm-dd
     * @param  $hour      string 时间 HH:ii:ss
     * @param  $pagesize  int    获取数量
     * @param  $junctionIds  array    路口数组
     * @return array
     * @throws Exception
     */
    public function getTopCycleTimeByJunctionId($cityId, $date, $hour, $pagesize, $junctionIds=[])
    {
        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }

        $tmpRs = [];
        $chunkJunctionIds=array_chunk($junctionIds,500);
        foreach ($chunkJunctionIds as $Jids){
            $data = [
                'source' => 'signal_control', // 调用方
                'cityId' => $cityId,          // 城市ID
                'requestId' => get_traceid(),    // trace id
                'trailNum' => $trajNum,
                'dayTime' => $date . " " . $hour,
                'andOperations' => [
                    'cityId' => 'eq',  // cityId相等
                    'trailNum' => 'gte', // 轨迹数大于等于5
                    'dayTime' => 'eq',  // 等于hour
                ],
                'limit' => $pagesize,
                "orderOperations" => [
                    [
                        'orderField' => 'avgStopNumUp',
                        'orderType' => 'DESC',
                    ],
                ],
            ];
            if (!empty($Jids)) {
                $data['junctionId'] = implode(",",$Jids);
                $data["andOperations"]['junctionId'] = 'in';
            }
            $esRes = $this->searchDetail($data,false);
            if (!empty($esRes)) {
                $tmpRs = array_merge($tmpRs,$esRes);
            }
        }

        uasort($tmpRs,function ($a,$b) {
            $aValue = !empty($a["avgStopNumUp"]) ? $a["avgStopNumUp"]:0;
            $bValue = !empty($b["avgStopNumUp"]) ? $b["avgStopNumUp"]:0;
            if ($aValue==$bValue) return 0;
            return ($aValue<$bValue)?1:-1;
        });
        return array_values($tmpRs);
    }

    /**
     * 停车top20
     * @param  $cityId    int    城市ID
     * @param  $date      string 日期 yyyy-mm-dd
     * @param  $hour      string 时间 HH:ii:ss
     * @param  $pagesize  int    获取数量
     * @param  $junctionIds  array    路口数组
     * @return array
     * @throws Exception
     */
    public function getTopCycleTime($cityId, $date, $hour, $pagesize, $junctionIds=[])
    {
        if(!empty($junctionIds)){
            return $this->getTopCycleTimeByJunctionId($cityId, $date, $hour, $pagesize, $junctionIds);
        }

        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }

        $data = [
            'source' => 'signal_control', // 调用方
            'cityId' => $cityId,          // 城市ID
            'requestId' => get_traceid(),    // trace id
            'trailNum' => $trajNum,
            'dayTime' => $date . " " . $hour,
            'andOperations' => [
                'cityId' => 'eq',  // cityId相等
                'trailNum' => 'gte', // 轨迹数大于等于5
                'dayTime' => 'eq',  // 等于hour
            ],
            'limit' => $pagesize,
            "orderOperations" => [
                [
                    'orderField' => 'avgStopNumUp',
                    'orderType' => 'DESC',
                ],
            ],
        ];
        return $this->searchDetail($data, false);
    }

    /**
     * 获取路口的当天平均延误数据
     * @param        $cityId
     * @param        $date
     * @param        $hour
     * @param        $pagesize
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getJunctionAvgStopDelayList($cityId, $junctionId, $date)
    {
        $this->isExisted($cityId);
        $res = $this->db->select("avg(`stop_delay`) as avg_stop_delay, hour")
                ->from($this->tb . $cityId)
                ->where('logic_junction_id', $junctionId)
                // ->where('traj_count >=', 10)
                ->where('updated_at >=', $date . ' 00:00:00')
                ->where('updated_at <=', $date . ' 23:59:59')
                ->group_by('hour')
                ->order_by('hour')
                ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 获取路口的当天平均延误数据
     * @param        $cityId
     * @param        $date
     * @param        $offset
     *
     * @return array
     * @throws Exception
     */
    public function delOutdateRealtimeData($cityId, $date, $offset)
    {
        $this->isExisted($cityId);
        $this->db->where("updated_at < ", $date . ' 00:00:00');
        $this->db->limit($offset);
        return $this->db->delete($this->tb . $cityId);
    }

    public function getOutdateRealtimeDataCnt($cityId, $date)
    {
        $this->isExisted($cityId);
        $res = $this->db->select("count(id) as cnt")
                        ->from($this->tb . $cityId)
                        ->where("updated_at < ", $date . ' 00:00:00')
                        ->get()
                        ->row_array();
        if (!isset($res['cnt'])) {
            return false;
        }
        return $res['cnt'];
    }


    /**
     * 获取路口指标排序列表
     * @param $params['city_id']        int     Y 城市ID
     * @param $params['junction_id']    array   N 路口ID
     * @param $params['quota_key']      string  Y 指标KEY
     * @param $params['date']           string  N 日期 yyyy-mm-dd
     * @param $params['time_point']     string  N 时间 HH:ii:ss
     * @param $params['limit']          int     Y 限定数量
     *
     * @return array
     * @throws \Exception
     */
    public function getJunctionQuotaSortList($params)
    {
        $cityId   = $params['city_id'];
        $limit   = $params['limit'];
        $junctionIds = !empty($params['junction_id']) ? $params['junction_id'] : [];

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');
        $quotaKey = $quotaConf[$params['quota_key']]['escolumn'];

        // 获取最近时间
        $dayTime = $params['date'] . ' ' . $params['time_point'];

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
                    "limit"      => $limit,
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
            $esRes = $this->searchQuota($esData);
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
                        "limit"      => $limit,
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

                $searchRes = $this->searchQuota($esData);
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

    public function junctionRealtimeFlowQuotaList($params)
    {
        $cityId   = $params['city_id'];
        $limit   = $params['limit'];
        $junctionIds = !empty($params['junction_id']) ? $params['junction_id'] : [];

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');
        $quotaKey = $quotaConf[$params['quota_key']]['escolumn'];

        // 获取最近时间
        $dayTime = $params['date'] . ' ' . $params['time_point'];

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
                    "limit"      => $limit,
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
            $esRes = $this->searchQuota($esData);
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
                        "limit"      => $limit,
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

                $searchRes = $this->searchQuota($esData);
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
}
