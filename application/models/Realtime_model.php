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

    public function search($body,$sType=0,$isScroll=0)
    {
        $isScroll= 0;
        $hosts = $this->config->item('dmp_es_interface');
        $index = $this->config->item('dmp_es_index');
        $scrollInfo = "";
        if($isScroll){
            $scrollInfo = "scroll=5m";
        }
        if($sType){
            $queryUrl = sprintf('http://%s/_sql?%s',$hosts[0],$scrollInfo);
            $response = httpPOST($queryUrl, $body, 8000, 'raw');
        }else{
            $queryUrl = sprintf('http://%s/%s/_search?%s',$hosts[0],"new_dmp_forecast",$scrollInfo);
//            $response = httpPOST($queryUrl, json_decode($body,true), 0, 'json');
            $response = httpPOST($queryUrl,$body, 0, 'raw');
        }
        if (!$response) {
            return [];
        }
        $resPart = json_decode($response,true);
        return $resPart;
//        if(!$isScroll){
//            return $resPart;
//        }
//        $hits = $resPart["hits"]["hits"];
//        while(count($resPart["hits"]["hits"])>0){
//            $scrollID = $resPart["_scroll_id"];
//            $qBody = [
//                "scroll_id"=>$scrollID,
//                "scroll"=>"1m",
//            ];
//            $queryUrl = sprintf('http://%s/_search/scroll',$hosts[0]);
//            $response = httpPOST($queryUrl, $qBody, 0, 'json');
//            $resPart = json_decode($response,true);
//            $hits = array_merge($hits,$resPart["hits"]["hits"]);
//        }
//        $resPart["hits"]["hits"] = $hits;
//        return $resPart;
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

//        $baseUrl = $this->esUrl;
//        if(!empty($data["cityId"]) && in_array($data["cityId"],$this->quotaCityIds)){
//            $baseUrl = $this->newEsUrl;
//        }
        $baseUrl = $this->newEsUrl;
        $resData = [];
        $result = httpPOST($baseUrl , $data, 0, 'json');

        if (!$result) {
            throw new \Exception('调用es接口 queryIndices 失败！', ERR_DEFAULT);
        }
        $result = json_decode($result, true);


        $resData = $result['data']['result']['diagnosisIndices'];


//        if ($result['code'] != '000000' && $result['code'] != '400001') {
//            throw new \Exception($result['message'], ERR_DEFAULT);
//        }

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

//        $baseUrl = $this->esUrl;
//        if(!empty($data["cityId"]) && in_array($data["cityId"],$this->quotaCityIds)){
//
//        }
        $baseUrl = $this->newEsUrl;
//        $queryUrl = $baseUrl . '/estimate/diagnosis/queryQuota';

        $result = httpPOST($baseUrl, $data, 9000, 'json');
        if (!$result) {
            com_log_warning('searchQuota_result_invalid', 0, "", compact("queryUrl","data","result"));
            throw new \Exception('调用es接口 queryIndices 失败！', ERR_DEFAULT);
        }
        $result = json_decode($result, true);

//        if ($result['code'] != '000000' && $result['code'] != '400001') {
//            com_log_warning('searchQuota_result_errcode', 0, "", compact("queryUrl","data","result"));
//            throw new \Exception($result['message'], ERR_DEFAULT);
//        }
        return $result['data'];
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
        $sql = sprintf('SELECT * FROM new_dmp_forecast where city_id =%d and day_time_hms>"%s" order by day_time_hms desc limit 1',$cityId,date('Y-m-d 00:00:00'));
        $res = $this->search($sql,1,0);
        if(empty($res["hits"]["hits"][0]["_source"]["day_time_hms"])){
            return "00:00:00";
        }
        return date("H:i:s",strtotime($res["hits"]["hits"][0]["_source"]["day_time_hms"]));
//        $data = [
//            'source' => 'signal_control', // 调用方
//            'cityId' => $cityId,          // 城市ID
//            'requestId' => get_traceid(),    // trace id
//            'timestamp' => strtotime(date('Y-m-d')) * 1000, // 当天0点(yyyy-mm-dd 00:00:00)毫秒时间戳
//            'andOperations' => [
//                'cityId' => 'eq', // cityId相等
//                'timestamp' => 'gte' // 大于等于当天开始时间
//            ],
//            'quotaRequest' => [
//                "groupField" => 'dayTime',
//                "limit" => 1,
//                "orderField" => "max_timestamp",
//                "asc" => false,
//                "quotas" => "max_timestamp",
//            ],
//        ];
//        $res = $this->searchQuota($data);
//        if (empty($res['result']['quotaResults'][0]['quotaMap'])) {
//            return "00:00:00";
//            // throw new \Exception('获取实时数据最新批次hour失败！', ERR_DEFAULT);
//        }
//        $lastHour = date('H:i:s', strtotime($res['result']['quotaResults'][0]['quotaMap']['dayTime']));
//        return $lastHour;
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
        $juncsWithQuota = [];
        foreach($junctionIds as $junc){
            $juncsWithQuota[] = '"'.$junc.'"';
        }
        $juncSql = '';
        if(!empty($junctionIds)){
            $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
        }
//        $sql = sprintf('select sum(stop_delay_up * traj_num) as agg_0,sum(traj_num) as agg_1,day_time_hms from new_dmp_forecast* where city_id=%d and day_time_hms="%s" %s group by terms("alias"="day_time_hms","field"="day_time_hms","size"=10000) order by day_time_hms asc', $cityId, $date." ".$hour, $juncSql);
        // echo $sql;

        $dsl = sprintf("{\"from\":0,\"size\":0,\"query\":{\"bool\":{\"must\":[{\"match_phrase\":{\"city_id\":{\"query\":\"%s\"}}},{\"match_phrase\":{\"day_time_hms\":{\"query\":\"%s\"}}}]}},\"_source\":{\"includes\":[\"SUM\",\"SUM\",\"day_time_hms\"],\"excludes\":[]},\"stored_fields\":\"day_time_hms\",\"sort\":[{\"day_time_hms\":{\"order\":\"asc\"}}],\"aggregations\":{\"day_time_hms\":{\"terms\":{\"field\":\"day_time_hms\",\"size\":10000},\"aggregations\":{\"agg_0\":{\"sum\":{\"script\":{\"inline\":\"doc['stop_delay_up'].value * doc['traj_num'].value\"}}},\"agg_1\":{\"sum\":{\"field\":\"traj_num\"}}}}}}",$cityId, $date." ".$hour);
        $res = $this->search($dsl,0,0);
        if(empty($res["aggregations"]["day_time_hms"]["buckets"]["0"])){
            return [];
        }
        $bucket = $res["aggregations"]["day_time_hms"]["buckets"]["0"];
        $result = [
            'avg_stop_delay' => $bucket["agg_0"]["value"]/$bucket["agg_1"]["value"],
            'hour' => date('H:i:s', strtotime($bucket["key"])),
        ];
        return $result;

//        $data = [
//            "source" => "signal_control",
//            "cityId" => $cityId,
//            'requestId' => get_traceid(),
//            "dayTime" => $date . ' ' . $hour,
//            "andOperations" => [
//                "cityId" => "eq",
//                "dayTime" => "eq",
//            ],
//            "quotaRequest" => [
//                "quotaType" => "weight_avg",
//                "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
//                "groupField" => "dayTime",
//            ],
//        ];
//        if (!empty($junctionIds)) {
//            $data['junctionId'] = implode(",",$junctionIds);
//            $data["andOperations"]['junctionId'] = 'in';
//        }
//
//        $esRes = $this->searchQuota($data);
//        if (empty($esRes['result']['quotaResults'])) {
//            return [];
//        }
//        $result = [];
//        if(!empty($esRes['result']['quotaResults'])){
//            foreach ($esRes['result']['quotaResults'] as $k => $v) {
//                $result = [
//                    'avg_stop_delay' => $v['quotaMap']['weight_avg'],
//                    'hour' => date('H:i:s', strtotime($v['quotaMap']['dayTime'])),
//                ];
//            }
//        }
//        return $result;
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
        $sumAgg0 = 0;
        $sumAgg1 = 0;
        foreach ($chunkJunctionIds as $partJuncIds){
            $juncsWithQuota = [];
            foreach($partJuncIds as $junc){
                $juncsWithQuota[] = '"'.$junc.'"';
            }
            $juncSql = '';
            if(!empty($juncsWithQuota)){
                $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
            }
            $dsl = sprintf("{\"from\":0,\"size\":0,\"query\":{\"bool\":{\"must\":[{\"match_phrase\":{\"city_id\":{\"query\":\"%s\"}}},{\"match_phrase\":{\"day_time_hms\":{\"query\":\"%s\"}}},{\"terms\":{\"logic_junction_id\":[\"%s\"]}}]}},\"_source\":{\"includes\":[\"SUM\",\"SUM\",\"day_time_hms\"],\"excludes\":[]},\"stored_fields\":\"day_time_hms\",\"sort\":[{\"day_time_hms\":{\"order\":\"asc\"}}],\"aggregations\":{\"day_time_hms\":{\"terms\":{\"field\":\"day_time_hms\",\"size\":10000},\"aggregations\":{\"agg_0\":{\"sum\":{\"script\":{\"inline\":\"doc['stop_delay_up'].value * doc['traj_num'].value\"}}},\"agg_1\":{\"sum\":{\"field\":\"traj_num\"}}}}}}",$cityId, $date." ".$hour, implode(",",$juncsWithQuota));
            $res = $this->search($dsl,0,0);
//            $sql = sprintf('select sum(stop_delay_up * traj_num) as agg_0,sum(traj_num) as agg_1,day_time_hms from new_dmp_forecast* where city_id=%d and day_time_hms="%s" %s group by terms("alias"="day_time_hms","field"="day_time_hms","size"=10000) order by day_time_hms asc', $cityId, $date." ".$hour, $juncSql);
//            // echo $sql;
//            $res = $this->search($sql,1,0);
            if(empty($res["aggregations"]["day_time_hms"]["buckets"]["0"])){
                //一次查询失败不影响其他批次查询
                // return [];
            }
            $bucket = $res["aggregations"]["day_time_hms"]["buckets"]["0"];
            $sumAgg0+=$bucket["agg_0"]["value"];
            $sumAgg1+=$bucket["agg_1"]["value"];
        }
        $result = [
            'avg_stop_delay' => $sumAgg0/$sumAgg1,
            'hour' => $hour,
        ];
        return $result;

//        $chunkJunctionIds = array_chunk($junctionIds,500);
//        $tmpRs = [];
//        foreach ($chunkJunctionIds as $ids){
//            $data = [
//                "source" => "signal_control",
//                "cityId" => $cityId,
//                'requestId' => get_traceid(),
//                "dayTime" => $date . ' ' . $hour,
//                "andOperations" => [
//                    "cityId" => "eq",
//                    "dayTime" => "eq",
//                ],
//                "quotaRequest" => [
//                    "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
//                    "groupField" => "dayTime",
//                ],
//            ];
//            $data['junctionId'] = implode(",",$ids);
//            $data["andOperations"]['junctionId'] = 'in';
//            if(count($ids)==0){
//                return [];
//            }
//            $esRes = $this->searchQuota($data);
//            if (empty($esRes['result']['quotaResults'])) {
//                //return [];
//            }
//            if(!empty($esRes['result']['quotaResults'])){
//                foreach ($esRes['result']['quotaResults'] as $k => $v) {
//                    $hour=date('H:i:s', strtotime($v['quotaMap']['dayTime']));
//                    if(!empty($tmpRs[$hour])){
//                        $tmpRs[$hour]['sum_stopDelayUp*trailNum']+=$tmpRs[$hour]['sum_stopDelayUp*trailNum'];
//                        $tmpRs[$hour]['sum_trailNum']+=$tmpRs[$hour]['sum_trailNum'];
//                    }else{
//                        $tmpRs[$hour] = [
//                            'sum_stopDelayUp*trailNum' => $v['quotaMap']['sum_stopDelayUp*trailNum'],
//                            'sum_trailNum' => $v['quotaMap']['sum_trailNum'],
//                        ];
//                    }
//                }
//            }
//        }
//
//        $result = [];
//        foreach ($tmpRs as $hour=>$item){
//            $result = [
//                'avg_stop_delay' => $item['sum_stopDelayUp*trailNum']/$item['sum_trailNum'],
//                'hour' => $hour,
//            ];
//        }
//        return $result;
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
        $juncsWithQuota = [];
        $junctionIds = explode(",",$junctionIds);
        foreach($junctionIds as $junc){
            $juncsWithQuota[] = '"'.$junc.'"';
        }
        $juncSql = '';
        if(!empty($juncsWithQuota)){
            $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
        }
//        $sql = sprintf('select sum(%s * traj_num) as agg_0,sum(traj_num) as agg_1,day_time_hms from new_dmp_forecast* where city_id=%d and day_time_hms="%s" %s group by terms("alias"="day_time_hms","field"="day_time_hms","size"=10000) order by day_time_hms asc', $quotaKey, $cityId, $dayTime, $juncSql);
//        // echo $sql;
//        $res = $this->search($sql,1,0);
        $dsl = sprintf("{\"from\":0,\"size\":0,\"query\":{\"bool\":{\"must\":[{\"match_phrase\":{\"city_id\":{\"query\":\"%s\"}}},{\"match_phrase\":{\"day_time_hms\":{\"query\":\"%s\"}}},{\"terms\":{\"logic_junction_id\":[\"%s\"]}}]}},\"_source\":{\"includes\":[\"SUM\",\"SUM\",\"day_time_hms\"],\"excludes\":[]},\"stored_fields\":\"day_time_hms\",\"sort\":[{\"day_time_hms\":{\"order\":\"asc\"}}],\"aggregations\":{\"day_time_hms\":{\"terms\":{\"field\":\"day_time_hms\",\"size\":10000},\"aggregations\":{\"agg_0\":{\"sum\":{\"script\":{\"inline\":\"doc['%s'].value * doc['traj_num'].value\"}}},\"agg_1\":{\"sum\":{\"field\":\"traj_num\"}}}}}}",$quotaKey,$cityId, $dayTime, implode(",",$juncsWithQuota));
        $res = $this->search($dsl,0,0);
        if(empty($res["aggregations"]["day_time_hms"]["buckets"]["0"])){
            return [];
        }
        $bucket = $res["aggregations"]["day_time_hms"]["buckets"]["0"];
        $result = [
            date('H:i:s', strtotime($dayTime)) => [
                'avg_stop_delay' => $bucket["agg_0"]["value"]/$bucket["agg_1"]["value"],
                'hour' => date('H:i:s', strtotime($bucket["key"])),
            ]
        ];
        return $result;

//        $esData = [
//            'source' => 'signal_control',
//            'cityId' => $cityId,
//            'junctionId' => $junctionIds,
//            'dayTime' => $dayTime,
//            'requestId' => get_traceid(),
//            'andOperations' => [
//                'junctionId' => 'in',
//                'cityId' => 'eq',
//                'dayTime' => 'eq',
//            ],
//            'quotaRequest' => [
//                "groupField" => "dayTime",
//                "quotaType" => "weight_avg",
//                "quotas" => "sum_{$quotaKey}*trailNum, sum_trailNum",
//            ],
//        ];
//        $res = $this->searchQuota($esData);
//        if (!empty($res['result']['quotaResults'])) {
//            list($quotaValueInfo) = $res['result']['quotaResults'];
//        }
//
//        return [
//            date('H:i:s', strtotime($dayTime)) => [
//                'value' => $quotaValueInfo['quotaMap']['weight_avg'] ?? 0,
//                'hour' => date('H:i:s', strtotime($quotaValueInfo['quotaMap']['dayTime'] ?? "")),
//            ]
//        ];
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
        $juncsWithQuota = [];
        $junctionIds = explode(",",$junctionIds);
        foreach($junctionIds as $junc){
            $juncsWithQuota[] = '"'.$junc.'"';
        }
        $juncSql = '';
        if(!empty($juncsWithQuota)){
            $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
        }
        // 因为一次性获取全天的数据会影响集群性能，间隔3小时实时数据采样
        $result = [];
        $nowHour = date('H') + 1;
        for ($i = 0; $i < $nowHour; $i += 3) {
            $sTime = strtotime($i . ':00');
            $eTime = strtotime(($i + 3) . ':00');
            if ($i == 21) {
                $eTime = strtotime('23:59:59');
            }
//            $sql = sprintf('select sum(%s * traj_num) as agg_0,sum(traj_num) as agg_1,day_time_hms from new_dmp_forecast* where city_id="%s" and (day_time_hms >= "%s" and day_time_hms <= "%s") %s group by terms("alias"="day_time_hms","field"="day_time_hms","size"=10000) order by day_time_hms asc', $quotaKey, $cityId, date("Y-m-d H:i:s", $sTime), date("Y-m-d H:i:s", $eTime), $juncSql);
//            // echo $sql;
//            $res = $this->search($sql,1,0);
            $dsl = sprintf("{\"from\":0,\"size\":0,\"query\":{\"bool\":{\"must\":[{\"match_phrase\":{\"city_id\":{\"query\":\"%s\"}}},{\"range\":{\"day_time_hms\":{\"from\":\"%s\",\"to\":\"%s\",\"include_lower\":true,\"include_upper\":true,}}},{\"terms\":{\"logic_junction_id\":[\"%s\"]}}]}},\"_source\":{\"includes\":[\"SUM\",\"SUM\",\"day_time_hms\"],\"excludes\":[]},\"stored_fields\":\"day_time_hms\",\"sort\":[{\"day_time_hms\":{\"order\":\"asc\"}}],\"aggregations\":{\"day_time_hms\":{\"terms\":{\"field\":\"day_time_hms\",\"size\":10000},\"aggregations\":{\"agg_0\":{\"sum\":{\"script\":{\"inline\":\"doc['%s'].value * doc['traj_num'].value\"}}},\"agg_1\":{\"sum\":{\"field\":\"traj_num\"}}}}}}",$quotaKey,$cityId, date("Y-m-d H:i:s", $sTime),date("Y-m-d H:i:s", $eTime), implode(",",$juncsWithQuota));
            $res = $this->search($dsl,0,0);
            if(empty($res["aggregations"]["day_time_hms"]["buckets"])){
                return [];
            }
            foreach($res["aggregations"]["day_time_hms"]["buckets"] as $bucket){
                $result[strtotime($bucket["key"])] = [
                    'value' => $bucket["agg_0"]["value"]/$bucket["agg_1"]["value"],
                    'hour' => date('H:i:s', strtotime($bucket["key"])),
                ];
            }
            //数据排序
            ksort($result);
        }
        if (!empty($result)) {
            $result = array_values($result);
        }
        return $result;
//        $esData = [
//            'source' => 'signal_control',
//            'cityId' => $cityId,
//            'junctionId' => $junctionIds,
//            'requestId' => get_traceid(),
//            'andOperations' => [
//                'junctionId' => 'in',
//                'cityId' => 'eq',
//                'timestamp' => 'range',
//            ],
//            'quotaRequest' => [
//                "groupField" => "dayTime",
//                "quotaType" => "weight_avg",
//                "quotas" => "sum_{$quotaKey}*trailNum, sum_trailNum",
//                "orderField" => 'dayTime',
//                "asc" => true,
//            ],
//        ];
//
//        $result = [];
//        // 因为一次性获取全天的数据会影响集群性能，会被禁止，所有要分断进行获取 y m d h i s
//        $nowHour = date('H') + 1;
//        for ($i = 0; $i < $nowHour; $i += 3) {
//            $sTime = strtotime($i . ':00') * 1000;
//            $eTime = strtotime(($i + 3) . ':00') * 1000;
//            if ($i == 21) {
//                $eTime = strtotime('23:59:59') * 1000;
//            }
//            $esData['timestamp'] = "[{$sTime}, {$eTime}]";
//            $res = $this->searchQuota($esData);
//            if (!empty($res['result']['quotaResults'])) {
//                foreach ($res['result']['quotaResults'] as $k => $v) {
//                    $hour = date('H:i:s', strtotime($v['quotaMap']['dayTime']));
//                    $result[$hour] = [
//                        'value' => $v['quotaMap']['weight_avg'],
//                        'hour' => $hour,
//                    ];
//                }
//            }
//        }
//        if (!empty($result)) {
//            $result = array_values($result);
//        }
//        return $result;
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
        $juncsWithQuota = [];
        foreach($junctionIds as $junc){
            $juncsWithQuota[] = '"'.$junc.'"';
        }
        $juncSql = '';
        if(!empty($juncsWithQuota)){
            $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
        }
        $sql = sprintf('select * from new_dmp_forecast* where traj_num>=%d and day_time_hms="%s" and city_id="%d" %s order by day_time_hms asc limit 5000', $trajNum, $date." ".$hour, $cityId, $juncSql);
        $res = $this->search($sql,1,1);
        $hitList=[];
        foreach($res["hits"]["hits"] as $hitIndex=>$source){
            foreach($source["_source"] as $column=>$columnVal){
                $hitList[$hitIndex][camelize($column)] = $columnVal;
                if($column=="logic_flow_id"){
                    $hitList[$hitIndex]["movementId"] = $columnVal;
                }
                if($column=="logic_junction_id"){
                    $hitList[$hitIndex]["junctionId"] = $columnVal;
                }
                if($column=="day_time_hms"){
                    $hitList[$hitIndex]["dayTime"] = $columnVal;
                }
                if($column=="traj_num"){
                    $hitList[$hitIndex]["trailNum"] = $columnVal;
                }
            }
        }
        return $hitList;
//
//        if($cityId==175){
//            $trajNum = 1;
//        }
//        $data = [
//            'source' => 'signal_control', // 调用方
//            'cityId' => $cityId,          // 城市ID
//            'requestId' => get_traceid(),    // trace id
//            'trailNum' => $trajNum,
//            'dayTime' => $date . " " . $hour,
//            'andOperations' => [
//                'cityId' => 'eq',  // cityId相等
//                'trailNum' => 'gte', // 轨迹数大于等于5
//                'dayTime' => 'eq',  // 等于hour
//            ],
//            'limit' => 5000,
//        ];
//        if (!empty($junctionIds)) {
//            $data['junctionId'] = implode(",",$junctionIds);
//            $data["andOperations"]['junctionId'] = 'in';
//        }
//        $realTimeEsData = $this->searchDetail($data);
//        return $realTimeEsData;
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
        $juncsWithQuota = [];
        foreach($junctionIds as $junc){
            $juncsWithQuota[] = '"'.$junc.'"';
        }
        $juncSql = '';
        if(!empty($juncsWithQuota)){
            $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
        }
        $sql = sprintf('select * from new_dmp_forecast* where traj_num>=%d and day_time_hms="%s" and city_id="%d" %s order by timestamp desc limit 5000', $trajNum, $date." ".$hour, $cityId, $juncSql);
        $res = $this->search($sql,1,1);
        $hitList=[];
        foreach($res["hits"]["hits"] as $hitIndex=>$source){
            foreach($source["_source"] as $column=>$columnVal){
                $hitList[$hitIndex][camelize($column)] = $columnVal;
                if($column=="logic_flow_id"){
                    $hitList[$hitIndex]["movementId"] = $columnVal;
                }
                if($column=="logic_junction_id"){
                    $hitList[$hitIndex]["junctionId"] = $columnVal;
                }
                if($column=="day_time_hms"){
                    $hitList[$hitIndex]["dayTime"] = $columnVal;
                }
                if($column=="traj_num"){
                    $hitList[$hitIndex]["trailNum"] = $columnVal;
                }
            }
        }
        $result = [];
        foreach ($hitList as $k => $v) {
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
//        $trajNum = 5;
//        if($cityId==175){
//            $trajNum = 1;
//        }
//        $data = [
//            'source' => 'signal_control', // 调用方
//            'cityId' => $cityId,          // 城市ID
//            'requestId' => get_traceid(),    // trace id
//            'trailNum' => $trajNum,
//            'dayTime' => $date . " " . $hour,
//            'andOperations' => [
//                'cityId' => 'eq',  // cityId相等
//                'trailNum' => 'gte', // 轨迹数大于等于5
//                'dayTime' => 'eq',  // 等于hour
//            ],
//            'orderOperations'=>[
//                [
//                    'orderField'=>'trailNum',
//                    'orderType'=>"DESC",
//                ],
////                    "orderField": "trailNum",
////        "orderType": "DESC"
////    }
//
//            ],
//
////            'quotaRequest'=>[
////                'groupField'=>'junctionId',
////                'quotas' =>'sum_stopDelayUp*trailNum, sum_trailNum'
////            ],
//            'limit' => 10000,
//        ];
//        if (!empty($junctionIds)) {
//            $data['junctionId'] = implode(",",$junctionIds);
//            $data["andOperations"]['junctionId'] = 'in';
//        }
//        $realTimeEsData = $this->searchDetail($data);
//        $result = [];
//        foreach ($realTimeEsData as $k => $v) {
//            $result[$k] = [
//                'logic_junction_id' => $v['junctionId'],
//                'hour' => date('H:i:s', strtotime($v['dayTime'])),
//                'logic_flow_id' => $v['movementId'],
//                'stop_time_cycle' => $v['avgStopNumUp'],
//                'spillover_rate' => $v['spilloverRateDown'],
//                'queue_length' => $v['queueLengthUp'],
//                'stop_delay' => $v['stopDelayUp'],
//                'stop_rate' => ($v['oneStopRatioUp'] + $v['multiStopRatioUp']),
//                'twice_stop_rate' => $v['multiStopRatioUp'],
//                'speed' => $v['avgSpeedUp'],
//                'free_flow_speed' => $v['freeFlowSpeedUp'],
//                'traj_count' => $v['trajNum'],
//            ];
//        }
//
//        return $result;
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
        $juncSql = '';
        if(!empty($logicJunctionId)){
            $juncSql = ' and logic_junction_id = "'.$logicJunctionId.'" ';
        }
        $flowsWithQuota = [];
        foreach($logicFlowId as $flow){
            $flowsWithQuota[] = '"'.$flow.'"';
        }
        if(!empty($logicFlowIds)){
            $flowSql = ' and logic_flow_id in  ('.implode(",",$flowsWithQuota).') ';
        }
        $sql = sprintf('select * from new_dmp_forecast* where day_time_hms="%s" and city_id="%d" %s %s order by timestamp desc limit 5000', $date." ".$hour, $cityId, $juncSql, $flowSql);
        // echo $sql;
        $res = $this->search($sql,1,1);
        $hitList=[];
        foreach($res["hits"]["hits"] as $hitIndex=>$source){
            foreach($source["_source"] as $column=>$columnVal){
                $hitList[$hitIndex][camelize($column)] = $columnVal;
                if($column=="logic_flow_id"){
                    $hitList[$hitIndex]["movementId"] = $columnVal;
                }
                if($column=="logic_junction_id"){
                    $hitList[$hitIndex]["junctionId"] = $columnVal;
                }
                if($column=="day_time_hms"){
                    $hitList[$hitIndex]["dayTime"] = $columnVal;
                }
                if($column=="traj_num"){
                    $hitList[$hitIndex]["trailNum"] = $columnVal;
                }
            }
        }
        return $hitList;
//        $date = date('Y-m-d');
//
//        $flowIds = implode(',', $logicFlowId);
//
//        $data = [
//            'source' => 'signal_control', // 调用方
//            'cityId' => $cityId,          // 城市ID
//            'requestId' => get_traceid(),    // trace id
//            'junctionId' => $logicJunctionId,
//            'dayTime' => $date . " " . $hour,
//            'movementId' => "{$flowIds}",
//            'andOperations' => [
//                'cityId' => 'eq',
//                'junctionId' => 'eq',
//                'dayTime' => 'eq',
//                'movementId' => 'in',
//            ],
//            'limit' => 5000,
//        ];
//        $realTimeEsData = $this->searchDetail($data);
//
//        return $realTimeEsData;
    }

    public function getJunctionQuotaCurve($params){
        if(!isset($params['date'])){
            throw new Exception("参数 date 未传递", 1);
        }
        if(!isset($params['quota_key'])){
            throw new Exception("参数 quota_key 未传递", 1);
        }
        if(!isset($params['junction_id'])){
            throw new Exception("参数 junction_id 未传递", 1);
        }
        if(!isset($params['city_id'])){
            throw new Exception("参数 city_id 未传递", 1);
        }

        $dayTime = $params['date'] . ' 00:00:00';
        $quotaKey = $params['quota_key'];
        $junctionID = $params['junction_id'];
        $cityID = $params['city_id'];

        //旧指标转换新指标
        if($params['quota_key']=="stop_time_cycle"){
            $params['quota_key'] = "avg_stop_num_up";
        }
        if($params['quota_key']=="spillover_rate"){
            $params['quota_key'] = "spillover_rate_down";
        }
        if($params['quota_key']=="queue_length"){
            $params['quota_key'] = "queue_length_up";
        }
        if($params['quota_key']=="stop_delay"){
            $params['quota_key'] = "stop_delay_up";
        }
        if($params['quota_key']=="speed"){
            $params['quota_key'] = "avg_speed_up";
        }
        if($params['quota_key']=="free_flow_speed"){
            $params['quota_key'] = "free_flow_speed_up";
        }
        if($params['quota_key']=="twice_stop_rate"){
            $params['quota_key'] = "multi_stop_ratio_up";
        }
        $sumKeys=["stop_delay", "stop_delay_up", "avg_speed_up", "one_stop_ratio_up", "traffic_jam_index_up", "travel_time_up"];
        $maxKeys=["spillover_rate_up"];
        $juncSql = '';
        if(!empty($junctionID)){
            $juncSql = ' and logic_junction_id = "'.$junctionID.'" ';
        }


        $result = [];
        if (in_array($params['quota_key'],$sumKeys)) {
//            $sql = sprintf('select sum(%s * traj_num) as agg_0,sum(traj_num) as agg_1,day_time_hms from new_dmp_forecast* where city_id="%s" and day_time_hms >= "%s" %s group by terms("alias"="day_time_hms","field"="day_time_hms","size"=10000) order by day_time_hms asc', $quotaKey, $cityID, $dayTime, $juncSql);
//            $res = $this->search($sql,1,0);
            $dsl = sprintf("{\"from\":0,\"size\":0,\"query\":{\"bool\":{\"must\":[{\"match_phrase\":{\"city_id\":{\"query\":\"%s\"}}},{\"range\":{\"day_time_hms\":{\"from\":\"%s\",\"to\":null,\"include_lower\":false,\"include_upper\":true,}}},{\"terms\":{\"logic_junction_id\":[\"%s\"]}}]}},\"_source\":{\"includes\":[\"SUM\",\"SUM\",\"day_time_hms\"],\"excludes\":[]},\"stored_fields\":\"day_time_hms\",\"sort\":[{\"day_time_hms\":{\"order\":\"asc\"}}],\"aggregations\":{\"day_time_hms\":{\"terms\":{\"field\":\"day_time_hms\",\"size\":10000},\"aggregations\":{\"agg_0\":{\"sum\":{\"script\":{\"inline\":\"doc['%s'].value * doc['traj_num'].value\"}}},\"agg_1\":{\"sum\":{\"field\":\"traj_num\"}}}}}}",$quotaKey,$params['city_id'], $dayTime,$junctionID);
            $res = $this->search($dsl,0,0);
            if(empty($res["aggregations"]["day_time_hms"]["buckets"])){
                return [];
            }
            foreach($res["aggregations"]["day_time_hms"]["buckets"] as $bucket){
                $result[strtotime($bucket["key"])] = [
                    "quotaMap"=>[
                        'weight_avg' => $bucket["agg_0"]["value"]/$bucket["agg_1"]["value"],
                        'dayTime' => date('Y-m-h H:i:s', strtotime($bucket["key"])),
                    ]
                ];
            }
            ksort($result);
        }elseif(in_array($params['quota_key'],$maxKeys)) {
            $sql = sprintf('select max(%s) as agg_0, day_time_hms from new_dmp_forecast* where city_id="%s" and day_time_hms >= "%s" %s group by terms("alias"="day_time_hms","field"="day_time_hms","size"=10000) order by day_time_hms asc', $quotaKey, $cityID, $dayTime, $juncSql);
            // echo $sql;
            $res = $this->search($sql,1,0);
            if(empty($res["aggregations"]["day_time_hms"]["buckets"])){
                return [];
            }
            foreach($res["aggregations"]["day_time_hms"]["buckets"] as $bucket){
                $result[strtotime($bucket["key"])] = [
                    "quotaMap"=>[
                        'max_'.camelize($params['quota_key'])=> $bucket["agg_0"]["value"],
                        'weight_avg' => $bucket["agg_0"]["value"],
                        'dayTime' => date('Y-m-d H:i:s', strtotime($bucket["key"])),
                    ]
                ];
            }
            ksort($result);
        }else{
            $sql = sprintf('select avg(%s) as agg_0, day_time_hms from new_dmp_forecast* where city_id="%s" and day_time_hms >= "%s" %s group by terms("alias"="day_time_hms","field"="day_time_hms","size"=10000) order by day_time_hms asc', $quotaKey, $cityID, $dayTime, $juncSql);
            // echo $sql;
            $res = $this->search($sql,1,0);
            if(empty($res["aggregations"]["day_time_hms"]["buckets"])){
                return [];
            }
            foreach($res["aggregations"]["day_time_hms"]["buckets"] as $bucket){
                $result[strtotime($bucket["key"])] = [
                    "quotaMap"=>[
                        'avg_'.camelize($params['quota_key'])=> $bucket["agg_0"]["value"],
                        'weight_avg' => $bucket["agg_0"]["value"],
                        'dayTime' => date('Y-m-d H:i:s', strtotime($bucket["key"])),
                    ]
                ];
            }
            ksort($result);
        }
        $outRet["result"]["quotaResults"] = array_values($result);
        return $outRet;
//        $timestamp = strtotime($params['date'] . ' 00:00:00') * 1000;
//        $dayTime = $params['date'] . ' 00:00:00';
//        $quotaKey = camelize($params['quota_key']);
//        $data = [
//            'source' => 'signal_control',   // 调用方
//            'cityId' => $params['city_id'], // 城市ID
//            'requestId' => get_traceid(),      // trace id
//            'timestamp' => $timestamp,
//            'dayTime'=> $dayTime,
//            'junctionId' => $params['junction_id'],
//            'trailNum' => 0,
//            'andOperations' => [
//                'cityId' => 'eq',  // cityId相等
//                'timestamp' => 'gte', // 大于等于当天开始时间
//                'dayTime' => 'gte', // 大于等于当天开始时间
//                'junctionId' => 'eq',
//                'trailNum' => 'gte',
//            ],
//            "quotaRequest" => [
//                // 'quotaType' => 'weight_avg',
//                // 'quotas' => 'sum_'.$quotaKey.'*trailNum,sum_trailNum',
//                'groupField' => 'dayTime',
//                // 'orderField' => 'dayTime',
//                'asc' => 'true',
//            ],
//        ];
//
//        //特殊设置
//        $quotaValueKey = "weight_avg";
//        if (in_array($params['quota_key'],["stop_delay","avg_speed_up","one_stop_ratio_up","traffic_jam_index_up","travel_time_up"])) {
//            $data['quotaRequest']['quotas'] = 'sum_' . $quotaKey . '*trailNum, sum_trailNum';
//            $data['quotaRequest']['quotaType'] = "weight_avg";
//            $quotaKey = 'weight_avg';
//        } elseif(in_array($params['quota_key'],["spillover_rate_up"])) {
//            //无法排序
//            $data['quotaRequest']['quotas'] = 'max_' . $quotaKey;
//            // $data['quotaRequest']['quotaType'] = "max";
//            $quotaKey = 'max_' . $quotaKey;
//        } else {
//            //无法排序
//            $data['quotaRequest']['quotas'] = 'avg_' . $quotaKey;
//            // $data['quotaRequest']['quotaType'] = "avg";
//            $quotaKey = 'avg_' . $quotaKey;
//        }
//        $realTimeEsData = $this->searchQuota($data);
//        if(empty($realTimeEsData["result"]["quotaResults"])){
//            return $realTimeEsData;
//        }
//
//        //特殊排序
//        $keys = [];
//        foreach ($realTimeEsData["result"]["quotaResults"] as $key => $value) {
//            $keys[$key] = strtotime($value["quotaMap"]["dayTime"]);
//        }
//        array_multisort($keys, SORT_NUMERIC, SORT_ASC, $realTimeEsData["result"]["quotaResults"]);
//        // print_r($realTimeEsData);exit;
//        foreach ($realTimeEsData["result"]["quotaResults"] as $key => $value) {
//            // print_r($value);exit;
//            $value["quotaMap"]["weight_avg"] = $value["quotaMap"][$quotaKey];
//            $realTimeEsData["result"]["quotaResults"][$key] = $value;
//        }
//        return $realTimeEsData;
    }

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
        if(!isset($params['date'])){
            throw new Exception("参数 date 未传递", 1);
        }
        if(!isset($params['city_id'])){
            throw new Exception("参数 city_id 未传递", 1);
        }
        if(!isset($params['junction_id'])){
            throw new Exception("参数 junction_id 未传递", 1);
        }
        if(!isset($params['flow_id'])){
            throw new Exception("参数 flow_id 未传递", 1);
        }
        $date = $params["date"];
        $cityID = $params["city_id"];
        $junctionId = $params["junction_id"];
        $flowId = $params["flow_id"];
        $juncSql = '';
        if(!empty($junctionId)){
            $juncSql = ' and logic_junction_id = "'.$junctionId.'" ';
        }
        if(!empty($flowId)){
            $flowSql = ' and logic_flow_id = "'.$flowId.'" ';
        }
        $sql = sprintf('select * from new_dmp_forecast* where day_time_hms>="%s" and city_id="%d" %s %s order by timestamp asc limit 5000', $date." "."00:00:00", $cityID, $juncSql, $flowSql);

        $redis_key = 'getQuotaByFlowId_' . md5($sql);
        $result = $cached ? $this->redis_model->getData($redis_key) : [];
        if (!$result) {
            //查询逻辑开始==>
            $res = $this->search($sql,1,1);
            $hitList=[];
            foreach($res["hits"]["hits"] as $hitIndex=>$source){
                foreach($source["_source"] as $column=>$columnVal){
                    $hitList[$hitIndex][camelize($column)] = $columnVal;
                    if($column=="logic_flow_id"){
                        $hitList[$hitIndex]["movementId"] = $columnVal;
                    }
                    if($column=="logic_junction_id"){
                        $hitList[$hitIndex]["junctionId"] = $columnVal;
                    }
                    if($column=="day_time_hms"){
                        $hitList[$hitIndex]["dayTime"] = $columnVal;
                    }
                    if($column=="traj_num"){
                        $hitList[$hitIndex]["trailNum"] = $columnVal;
                    }
                }
            }
            //<====查询逻辑结束

            if($cached){
                $this->redis_model->setEx($redis_key, json_encode($hitList), 120);
            }
            return $hitList;
        }
        return json_decode($result, true);
//        $timestamp = strtotime($params['date'] . ' 00:00:00') * 1000;
//        $data = [
//            'source' => 'signal_control',   // 调用方
//            'cityId' => $params['city_id'], // 城市ID
//            'requestId' => get_traceid(),      // trace id
//            'timestamp' => $timestamp,
//            'junctionId' => $params['junction_id'],
//            'movementId' => $params['flow_id'],
//            'andOperations' => [
//                'cityId' => 'eq',  // cityId相等
//                'timestamp' => 'gte', // 大于等于当天开始时间
//                'junctionId' => 'eq',
//                'movementId' => 'eq',
//            ],
//            'limit' => 5000,
//            "orderOperations" => [
//                [
//                    'orderField' => 'dayTime',
//                    'orderType' => 'ASC',
//                ],
//            ],
//        ];
//
//        $redis_key = 'getQuotaByFlowId_' . md5(json_encode($data));
//        $result = $cached ? $this->redis_model->getData($redis_key) : [];
//        if (!$result) {
//            $realTimeEsData = $this->searchDetail($data);
//            if($cached){
//                $this->redis_model->setEx($redis_key, json_encode($realTimeEsData), 120);
//            }
//            return $realTimeEsData;
//        }
//        return json_decode($result, true);
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
        $dayTime = $date. ' ' .$hour;
        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }
        $chunkJunctionIds=array_chunk($junctionIds,1000);
        foreach ($chunkJunctionIds as $Jids){
            $juncsWithQuota = [];
            foreach($Jids as $junc){
                $juncsWithQuota[] = '"'.$junc.'"';
            }
            $juncSql = '';
            if(!empty($juncsWithQuota)){
                $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
            }
//            $sql = sprintf('select sum(stop_delay_up * traj_num) as agg_0,sum(traj_num) as agg_1,day_time_hms from new_dmp_forecast* where city_id=%s and day_time_hms = "%s" and traj_num >= %s %s group by terms("alias"="logic_junction_id","field"="logic_junction_id","size"=10000) order by day_time_hms asc', $cityId, $dayTime, $trajNum, $juncSql);
//            // echo $sql;
//            $res = $this->search($sql,1,0);
            $dsl = sprintf("{\"from\":0,\"size\":0,\"query\":{\"bool\":{\"must\":[{\"match_phrase\":{\"city_id\":{\"query\":\"%s\"}}},{\"match_phrase\":{\"day_time_hms\":{\"query\":\"%s\"}}},{\"terms\":{\"logic_junction_id\":[\"%s\"]}}]}},\"_source\":{\"includes\":[\"SUM\",\"SUM\",\"day_time_hms\"],\"excludes\":[]},\"stored_fields\":\"logic_junction_id\",\"sort\":[{\"day_time_hms\":{\"order\":\"asc\"}}],\"aggregations\":{\"logic_junction_id\":{\"terms\":{\"field\":\"logic_junction_id\",\"size\":10000},\"aggregations\":{\"agg_0\":{\"sum\":{\"script\":{\"inline\":\"doc['stop_delay_up'].value * doc['traj_num'].value\"}}},\"agg_1\":{\"sum\":{\"field\":\"traj_num\"}}}}}}",$cityId, $date." ".$hour, implode(",",$juncsWithQuota));
            $res = $this->search($dsl,0,0);
            if(empty($res["aggregations"]["logic_junction_id"]["buckets"])){
                return [];
            }
            foreach($res["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
                $result[$bucket["key"]] = [
                    "quotaMap"=>[
                        'weight_avg' => $bucket["agg_0"]["value"]/$bucket["agg_1"]["value"],
                        'junctionId' => $bucket["key"],
                    ]
                ];
            }
        }
        uasort($result,function ($a,$b) {
            $aValue = !empty($a["quotaMap"]["weight_avg"]) ? $a["quotaMap"]["weight_avg"] : 0;
            $bValue = !empty($b["quotaMap"]["weight_avg"]) ? $b["quotaMap"]["weight_avg"] : 0;
            if ($aValue==$bValue) return 0;
            return ($aValue<$bValue)?1:-1;
        });
        $result = array_values($result);
        return $result;
//        $dayTime = $date . ' ' . $hour;
//
//        $trajNum = 5;
//        if($cityId==175){
//            $trajNum = 1;
//        }
//
//        $tmpRs = [];
//        $chunkJunctionIds=array_chunk($junctionIds,1000);
//        foreach ($chunkJunctionIds as $Jids){
//            $data = [
//                "source" => "signal_control",
//                "cityId" => $cityId,
//                'requestId' => get_traceid(),
//                "dayTime" => $dayTime,
//                "trailNum" => $trajNum,
//                "andOperations" => [
//                    "cityId" => "eq",
//                    "dayTime" => "eq",
//                    "trailNum" => 'gte',
//                ],
//                "quotaRequest" => [
//                    "quotaType" => "weight_avg",
//                    "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
//                    "groupField" => "junctionId",
//                    "orderField" => "weight_avg",
//                    "asc" => "false",
//                    "limit" => $pagesize,
//                ],
//            ];
//            if (!empty($Jids)) {
//                $data['junctionId'] = implode(",",$Jids);
//                $data["andOperations"]['junctionId'] = 'in';
//            }
//
//            $esRes = $this->searchQuota($data);
//            if (!empty($esRes['result']['quotaResults'])) {
//                $tmpRs = array_merge($tmpRs,$esRes['result']['quotaResults']);
//            }
//        }
//
//        uasort($tmpRs,function ($a,$b) {
//            $aValue = !empty($a["quotaMap"]["weight_avg"]) ? $a["quotaMap"]["weight_avg"] : 0;
//            $bValue = !empty($b["quotaMap"]["weight_avg"]) ? $b["quotaMap"]["weight_avg"] : 0;
//            if ($aValue==$bValue) return 0;
//            return ($aValue<$bValue)?1:-1;
//        });
//        return $tmpRs;
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
        $dayTime = $date. ' ' .$hour;
        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }
