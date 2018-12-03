<?php

/********************************************
 * # desc:    实时报警model
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-07-30
 ********************************************/
class Realtimewarning_model extends CI_Model
{
    protected $token;
    protected $userid = '';

    /**
     * @var CI_DB_query_builder
     */
    protected $db;

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        $this->load->config('nconf');
        $this->load->helper('http');

        $this->token  = $this->config->item('waymap_token');
        $this->userid = $this->config->item('waymap_userid');

        $this->config->load('realtime_conf');
        $this->load->model('waymap_model');
        $this->load->model('alarmanalysis_model');
        $this->load->model('realtime_model');
    }

    public function process($cityId, $date, $hour, $traceId)
    {
        $rtwRule   = $this->config->item('realtimewarning_rule');
        $rtwRule   = empty($rtwRule[$cityId]) ? $rtwRule['default'] : $rtwRule[$cityId];
        $tableName = "real_time_" . $cityId;
        $isExisted = $this->db->table_exists($tableName);
        if (!$isExisted) {
            echo "{$tableName} not exists!\n\r";
            exit;
        }

        $currentId = 0;
        while (1) {
            $sql = "SELECT * FROM `{$tableName}` WHERE `updated_at`>\"{$date}\" and hour=\"{$hour}\" and id>{$currentId} {$rtwRule['where']} order by id asc limit 2000";
            $this->db->forceMaster();
            $query = $this->db->query($sql);
            $this->db->ignoreMaster();

            $result = $query->result_array();
            if (empty($result)) {
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=$traceId||sql=$sql||message=loop over!\n\r";
                break;
            }
            foreach ($result as $var => $val) {
                $currentId = $val["id"];
                if ($this->isOverFlow($val, $rtwRule)) {
                    $this->updateWarning($val, 1, $date, $cityId, $traceId);
                }
                if ($this->isSAT($val, $rtwRule)) {
                    $this->updateWarning($val, 2, $date, $cityId, $traceId);
                }
                //sleep(10);
            }
        }

        //删除30天前数据
        $splitHour  = explode(':', $hour);
        $limitMinus = [3, 4, 5];                          //只在分钟级的0-2之间执行
        if (isset($splitHour[0]) &&
            $splitHour[0] == '00' &&                     //小时
            isset($splitHour[1][1]) &&
            $splitHour[1][0] == '0' &&                    //分钟第一位
            in_array($splitHour[1][1], $limitMinus)) {   //分钟第二位
            $dtime = date("Y-m-d H:i:s", strtotime("-30 day"));
            $sql   = "DELETE FROM `real_time_alarm` WHERE `created_at`<'{$dtime}';";
            $this->db->query($sql);
            echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=$traceId||sql=$sql||message=delete_expired_data\n\r";
        }
    }

    /**
     * 是否溢流
     *
     * @param $record
     *
     * @return bool
     */
    public function isOverFlow($record, $rule)
    {
        if (!isset($rule['isOverFlow']['spillover_rate']) || !isset($rule['isOverFlow']['stop_delay'])) {
            return false;
        }
        if (!isset($record["spillover_rate"]) || !isset($record["stop_delay"])) {
            return false;
        }
        if ($record["spillover_rate"] >= $rule['isOverFlow']['spillover_rate'] && $record["stop_delay"] >= $rule['isOverFlow']['stop_delay']) {
            return true;
        }
        return false;
    }

    public function updateWarning($val, $type, $date, $cityId, $traceId)
    {
        //验证路口问题
        $logicJunctionId   = $val['logic_junction_id'];
        $logicFlowId       = $val['logic_flow_id'];
        $realtimeUpatetime = $val['updated_at'];
        $this->db->reconnect();
        $this->db->trans_begin();
        try {
            //判断数据是否存在?
            $warnRecord      = $this->db->select("id, start_time, last_time")->from('real_time_alarm')
                ->where('date', $date)
                ->where('logic_flow_id', $logicFlowId)
                ->where('type', $type)
                ->where('deleted_at', "1970-01-01 00:00:00")
                ->get()->result_array();
            $warningId       = !empty($warnRecord[0]['id']) ? $warnRecord[0]['id'] : 0;
            $warningLastTime = !empty($warnRecord[0]['last_time']) ? $warnRecord[0]['last_time'] : 0;
            if ($warningId == 0) {
                //今天无数据
                $data = [
                    'city_id' => $cityId,
                    'logic_junction_id' => $logicJunctionId,
                    'logic_flow_id' => $logicFlowId,
                    'start_time' => $realtimeUpatetime,
                    'last_time' => $realtimeUpatetime,
                    'type' => $type,
                    'count' => 1,
                    'date' => $date,
                    'deleted_at' => '1970-01-01 00:00:00',
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s"),
                ];
                $this->db->insert('real_time_alarm', $data);
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=" . $traceId . "||junction_id=" . $logicJunctionId . "||flow_id=" . $logicFlowId . "||message=insert\n\r";
            } else {
                //判断warning表的最后一次更新时间点与实时表数据更新时间差是否小于10分钟?
                $diffTime = strtotime($realtimeUpatetime) - strtotime($warningLastTime);

                //小于等于0时,代表重复执行脚本,不执行操作
                if ($diffTime <= 0) {
                    throw new \Exception("repeat_process");
                }

                //大于10分钟时, 代表非持续报警, 更新start_time
                if ($diffTime > 600) {
                    $this->db->set('start_time', $realtimeUpatetime);
                }
                $this->db->set('count', 'count+1', false);
                $this->db->set('updated_at', date("Y-m-d H:i:s"));
                $this->db->set('last_time', $realtimeUpatetime);
                $this->db->where('id', $warningId);
                $this->db->update('real_time_alarm');
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=" . $traceId . "||junction_id=" . $logicJunctionId . "||flow_id=" . $logicFlowId . "||message=update\n\r";
            }
            $this->db->trans_commit();
        } catch (\Exception $e) {
            $this->db->trans_rollback();
            echo "[ERROR] " . date("Y-m-d\TH:i:s") . " trace_id=" . $traceId . "||junction_id=" . $logicJunctionId . "||flow_id=" . $logicFlowId . "||message=" . $e->getMessage() . "\n\r";
            com_log_warning('_realtimewarning_updatewarning_error', 0, $e->getMessage(), compact("val", "type", "date", "cityId", "traceId"));
        }
        return true;
    }

    /**
     * 是否过饱和
     *
     * @param $record
     *
     * @return bool
     */
    public function isSAT($record, $rule)
    {
        if (!isset($rule['isSAT']['twice_stop_rate']) || !isset($rule['isSAT']['queue_length']) || !isset($rule['isSAT']['stop_delay'])) {
            return false;
        }
        if (!isset($record["twice_stop_rate"]) || !isset($record["queue_length"]) || !isset($record["stop_delay"])) {
            return false;
        }
        if ($record["twice_stop_rate"] >= $rule['isSAT']['twice_stop_rate'] && $record["queue_length"] >= $rule['isSAT']['queue_length'] && $record["stop_delay"] >= $rule['isSAT']['stop_delay']) {
            return true;
        }
        return false;
    }

    /**
     * 指标计算
     *
     * @param $cityId
     * @param $date
     * @param $hour
     * @param $traceId
     */
    public function calculate($cityId, $date, $hour, $traceId)
    {
        $rtwRule   = $this->config->item('realtimewarning_rule');
        $rtwRule   = empty($rtwRule[$cityId]) ? $rtwRule['default'] : $rtwRule[$cityId];
        $tableName = "real_time_" . $cityId;
        $isExisted = $this->db->table_exists($tableName);
        if (!$isExisted) {
            echo "{$tableName} not exists!\n\r";
            exit;
        }

        //todo生成rediskey
        $this->load->model('redis_model');

        //生成 avg(stop_delay) group by hour
        //生成平均延误曲线数据
        $splitHour  = explode(':', $hour);
        $limitMinus = [5, 6, 7];  //只在分钟级的5-7之间执行
        if (isset($splitHour[1][1]) && in_array($splitHour[1][1], $limitMinus)) {
            $sql = "SELECT `hour`, sum(stop_delay * traj_count) / sum(traj_count) as avg_stop_delay FROM `{$tableName}` WHERE `updated_at` >= '{$date} 00:00:00' AND `updated_at` <= '{$date} 23:59:59' GROUP BY `hour`";
            $this->db->setQueryFlag("100001");
            $this->db->forceMaster();
            $query = $this->db->query($sql);
            $this->db->ignoreMaster();
            $this->db->setQueryFlag("");

            $result = $query->result_array();
            if (empty($result)) {
                echo "生成 avg(stop_delay) group by hour failed!\n\r{$cityId} {$date} {$hour}\n\r";
                exit;
            }
            $avgStopDelayKey = "its_realtime_avg_stop_delay_{$cityId}_{$date}";
            $this->redis_model->setEx($avgStopDelayKey, json_encode($result), 24 * 3600);
        }

        //========计算缓存数据start==========>
        //获取实时指标数据
        $data = [
            'source'        => 'signal_control', // 调用方
            'cityId'        => $cityId,          // 城市ID
            'requestId'     => get_traceid(),    // trace id
            'trailNum'      => 5,
            'dayTime'       => $date ." ". $hour,
            'andOperations' => [
                'cityId'    => 'eq', // cityId相等
                'trailNum'  => 'gte', // 轨迹数大于等于5
                'dayTime'   => 'eq',  // 等于hour
            ],
            'limit'         => 5000,
        ];
        $realTimeEsData = $this->realtime_model->searchDetail($data);
        $result = [];
        foreach ($realTimeEsData as $k=>$v) {
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

        //获取实时报警表数据
        $data['date'] = $date;
        $data['city_id'] = $cityId;
        $realTimeAlarmsInfoResultOrigal = $this->alarmanalysis_model->getRealTimeAlarmsInfoFromEs($cityId, $date, $hour);
        //实时数据flow排重===>开始
        $realTimeAlarmsInfoResult = [];  
        foreach ($realTimeAlarmsInfoResultOrigal as $item) {
            $realTimeAlarmsInfoResult[$item['logic_flow_id'] . $item['type']] = $item;
        }
        $realTimeAlarmsInfoResult = array_values($realTimeAlarmsInfoResult);
        //实时数据flow排重<===结束

        //聚合路口数据
        $realTimeAlarmsInfo = [];
        foreach ($realTimeAlarmsInfoResult as $item) {
            $realTimeAlarmsInfo[$item['logic_flow_id'] . $item['type']] = $item;
        }
        $junctionList = $this->getJunctionListResult($cityId, $result, $realTimeAlarmsInfo);

        //计算junctionSurvey 数据
        $data                       = $junctionList['dataList'] ?? [];
        $result                     = [];
        $result['junction_total']   = count($data);
        $result['alarm_total']      = 0;
        $result['congestion_total'] = 0;
        foreach ($data as $datum) {
            $result['alarm_total']      += $datum['alarm']['is'] ?? 0;
            $result['congestion_total'] += (int)(($datum['status']['key'] ?? 0) == 3);
        }
        $junctionSurvey = $result;
        //<========计算缓存数据end==========

        // 缓存诊断路口统计数据
        $junctionSurveyKey = "its_realtime_pretreat_junction_survey_{$cityId}_{$date}_{$hour}";
        $this->redis_model->setEx($junctionSurveyKey, json_encode($junctionSurvey), 24 * 3600);

        // 缓存诊断路口列表数据
        $junctionListKey = "its_realtime_pretreat_junction_list_{$cityId}_{$date}_{$hour}";
        $this->redis_model->setEx($junctionListKey, json_encode($junctionList), 24 * 3600);

        // 缓存最新hour
        $redisKey = "its_realtime_lasthour_$cityId";
        $this->redis_model->setEx($redisKey, $hour, 24 * 3600);

        // 缓存实时报警路口数据
        $realTimeAlarmRedisKey = 'its_realtime_alarm_' . $cityId;
        $this->redis_model->setEx($realTimeAlarmRedisKey, json_encode($realTimeAlarmsInfoResult), 24 * 3600);

        // 冗余缓存实时报警路口数据,每一个批次一份
        $realTimeAlarmBakKey = "its_realtime_alarm_{$cityId}_{$date}_{$hour}";
        $this->redis_model->setEx($realTimeAlarmBakKey, json_encode($realTimeAlarmsInfoResult), 24 * 3600);
    }

    //================以下方法全部为数据处理方法=====================//

    /**
     * 处理从数据库中取出的原始数据并返回
     *
     * @param $cityId
     * @param $result
     * @param $realTimeAlarmsInfo
     *
     * @return array
     */
    private function getJunctionListResult($cityId, $result, $realTimeAlarmsInfo)
    {
        //获取全部路口 ID
        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        //获取路口信息的自定义返回格式
        $junctionsInfo = $this->waymap_model->getAllCityJunctions($cityId, 0);
        $junctionsInfo = array_column($junctionsInfo, null, 'logic_junction_id');

        //获取需要报警的全部路口ID
        $alarmJunctonIdArr = array_unique(array_column($realTimeAlarmsInfo, 'logic_junction_id'));
        $ids = implode(',', $alarmJunctonIdArr);

        //获取需要报警的全部路口的全部方向的信息
        try {
            $flowsInfo = $this->waymap_model->getFlowsInfo($ids);
        }catch(\Exception $e){
            $flowsInfo = [];
        }

        //数组初步处理，去除无用数据
        $result = array_map(function ($item) use ($flowsInfo, $realTimeAlarmsInfo) {
            $alarmInfo = $this->getRawAlarmInfo($item, $flowsInfo, $realTimeAlarmsInfo);
            if($item['traj_count']>=10 || !empty($alarmInfo)){
                return [
                    'logic_junction_id' => $item['logic_junction_id'],
                    'quota' => $this->getRawQuotaInfo($item),
                    'alarm_info' => $this->getRawAlarmInfo($item, $flowsInfo, $realTimeAlarmsInfo),
                ];
            }
        }, $result);

        //数组按照 logic_junction_id 进行合并
        $temp = [];
        foreach ($result as $item) {
            $temp[$item['logic_junction_id']] = isset($temp[$item['logic_junction_id']]) ?
                $this->mergeFlowInfo($temp[$item['logic_junction_id']], $item) :
                $item;
        };

        //处理数据内容格式
        $temp = array_map(function ($item) use ($junctionsInfo) {
            return [
                    'jid' => $item['logic_junction_id'],
                    'name' => $junctionsInfo[$item['logic_junction_id']]['name'] ?? '',
                    'lng' => $junctionsInfo[$item['logic_junction_id']]['lng'] ?? '',
                    'lat' => $junctionsInfo[$item['logic_junction_id']]['lat'] ?? '',
                    'quota' => ($quota = $this->getFinalQuotaInfo($item)),
                    'alarm' => $this->getFinalAlarmInfo($item),
                    'status' => $this->getJunctionStatus($quota),
                ];
        }, $temp);

        $lngs = array_filter(array_column($temp, 'lng'));
        $lats = array_filter(array_column($temp, 'lat'));

        $center['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
        $center['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));

        return [
            'dataList' => array_values($temp),
            'center' => $center,
        ];
    }

    /**
     * 获取原始指标信息
     *
     * @param $item
     *
     * @return array
     */
    private function getRawQuotaInfo($item)
    {
        return [
            'stop_delay_weight' => $item['stop_delay'] * $item['traj_count'],
            'stop_time_cycle' => $item['stop_time_cycle'],
            'traj_count' => $item['traj_count'],
        ];
    }

    /**
     * 获取原始报警信息
     *
     * @param $item
     * @param $city_id
     * @param $flowsInfo
     *
     * @return array|string
     */
    private function getRawAlarmInfo($item, $flowsInfo, $realTimeAlarmsInfo)
    {
        $alarmCategory = $this->config->item('flow_alarm_category');

        $result = [];

        if (isset($flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']])) {
            foreach ($alarmCategory as $key => $value) {
                if (array_key_exists($item['logic_flow_id'] . $key, $realTimeAlarmsInfo)) {
                    $result[] = $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] .
                        '-' . $value['name'];
                }
            }
        }

        return $result;
    }

    /**
     * 数据处理，多个 flow 记录合并到其对应 junction
     *
     * @param $target
     * @param $item
     *
     * @return mixed
     */
    private function mergeFlowInfo($target, $item)
    {
        //合并属性 停车延误加权求和，停车时间求最大，权值求和
        $target['quota']['stop_delay_weight'] += $item['quota']['stop_delay_weight'];
        $target['quota']['stop_time_cycle']   = max($target['quota']['stop_time_cycle'], $item['quota']['stop_time_cycle']);
        $target['quota']['traj_count']        += $item['quota']['traj_count'];

        if (isset($target['alarm_info'])) {
            //合并报警信息
            $target['alarm_info'] = array_merge($target['alarm_info'], $item['alarm_info']) ?? [];
        }

        return $target;
    }

    /**
     * 获取最终指标信息
     *
     * @param $item
     *
     * @return array
     */
    private function getFinalQuotaInfo($item)
    {
        //实时指标配置文件
        $realTimeQuota = $this->config->item('real_time_quota');
        return [
            'stop_delay' => [
                'name' => '平均延误',
                'value' => $realTimeQuota['stop_delay']['round']($item['quota']['stop_delay_weight'] / $item['quota']['traj_count']),
                'unit' => $realTimeQuota['stop_delay']['unit'],
            ],
            'stop_time_cycle' => [
                'name' => '最大停车次数',
                'value' => $realTimeQuota['stop_time_cycle']['round']($item['quota']['stop_time_cycle']),
                'unit' => $realTimeQuota['stop_time_cycle']['unit'],
            ],
        ];
    }

    /**
     * 获取最终报警信息
     *
     * @param $item
     *
     * @return array
     */
    private function getFinalAlarmInfo($item)
    {
        return [
            'is' => (int)!empty($item['alarm_info']),
            'comment' => $item['alarm_info'],
        ];
    }

    /**
     * 获取当前路口的状态
     *
     * @param $item
     *
     * @return array
     */
    private function getJunctionStatus($quota)
    {
        $junctionStatus = $this->config->item('junction_status');

        $junctionStatusFormula = $this->config->item('junction_status_formula');

        return $junctionStatus[$junctionStatusFormula($quota['stop_delay']['value'])];
    }

    //================以上方法全部为数据处理方法=====================//

}
