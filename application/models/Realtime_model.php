<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午4:22
 */

class Realtime_model extends CI_Model
{
    /**
     * @var \CI_DB_query_builder
     */
    protected $db;
    private $tb = 'real_time_';

    // es interface addr
    private $esUrl = '';

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        // load config
        $this->load->config('nconf');
        $this->esUrl = $this->config->item('es_interface');
    }

    /**
     * ES诊断明细查询方法
     * @param $data      array es查询条件数组
     * @param $scrollsId string 分页ID 不为空时表示有分页
     * @return array
     */
    public function searchDetail($data)
    {
        $resData = [];
        $result = httpPOST($this->esUrl . '/estimate/diagnosis/queryIndices', $data, 0, 'json');

        if (!$result) {
            throw new \Exception('调用es接口 queryIndices 失败！', ERR_DEFAULT);
        }
        $result = json_decode($result, true);

        if ($result['code'] == '000000') {  // 000000:还有数据可查询 400001:查询完成
            $resData = $result['result']['diagnosisIndices'];
            $data['scrollsId'] = $result['result']['scrollsId'];
            $resData = array_merge($resData, $this->searchDetail($data));
        }

        if ($result['code'] == '400001') {
            $resData = array_merge($resData, $result['result']['diagnosisIndices']);
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
        $result = httpPOST($this->esUrl . '/estimate/diagnosis/queryQuota', $data, 0, 'json');

        if (!$result) {
            throw new \Exception('调用es接口 queryIndices 失败！', ERR_DEFAULT);
        }
        $result = json_decode($result, true);

        if ($result['code'] != '000000' && $result['code'] != '400001') {
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
            'source'        => 'signal_control', // 调用方
            'cityId'        => $cityId,          // 城市ID
            'requestId'     => get_traceid(),    // trace id
            'timestamp'     => strtotime(date('Y-m-d')) * 1000, // 当天0点(yyyy-mm-dd 00:00:00)毫秒时间戳
            'andOperations' => [
                'cityId'    => 'eq', // cityId相等
                'timestamp' => 'gte' // 大于等于当天开始时间
            ],
            'quotaRequest'  => [
                "groupField" => 'dayTime',
                "limit"      => 1,
                "orderField" => "max_timestamp",
                "asc"        => false,
                "quotas"     => "max_timestamp",
            ],
        ];

        $res = $this->searchQuota($data);

        if (empty($res['result']['quotaResults']['quotaMap'])) {
            throw new \Exception('获取实时数据最新批次hour失败！', ERR_DEFAULT);
        }

        $lastHour = date('H:i:s', strtotime($res['result']['quotaResults']['quotaMap']['dayTime']));
        return $lastHour;
    }

    /**
     * 平均延误曲线图
     * @param $cityId int    城市ID
     * @param $date   string 日期 yyyy-mm-dd
     * @return array
     */
    public function avgStopdelay($cityId, $date)
    {
        $startTime = strtotime($date . ' 00:00:00') * 1000;
        $endTime = strtotime($date . ' 23:59:59') * 1000;

        $data = [
            "source" => "signal_control",
            "cityId" => $cityId,
            'requestId' => get_traceid(),
            "timestamp" => "[{$startTime}, {$endTime}]",
            "andOperations" => [
                "cityId" => "eq",
                "timestamp" => "range",
            ],
            "quotaRequest" => [
                "quotaType" => "weight_avg",
                "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
                "groupField" => "dayTime",
                "orderField" => "dayTime",
                "asc" => "true",
            ],
        ];

        $esRes = $this->searchQuota($data);
        if (empty($esRes['result']['quotaResults'])) {
            return [];
        }

        $result = [];
        foreach ($esRes['result']['quotaResults'] as $k=>$v) {
            $result[$k] = [
                'avg_stop_delay' => $v['quotaMap']['weight_avg'],
                'hour'           => date('H:i:s', strtotime($v['quotaMap']['dayTime'])),
            ];
        }

        return $result;
    }

    /**
     * 获取实时指标路口数据（概览页路口列表）
     * @param $cityId int    城市ID
     * @param $date   string 日期 yyyy-mm-dd
     * @param $hour   string 时间 HH:ii:ss
     * @return array
     */
    public function getRealTimeJunctions($cityId, $date, $hour)
    {
        $data = [
            'source'        => 'signal_control', // 调用方
            'cityId'        => $cityId,          // 城市ID
            'requestId'     => get_traceid(),    // trace id
            'trailNum'      => 5,
            'dayTime'       => $date ." ". $hour,
            'andOperations' => [
                'cityId'    => 'eq',  // cityId相等
                'trailNum'  => 'gte', // 轨迹数大于等于5
                'dayTime'   => 'eq',  // 等于hour
            ],
            'limit'         => 5000,
        ];
        $realTimeEsData = $this->searchDetail($data);
        $result = [];
        foreach ($realTimeEsData as $k=>$v) {
            $result[$k] = [
                'logic_junction_id' => $v['junctionId'],
                'hour'              => date('H:i:s', strtotime($v['dayTime'])),
                'logic_flow_id'     => $v['movementId'],
                'stop_time_cycle'   => $v['avgStopNumUp'],
                'spillover_rate'    => $v['spilloverRateDown'],
                'queue_length'      => $v['queueLengthUp'],
                'stop_delay'        => $v['stopDelayUp'],
                'stop_rate'         => ($v['oneStopRatioUp'] + $v['multiStopRatioUp']),
                'twice_stop_rate'   => $v['multiStopRatioUp'],
                'speed'             => $v['avgSpeedUp'],
                'free_flow_speed'   => $v['freeFlowSpeedUp'],
                'traj_count'        => $v['trailNum'],
            ];
        }

        return $result;
    }

    /**
     * 判断数据表是否存在
     *
     * @param $cityId
     *
     * @throws Exception
     */
    protected function isExisted($cityId)
    {
        $isExisted = $this->db->table_exists($this->tb . $cityId);

        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
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
    public function getFlowsInFlowIds($cityId, $hour, $logicJunctionId, $logicFlowId, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('hour', $hour)
            ->where('logic_junction_id', $logicJunctionId)
            ->where('updated_at > ', date('Y-m-d', strtotime('-10  minutes')))
            ->where_in('logic_flow_id', $logicFlowId)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param        $cityId
     * @param        $hour
     * @param        $quotaKey
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getQuotasByHour($cityId, $hour, $quotaKey, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('traj_count >= 10')
            ->where('hour', $hour)
            ->group_by('logic_junction_id')
            ->order_by($quotaKey, 'DESC')
            ->limit(100)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * @param        $cityId
     * @param        $logicJunctionId
     * @param        $logicFlowId
     * @param        $upTime
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getQuotaByFlowId($cityId, $logicJunctionId, $logicFlowId, $upTime, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('logic_junction_id', $logicJunctionId)
            ->where('logic_flow_id', $logicFlowId)
            ->where('updated_at >', $upTime)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 获取平均延误-按时间与路口
     * @param $cityId int    城市ID
     * @param $hour   string 时间 HH:ii:ss
     * @param $date   string 日期 yyyy-mm-dd
     * @return array
     * @throws Exception
     */
    public function getAvgQuotaByJunction($cityId, $date, $hour)
    {
        $dayTime = $date . ' ' . $hour;
        $data = [
            "source"    => "signal_control",
            "cityId"    => $cityId,
            'requestId' => get_traceid(),
            "dayTime"   => $dayTime,
            "trailNum"  => 10,
            "andOperations" => [
                "cityId"   => "eq",
                "dayTime"  => "eq",
                "trailNum" => 'gte',
            ],
            "quotaRequest" => [
                "quotaType" => "weight_avg",
                "quotas" => "sum_stopDelayUp*trailNum, sum_trailNum",
                "groupField" => "logic_junction_id",
            ],
        ];

        $esRes = $this->searchQuota($data);
        if (empty($esRes['result']['quotaResults'])) {
            return [];
        }

        return $esRes['result']['quotaResults'];
    }

    /**
     * 延误top20
     * @param  $cityId    int    城市ID
     * @param  $date      string 日期 yyyy-mm-dd
     * @param  $hour      string 时间 HH:ii:ss
     * @param  $pagesize  int    获取数量
     * @return array
     * @throws Exception
     */
    public function getTopStopDelay($cityId, $date, $hour, $pagesize)
    {
        $dayTime = $date . ' ' . $hour;
        $data = [
            "source"    => "signal_control",
            "cityId"    => $cityId,
            'requestId' => get_traceid(),
            "dayTime"   => $dayTime,
            "trailNum"  => 10,
            "andOperations" => [
                "cityId"   => "eq",
                "dayTime"  => "eq",
                "trailNum" => 'gte',
            ],
            "quotaRequest" => [
                "quotaType"  => "weight_avg",
                "quotas"     => "sum_stopDelayUp*trailNum, sum_trailNum",
                "groupField" => "logic_junction_id",
                "orderField" => "weight_avg",
                "asc"        => "false",
                "limit"      => $pagesize,
            ],
        ];

        $esRes = $this->searchQuota($data);
        if (empty($esRes['result']['quotaResults'])) {
            return [];
        }

        return $esRes['result']['quotaResults'];
    }

    /**
     * @param        $cityId
     * @param        $date
     * @param        $hour
     * @param        $pagesize
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getTopCycleTime($cityId, $date, $hour, $pagesize, $select = '*')
    {
        $this->isExisted($cityId);

        $res = $this->db->select($select)
            ->from($this->tb . $cityId)
            ->where('hour', $hour)
            ->where('traj_count >=', 10)
            ->where('updated_at >=', $date . ' 00:00:00')
            ->where('updated_at <=', $date . ' 23:59:59')
            ->order_by('stop_time_cycle', 'desc')
            ->limit($pagesize)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
}