//        $sql = sprintf('select sum(stop_delay_up * traj_num) as agg_0,sum(traj_num) as agg_1,day_time_hms from new_dmp_forecast* where city_id=%s and day_time_hms = "%s" and traj_num >= %s group by terms("alias"="logic_junction_id","field"="logic_junction_id","size"=10000) order by day_time_hms asc', $cityId, $dayTime, $trajNum);
//        // echo $sql;
//        $res = $this->search($sql,1,0);
        $dsl = sprintf("{\"from\":0,\"size\":0,\"query\":{\"bool\":{\"must\":[{\"match_phrase\":{\"city_id\":{\"query\":\"%s\"}}},{\"match_phrase\":{\"day_time_hms\":{\"query\":\"%s\"}}}]}},\"_source\":{\"includes\":[\"SUM\",\"SUM\",\"day_time_hms\"],\"excludes\":[]},\"stored_fields\":\"logic_junction_id\",\"sort\":[{\"day_time_hms\":{\"order\":\"asc\"}}],\"aggregations\":{\"logic_junction_id\":{\"terms\":{\"field\":\"logic_junction_id\",\"size\":10000},\"aggregations\":{\"agg_0\":{\"sum\":{\"script\":{\"inline\":\"doc['stop_delay_up'].value * doc['traj_num'].value\"}}},\"agg_1\":{\"sum\":{\"field\":\"traj_num\"}}}}}}",$cityId, $date." ".$hour);
        $res = $this->search($dsl,0,0);
        if(empty($res["aggregations"]["logic_junction_id"]["buckets"])){
            return [];
        }
        foreach($res["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
            $result[$bucket["key"]] = [
                "quotaMap"=>[
                    'weight_avg' => $bucket["agg_0"]["value"]/$bucket["agg_1"]["value"],
                    'junctionId' => $bucket["key"],
                ]
            ];
        }
        uasort($result,function ($a,$b) {
            $aValue = !empty($a["quotaMap"]["weight_avg"]) ? $a["quotaMap"]["weight_avg"] : 0;
            $bValue = !empty($b["quotaMap"]["weight_avg"]) ? $b["quotaMap"]["weight_avg"] : 0;
            if ($aValue==$bValue) return 0;
            return ($aValue<$bValue)?1:-1;
        });
        $result = array_values($result);
        return $result;
//        if(!empty($junctionIds)){
//            return $this->getTopStopDelayByJunctionId($cityId,$date,$hour,$pagesize,$junctionIds);
//        }
//
//        $trajNum = 5;
//        if($cityId==175){
//            $trajNum = 1;
//        }
//
//        $dayTime = $date . ' ' . $hour;
//        $data = [
//            "source" => "signal_control",
//            "cityId" => $cityId,
//            'requestId' => get_traceid(),
//            "dayTime" => $dayTime,
//            "trailNum" => $trajNum,
//            "andOperations" => [
//                "cityId" => "eq",
//                "dayTime" => "eq",
//                "trailNum" => 'gte',
//            ],
//            "quotaRequest" => [
//                "quotaType" => "weight_avg",
//                "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
//                "groupField" => "junctionId",
//                "orderField" => "weight_avg",
//                "asc" => "false",
//                "limit" => $pagesize,
//            ],
//        ];
//
//        $esRes = $this->searchQuota($data);
//        if (empty($esRes['result']['quotaResults'])) {
//            return [];
//        }
//
//        return $esRes['result']['quotaResults'];
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
        $dayTime = $date. ' ' .$hour;
        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }
        $chunkJunctionIds=array_chunk($junctionIds,1000);
        $resultList = [];
        foreach ($chunkJunctionIds as $Jids){
            $juncsWithQuota = [];
            foreach($Jids as $junc){
                $juncsWithQuota[] = '"'.$junc.'"';
            }
            $juncSql = '';
            if(!empty($juncsWithQuota)){
                $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
            }
            $sql = sprintf('select * from new_dmp_forecast* where city_id=%s and day_time_hms = "%s" and traj_num >= %s %s order by avg_stop_num_up desc limit %s', $cityId, $dayTime, $trajNum, $juncSql, $pagesize);
            // echo $sql;
            $res = $this->search($sql,1,0);
            $hitList=[];
            foreach($res["hits"]["hits"] as $hitIndex=>$source){
                foreach($source["_source"] as $column=>$columnVal){
                    $hitList[$hitIndex][camelize($column)] = $columnVal;
                    if($column=="logic_flow_id"){
                        $hitList[$hitIndex]["movementId"] = $columnVal;
                    }
                    if($column=="logic_junction_id"){
                        $hitList[$hitIndex]["junctionId"] = $columnVal;
                    }
                    if($column=="day_time_hms"){
                        $hitList[$hitIndex]["dayTime"] = $columnVal;
                    }
                    if($column=="traj_num"){
                        $hitList[$hitIndex]["trailNum"] = $columnVal;
                    }
                }
            }
            $resultList = array_merge($resultList,$hitList);
        }
        uasort($resultList,function ($a,$b) {
            $aValue = !empty($a["avgStopNumUp"]) ? $a["avgStopNumUp"]:0;
            $bValue = !empty($b["avgStopNumUp"]) ? $b["avgStopNumUp"]:0;
            if ($aValue==$bValue) return 0;
            return ($aValue<$bValue)?1:-1;
        });
        return array_values($resultList);
//        $trajNum = 5;
//        if($cityId==175){
//            $trajNum = 1;
//        }
//
//        $tmpRs = [];
//        $chunkJunctionIds=array_chunk($junctionIds,500);
//        foreach ($chunkJunctionIds as $Jids){
//            $data = [
//                'source' => 'signal_control', // 调用方
//                'cityId' => $cityId,          // 城市ID
//                'requestId' => get_traceid(),    // trace id
//                'trailNum' => $trajNum,
//                'dayTime' => $date . " " . $hour,
//                'andOperations' => [
//                    'cityId' => 'eq',  // cityId相等
//                    'trailNum' => 'gte', // 轨迹数大于等于5
//                    'dayTime' => 'eq',  // 等于hour
//                ],
//                'limit' => $pagesize,
//                "orderOperations" => [
//                    [
//                        'orderField' => 'avgStopNumUp',
//                        'orderType' => 'DESC',
//                    ],
//                ],
//            ];
//            if (!empty($Jids)) {
//                $data['junctionId'] = implode(",",$Jids);
//                $data["andOperations"]['junctionId'] = 'in';
//            }
//            $esRes = $this->searchDetail($data,false);
//            if (!empty($esRes)) {
//                $tmpRs = array_merge($tmpRs,$esRes);
//            }
//        }
//
//        uasort($tmpRs,function ($a,$b) {
//            $aValue = !empty($a["avgStopNumUp"]) ? $a["avgStopNumUp"]:0;
//            $bValue = !empty($b["avgStopNumUp"]) ? $b["avgStopNumUp"]:0;
//            if ($aValue==$bValue) return 0;
//            return ($aValue<$bValue)?1:-1;
//        });
//        return array_values($tmpRs);
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
        $resultList = [];
        $sql = sprintf('select * from new_dmp_forecast* where city_id=%s and day_time_hms = "%s" and traj_num >= %s order by avg_stop_num_up desc limit %s', $cityId, $date." ".$hour, $trajNum, $pagesize);
        // echo $sql;
        $res = $this->search($sql,1,0);
        $hitList=[];
        foreach($res["hits"]["hits"] as $hitIndex=>$source){
            foreach($source["_source"] as $column=>$columnVal){
                $hitList[$hitIndex][camelize($column)] = $columnVal;
                if($column=="logic_flow_id"){
                    $hitList[$hitIndex]["movementId"] = $columnVal;
                }
                if($column=="logic_junction_id"){
                    $hitList[$hitIndex]["junctionId"] = $columnVal;
                }
                if($column=="day_time_hms"){
                    $hitList[$hitIndex]["dayTime"] = $columnVal;
                }
                if($column=="traj_num"){
                    $hitList[$hitIndex]["trailNum"] = $columnVal;
                }
            }
        }
        $resultList = array_merge($resultList,$hitList);
        uasort($resultList,function ($a,$b) {
            $aValue = !empty($a["avgStopNumUp"]) ? $a["avgStopNumUp"]:0;
            $bValue = !empty($b["avgStopNumUp"]) ? $b["avgStopNumUp"]:0;
            if ($aValue==$bValue) return 0;
            return ($aValue<$bValue)?1:-1;
        });
        return array_values($resultList);
//        if(!empty($junctionIds)){
//            return $this->getTopCycleTimeByJunctionId($cityId, $date, $hour, $pagesize, $junctionIds);
//        }
//
//        $trajNum = 5;
//        if($cityId==175){
//            $trajNum = 1;
//        }
//
//        $data = [
//            'source' => 'signal_control', // 调用方
//            'cityId' => $cityId,          // 城市ID
//            'requestId' => get_traceid(),    // trace id
//            'trailNum' => $trajNum,
//            'dayTime' => $date . " " . $hour,
//            'andOperations' => [
//                'cityId' => 'eq',  // cityId相等
//                'trailNum' => 'gte', // 轨迹数大于等于5
//                'dayTime' => 'eq',  // 等于hour
//            ],
//            'limit' => $pagesize,
//            "orderOperations" => [
//                [
//                    'orderField' => 'avgStopNumUp',
//                    'orderType' => 'DESC',
//                ],
//            ],
//        ];
//        return $this->searchDetail($data, false);
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
        $quotaConf = $this->config->item('real_time_quota');
        $quotaKey = $params['quota_key'];
        $dayTime = $params['date'] . ' ' . $params['time_point'];
        $trajNum = 5;
        if($cityId==175){
            $trajNum = 1;
        }
        //旧指标转换新指标
        if($quotaKey=="stop_time_cycle"){
            $quotaKey = "avg_stop_num_up";
        }
        if($quotaKey=="spillover_rate"){
            $quotaKey = "spillover_rate_down";
        }
        if($quotaKey=="queue_length"){
            $quotaKey = "queue_length_up";
        }
        if($quotaKey=="stop_delay"){
            $quotaKey = "stop_delay_up";
        }
        if($quotaKey=="speed"){
            $quotaKey = "avg_speed_up";
        }
        if($quotaKey=="free_flow_speed"){
            $quotaKey = "free_flow_speed_up";
        }
        if($quotaKey=="twice_stop_rate"){
            $quotaKey = "multi_stop_ratio_up";
        }
        if($quotaKey=="stop_rate"){
            $quotaKey = "multi_stop_ratio_up+one_stop_ratio_up";
        }
        if($quotaKey=="saturation"){
            $quotaKey = "multi_stop_ratio_up+multi_stop_ratio_up";
        }

        // es所需data
        // todo 待验证
        if(empty($junctionIds)){
            $sumKeys=["stop_delay_up", "avg_speed_up", "one_stop_ratio_up", "traffic_jam_index_up", "travel_time_up"];
            $maxKeys=["spillover_rate_up"];
            $result = [];
            if (in_array($quotaKey,$sumKeys)) {
//                $sql = sprintf('select sum(%s * traj_num) as agg_0,sum(traj_num) as agg_1,logic_junction_id from new_dmp_forecast* where city_id="%s" and day_time_hms = "%s" and traj_num>=%d  group by terms("alias"="logic_junction_id","field"="logic_junction_id","size"=10000)', $quotaKey, $cityId, $dayTime, $trajNum);
//                $res = $this->search($sql,1,0);
                $dsl = sprintf("{\"from\":0,\"size\":0,\"query\":{\"bool\":{\"must\":{\"bool\":{\"must\":[{\"match\":{\"city_id\":{\"query\":\"%s\",\"type\":\"phrase\"}}},{\"match\":{\"day_time_hms\":{\"query\":\"%s\",\"type\":\"phrase\"}}},{\"range\":{\"traj_num\":{\"from\":%s,\"to\":null,\"include_lower\":true,\"include_upper\":true}}}]}}}},\"_source\":{\"includes\":[\"SUM\",\"SUM\",\"logic_junction_id\"],\"excludes\":[]},\"stored_fields\":\"logic_junction_id\",\"aggregations\":{\"logic_junction_id\":{\"terms\":{\"field\":\"logic_junction_id\",\"size\":10000},\"aggregations\":{\"agg_0\":{\"sum\":{\"script\":{\"inline\":\"doc['stop_delay_up'].value * doc['traj_num'].value\"}}},\"agg_1\":{\"sum\":{\"field\":\"traj_num\"}}}}}}",$cityId, $dayTime,$trajNum);
                $res = $this->search($dsl,0,0);
                if(empty($res["aggregations"]["logic_junction_id"]["buckets"])){
                    return [];
                }
                foreach($res["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
                    $result[$bucket["key"]] = [
                        "quotaMap"=>[
                            'weight_avg' => $bucket["agg_0"]["value"]/$bucket["agg_1"]["value"],
                            'junctionId' => $bucket["key"],
                        ]
                    ];
                }
                // ksort($result);
            }elseif(in_array($params['quota_key'],$maxKeys)) {
                $sql = sprintf('select max(%s) as agg_0, logic_junction_id from new_dmp_forecast* where city_id="%s" and day_time_hms = "%s" and traj_num>=%d group by terms("alias"="logic_junction_id","field"="logic_junction_id","size"=10000) ', $quotaKey, $cityId, $dayTime, $trajNum);
                // echo $sql;
                $res = $this->search($sql,1,0);
                if(empty($res["aggregations"]["logic_junction_id"]["buckets"])){
                    return [];
                }
                foreach($res["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
                    $result[$bucket["key"]] = [
                        "quotaMap"=>[
                            'max_'.camelize($params['quota_key'])=> $bucket["agg_0"]["value"],
                            'weight_avg' => $bucket["agg_0"]["value"],
                            'junctionId' => $bucket["key"],
                        ]
                    ];
                }
                // ksort($result);
            }else{
                $sql = sprintf('select avg(%s) as agg_0 from new_dmp_forecast* where city_id="%s" and day_time_hms = "%s" and traj_num>=%d group by terms("alias"="logic_junction_id","field"="logic_junction_id","size"=10000)', $quotaKey, $cityId, $dayTime, $trajNum);
                // echo $sql;
                $res = $this->search($sql,1,0);
                if(empty($res["aggregations"]["logic_junction_id"]["buckets"])){
                    return [];
                }
                foreach($res["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
                    $result[$bucket["key"]] = [
                        "quotaMap"=>[
                            'avg_'.camelize($params['quota_key'])=> $bucket["agg_0"]["value"],
                            'weight_avg' => $bucket["agg_0"]["value"],
                            'junctionId' => $bucket["key"],
                        ]
                    ];
                }
                // ksort($result);
            }
            $esRes = [];
            uasort($result,function ($a,$b) {
                $aValue = !empty($a["quotaMap"]["weight_avg"]) ? $a["quotaMap"]["weight_avg"] : 0;
                $bValue = !empty($b["quotaMap"]["weight_avg"]) ? $b["quotaMap"]["weight_avg"] : 0;
                if ($aValue==$bValue) return 0;
                return ($aValue<$bValue)?1:-1;
            });
            $esRes['result']['quotaResults'] = array_slice(array_values($result),0,$limit);
        }else{
            $chunkJunctionIds = array_chunk($junctionIds,1000);
            foreach ($chunkJunctionIds as $Jids){
                $juncsWithQuota = [];
                foreach($Jids as $junc){
                    $juncsWithQuota[] = '"'.$junc.'"';
                }
                $juncSql = '';
                if(!empty($juncsWithQuota)){
                    $juncSql = ' and logic_junction_id in ('.implode(",",$juncsWithQuota).') ';
                }
                $sumKeys=["stop_delay_up", "avg_speed_up", "one_stop_ratio_up", "traffic_jam_index_up", "travel_time_up"];
                $maxKeys=["spillover_rate_up"];
                $result = [];
                if (in_array($quotaKey,$sumKeys)) {
                    $sql = sprintf('select sum(%s * traj_num) as agg_0,sum(traj_num) as agg_1,day_time_hms from new_dmp_forecast* where city_id="%s" and day_time_hms = "%s" and traj_num>=%d %s group by terms("alias"="logic_junction_id","field"="logic_junction_id","size"=10000)', $quotaKey, $cityId, $dayTime, $trajNum, $juncSql);
                    $res = $this->search($sql,1,0);
                    if(empty($res["aggregations"]["logic_junction_id"]["buckets"])){
                        return [];
                    }
                    foreach($res["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
                        $result[$bucket["key"]] = [
                            "quotaMap"=>[
                                'weight_avg' => $bucket["agg_0"]["value"]/$bucket["agg_1"]["value"],
                                'junctionId' => $bucket["key"],
                            ]
                        ];
                    }
                }elseif(in_array($params['quota_key'],$maxKeys)) {
                    $sql = sprintf('select max(%s) as agg_0, day_time_hms from new_dmp_forecast* where city_id="%s" and day_time_hms = "%s" and traj_num>=%d %s group by terms("alias"="logic_junction_id","field"="logic_junction_id","size"=10000)', $quotaKey, $cityId, $dayTime, $trajNum, $juncSql);
                    // echo $sql;
                    $res = $this->search($sql,1,0);
                    if(empty($res["aggregations"]["logic_junction_id"]["buckets"])){
                        return [];
                    }
                    foreach($res["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
                        $result[$bucket["key"]] = [
                            "quotaMap"=>[
                                'max_'.camelize($params['quota_key'])=> $bucket["agg_0"]["value"],
                                'weight_avg' => $bucket["agg_0"]["value"],
                                'junctionId' => $bucket["key"],
                            ]
                        ];
                    }
                }else{
                    $sql = sprintf('select avg(%s) as agg_0, day_time_hms from new_dmp_forecast* where city_id="%s" and day_time_hms = "%s" and traj_num>=%d %s group by terms("alias"="logic_junction_id","field"="logic_junction_id","size"=10000)', $quotaKey, $cityId, $dayTime, $trajNum, $juncSql);
                    // echo $sql;
                    $res = $this->search($sql,1,0);
                    if(empty($res["aggregations"]["logic_junction_id"]["buckets"])){
                        return [];
                    }
                    foreach($res["aggregations"]["logic_junction_id"]["buckets"] as $bucket){
                        $result[$bucket["key"]] = [
                            "quotaMap"=>[
                                'avg_'.camelize($params['quota_key'])=> $bucket["agg_0"]["value"],
                                'weight_avg' => $bucket["agg_0"]["value"],
                                'junctionId' => $bucket["key"],
                            ]
                        ];
                    }
                }
            }
            $esRes = [];
            uasort($result,function ($a,$b) {
                $aValue = !empty($a["quotaMap"]["weight_avg"]) ? $a["quotaMap"]["weight_avg"] : 0;
                $bValue = !empty($b["quotaMap"]["weight_avg"]) ? $b["quotaMap"]["weight_avg"] : 0;
                if ($aValue==$bValue) return 0;
                return ($aValue<$bValue)?1:-1;
            });
            $esRes['result']['quotaResults'] = array_slice(array_values($result),0,$limit);
        }
        $data = array_column($esRes['result']['quotaResults'], 'quotaMap');
        $result = [];
        $junctionIds = implode(',', array_unique(array_column($data, 'junctionId')));
        $junctionsInfo  = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');
        foreach ($data as $k => $val) {
            $result['dataList'][$k] = [
                'logic_junction_id' => $val['junctionId'],
                'junction_name' => $junctionIdName[$val['junctionId']] ?? '未知路口',
                'quota_value' => $val["weight_avg"],
            ];
        }
        $result['quota_info'] = [
            'name' => $quotaConf[$params['quota_key']]['name'],
            'key' => $params['quota_key'],
            'unit' => $quotaConf[$params['quota_key']]['unit'],
        ];
        return $result;
//        $cityId   = $params['city_id'];
//        $limit   = $params['limit'];
//        $junctionIds = !empty($params['junction_id']) ? $params['junction_id'] : [];
//
//        // 指标配置
//        $quotaConf = $this->config->item('real_time_quota');
//        $quotaKey = $quotaConf[$params['quota_key']]['escolumn'];
//
//        // 获取最近时间
//        $dayTime = $params['date'] . ' ' . $params['time_point'];
//
//        $trajNum = 5;
//        if($cityId==175){
//            $trajNum = 1;
//        }
//
//        // es所需data
//        if(empty($junctionIds)){
//            $esData = [
//                "source"    => "signal_control",
//                "cityId"    => $cityId,
//                'requestId' => get_traceid(),
//                "dayTime"   => $dayTime,
//                "trailNum"  => $trajNum,
//                "andOperations" => [
//                    "cityId"   => "eq",
//                    "dayTime"  => "eq",
//                    "trailNum" => 'gte',
//                ],
//                "quotaRequest" => [
//                    "groupField" => "junctionId",
//                    "asc"        => "false",
//                    "limit"      => $limit,
//                ],
//            ];
//            if ($params['quota_key'] == 'stop_delay') {
//                $esData['quotaRequest']['quotas'] = 'sum_' . $quotaKey . '*trailNum, sum_trailNum';
//                $esData['quotaRequest']['orderField'] = "weight_avg";
//                $esData['quotaRequest']['quotaType'] = "weight_avg";
//                $esQuotaKey = 'weight_avg'; // es接口返回的字段名
//            } else {
//                $esData['quotaRequest']['quotas'] = 'avg_' . $quotaKey;
//                $esData['quotaRequest']['orderField'] = 'avg_' . $quotaKey;
//                $esQuotaKey = 'avg_' . $quotaKey; // es接口返回的字段名
//            }
//
//            if (!empty($junctionIds)) {
//                $esData['junctionId'] = implode(",",$junctionIds);
//                $esData["andOperations"]['junctionId'] = 'in';
//            }
//            $esRes = $this->searchQuota($esData);
//            if (!$esRes) {
//                return [];
//            }
//        }else{
//            $chunkJunctionIds = array_chunk($junctionIds,1000);
//            $tmpRes = [];
//            foreach ($chunkJunctionIds as $Jids){
//                $esData = [
//                    "source"    => "signal_control",
//                    "cityId"    => $cityId,
//                    'requestId' => get_traceid(),
//                    "dayTime"   => $dayTime,
//                    "trailNum"  => $trajNum,
//                    "andOperations" => [
//                        "cityId"   => "eq",
//                        "dayTime"  => "eq",
//                        "trailNum" => 'gte',
//                    ],
//                    "quotaRequest" => [
//                        "groupField" => "junctionId",
//                        "asc"        => "false",
//                        "limit"      => $limit,
//                    ],
//                ];
//                if ($params['quota_key'] == 'stop_delay') {
//                    $esData['quotaRequest']['quotas'] = 'sum_' . $quotaKey . '*trailNum, sum_trailNum';
//                    $esData['quotaRequest']['orderField'] = "weight_avg";
//                    $esData['quotaRequest']['quotaType'] = "weight_avg";
//                    $esQuotaKey = 'weight_avg'; // es接口返回的字段名
//                } else {
//                    $esData['quotaRequest']['quotas'] = 'avg_' . $quotaKey;
//                    $esData['quotaRequest']['orderField'] = 'avg_' . $quotaKey;
//                    $esQuotaKey = 'avg_' . $quotaKey; // es接口返回的字段名
//                }
//
//                $esData['junctionId'] = implode(",",$Jids);
//                $esData["andOperations"]['junctionId'] = 'in';
//
//                $searchRes = $this->searchQuota($esData);
//                if (!empty($searchRes['result']['quotaResults'])) {
//                    $tmpRes = array_merge($tmpRes,$searchRes['result']['quotaResults']);
//                }
//            }
//            $esRes = [];
//            uasort($tmpRes,function ($a,$b) use($esQuotaKey) {
//                $aValue = !empty($a["quotaMap"][$esQuotaKey]) ? $a["quotaMap"][$esQuotaKey] : 0;
//                $bValue = !empty($b["quotaMap"][$esQuotaKey]) ? $b["quotaMap"][$esQuotaKey] : 0;
//                if ($aValue==$bValue) return 0;
//                return ($aValue<$bValue)?1:-1;
//            });
//            $esRes['result']['quotaResults'] = array_values($tmpRes);
//        }
//
//        $data = array_column($esRes['result']['quotaResults'], 'quotaMap');
//        $result = [];
//
//        // 所需查询路口名称的路口ID串
//        $junctionIds = implode(',', array_unique(array_column($data, 'junctionId')));
//
//        // 获取路口信息
//        $junctionsInfo  = $this->waymap_model->getJunctionInfo($junctionIds);
//        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');
//
//        foreach ($data as $k => $val) {
//            $result['dataList'][$k] = [
//                'logic_junction_id' => $val['junctionId'],
//                'junction_name' => $junctionIdName[$val['junctionId']] ?? '未知路口',
//                'quota_value' => $val[$esQuotaKey],
//            ];
//        }
//
//        // 返回数据：指标信息
//        $result['quota_info'] = [
//            'name' => $quotaConf[$params['quota_key']]['name'],
//            'key' => $params['quota_key'],
//            'unit' => $quotaConf[$params['quota_key']]['unit'],
//        ];
//        return $result;
    }

    public function junctionRealtimeFlowQuotaList($params)
    {
        return $this->getJunctionQuotaSortList($params);
//        $cityId   = $params['city_id'];
//        $limit   = $params['limit'];
//        $junctionIds = !empty($params['junction_id']) ? $params['junction_id'] : [];
//
//        // 指标配置
//        $quotaConf = $this->config->item('real_time_quota');
//        $quotaKey = $quotaConf[$params['quota_key']]['escolumn'];
//
//        // 获取最近时间
//        $dayTime = $params['date'] . ' ' . $params['time_point'];
//
//        $trajNum = 5;
//        if($cityId==175){
//            $trajNum = 1;
//        }
//
//        // es所需data
//        if(empty($junctionIds)){
//            $esData = [
//                "source"    => "signal_control",
//                "cityId"    => $cityId,
//                'requestId' => get_traceid(),
//                "dayTime"   => $dayTime,
//                "trailNum"  => $trajNum,
//                "andOperations" => [
//                    "cityId"   => "eq",
//                    "dayTime"  => "eq",
//                    "trailNum" => 'gte',
//                ],
//                "quotaRequest" => [
//                    "groupField" => "junctionId",
//                    "asc"        => "false",
//                    "limit"      => $limit,
//                ],
//            ];
//            if ($params['quota_key'] == 'stop_delay') {
//                $esData['quotaRequest']['quotas'] = 'sum_' . $quotaKey . '*trailNum, sum_trailNum';
//                $esData['quotaRequest']['orderField'] = "weight_avg";
//                $esData['quotaRequest']['quotaType'] = "weight_avg";
//                $esQuotaKey = 'weight_avg'; // es接口返回的字段名
//            } else {
//                $esData['quotaRequest']['quotas'] = 'avg_' . $quotaKey;
//                $esData['quotaRequest']['orderField'] = 'avg_' . $quotaKey;
//                $esQuotaKey = 'avg_' . $quotaKey; // es接口返回的字段名
//            }
//
//            if (!empty($junctionIds)) {
//                $esData['junctionId'] = implode(",",$junctionIds);
//                $esData["andOperations"]['junctionId'] = 'in';
//            }
//            $esRes = $this->searchQuota($esData);
//            if (!$esRes) {
//                return [];
//            }
//        }else{
//            $chunkJunctionIds = array_chunk($junctionIds,1000);
//            $tmpRes = [];
//            foreach ($chunkJunctionIds as $Jids){
//                $esData = [
//                    "source"    => "signal_control",
//                    "cityId"    => $cityId,
//                    'requestId' => get_traceid(),
//                    "dayTime"   => $dayTime,
//                    "trailNum"  => $trajNum,
//                    "andOperations" => [
//                        "cityId"   => "eq",
//                        "dayTime"  => "eq",
//                        "trailNum" => 'gte',
//                    ],
//                    "quotaRequest" => [
//                        "groupField" => "junctionId",
//                        "asc"        => "false",
//                        "limit"      => $limit,
//                    ],
//                ];
//                if ($params['quota_key'] == 'stop_delay') {
//                    $esData['quotaRequest']['quotas'] = 'sum_' . $quotaKey . '*trailNum, sum_trailNum';
//                    $esData['quotaRequest']['orderField'] = "weight_avg";
//                    $esData['quotaRequest']['quotaType'] = "weight_avg";
//                    $esQuotaKey = 'weight_avg'; // es接口返回的字段名
//                } else {
//                    $esData['quotaRequest']['quotas'] = 'avg_' . $quotaKey;
//                    $esData['quotaRequest']['orderField'] = 'avg_' . $quotaKey;
//                    $esQuotaKey = 'avg_' . $quotaKey; // es接口返回的字段名
//                }
//
//                $esData['junctionId'] = implode(",",$Jids);
//                $esData["andOperations"]['junctionId'] = 'in';
//
//                $searchRes = $this->searchQuota($esData);
//                if (!empty($searchRes['result']['quotaResults'])) {
//                    $tmpRes = array_merge($tmpRes,$searchRes['result']['quotaResults']);
//                }
//            }
//            $esRes = [];
//            uasort($tmpRes,function ($a,$b) use($esQuotaKey) {
//                $aValue = !empty($a["quotaMap"][$esQuotaKey]) ? $a["quotaMap"][$esQuotaKey] : 0;
//                $bValue = !empty($b["quotaMap"][$esQuotaKey]) ? $b["quotaMap"][$esQuotaKey] : 0;
//                if ($aValue==$bValue) return 0;
//                return ($aValue<$bValue)?1:-1;
//            });
//            $esRes['result']['quotaResults'] = array_values($tmpRes);
//        }
//
//        $data = array_column($esRes['result']['quotaResults'], 'quotaMap');
//        $result = [];
//
//        // 所需查询路口名称的路口ID串
//        $junctionIds = implode(',', array_unique(array_column($data, 'junctionId')));
//
//        // 获取路口信息
//        $junctionsInfo  = $this->waymap_model->getJunctionInfo($junctionIds);
//        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');
//
//        foreach ($data as $k => $val) {
//            $result['dataList'][$k] = [
//                'logic_junction_id' => $val['junctionId'],
//                'junction_name' => $junctionIdName[$val['junctionId']] ?? '未知路口',
//                'quota_value' => $val[$esQuotaKey],
//            ];
//        }
//
//        // 返回数据：指标信息
//        $result['quota_info'] = [
//            'name' => $quotaConf[$params['quota_key']]['name'],
//            'key' => $params['quota_key'],
//            'unit' => $quotaConf[$params['quota_key']]['unit'],
//        ];
//        return $result;
    }
}
