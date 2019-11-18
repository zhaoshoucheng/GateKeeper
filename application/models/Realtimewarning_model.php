<?php

/********************************************
 * # desc:    实时报警model
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-07-30
 ********************************************/

/**
 * Class Realtimewarning_model
 *
 * @property \Realtime_model $realtime_model
 * @property \Waymap_model   $waymap_model
 * @property \Alarmanalysis_model $alarmanalysis_model
 * @property \Userperm_model $userperm_model
 * @property \Adapt_model $adapt_model
 */
class Realtimewarning_model extends CI_Model
{
    protected $token;
    protected $userid = '';
    protected $cityQuotaList = [];

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

        $this->token = $this->config->item('waymap_token');
        $this->userid = $this->config->item('waymap_userid');

        $this->config->load('realtime_conf');
        $this->load->model('waymap_model');
        $this->load->model('alarmanalysis_model');
        $this->load->model('realtime_model');
        $this->load->model('userperm_model');
        $this->load->model('adapt_model');
    }

    public function process($cityId, $date, $hour, $traceId)
    {
        $rtwRule = $this->config->item('realtimewarning_rule');
        $rtwRule = empty($rtwRule[$cityId]) ? $rtwRule['default'] : $rtwRule[$cityId];
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
        $splitHour = explode(':', $hour);
        $limitMinus = [3, 4, 5];                          //只在分钟级的0-2之间执行
        if (isset($splitHour[0]) &&
            $splitHour[0] == '00' &&                     //小时
            isset($splitHour[1][1]) &&
            $splitHour[1][0] == '0' &&                    //分钟第一位
            in_array($splitHour[1][1], $limitMinus)
        ) {   //分钟第二位
            $dtime = date("Y-m-d H:i:s", strtotime("-30 day"));
            $sql = "DELETE FROM `real_time_alarm` WHERE `created_at`<'{$dtime}';";
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
        $logicJunctionId = $val['logic_junction_id'];
        $logicFlowId = $val['logic_flow_id'];
        $realtimeUpatetime = $val['updated_at'];
        $this->db->reconnect();
        $this->db->trans_begin();
        try {
            //判断数据是否存在?
            $warnRecord = $this->db->select("id, start_time, last_time")->from('real_time_alarm')
                ->where('date', $date)
                ->where('logic_flow_id', $logicFlowId)
                ->where('type', $type)
                ->where('deleted_at', "1970-01-01 00:00:00")
                ->get()->result_array();
            $warningId = !empty($warnRecord[0]['id']) ? $warnRecord[0]['id'] : 0;
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

    public function groupAvgStopDelayKey($cityId, $date, $hour, $groupId)
    {
        $cityIds = $this->userperm_model->getCityidByGroup($groupId);
        $junctionIds = $this->userperm_model->getJunctionidByGroup($groupId, $cityId);

        //有城市权限则路口数据为空
        if (in_array($cityId, $cityIds)) {
            $junctionIds = [];
        }
        $avgStopDelayList = $this->realtime_model->avgStopdelay($cityId, $date, $hour, $junctionIds);
        if (empty($avgStopDelayList)) {
            echo "生成 usergroup avg(stop_delay) group by hour failed!\n\r{$cityId} {$date} {$hour}\n\r";
            exit;
        }

        //缓存数据
        $avgStopDelayKey = "new_its_usergroup_realtime_avg_stop_delay_{$groupId}_{$cityId}_{$date}";
        $esStopDelay = $this->redis_model->getData($avgStopDelayKey);
        if (!empty($esStopDelay)) {
            $esStopDelay = json_decode($esStopDelay, true);
        }
        $esStopDelay[] = $avgStopDelayList;
        $this->redis_model->setEx($avgStopDelayKey, json_encode($esStopDelay), 24 * 3600);
    }

    /**
     * 指标计算
     *
     * @param $cityId   int     Y   城市Id
     * @param $date     string  Y   日期    格式y-m-d
     * @param $hour     string  Y   批次号  格式H:i:s
     * @param $traceId  string  Y   traceid
     * @param $type     string  Y   计算类型 0=全部计算 1=只计算平均延误和记录指标批次号
     */
    public function calculate($cityId, $date, $hour, $traceId, $ctype = 0)
    {
        //验证数据表是否存在?
        $rtwRule = $this->config->item('realtimewarning_rule');
        $rtwRule = empty($rtwRule[$cityId]) ? $rtwRule['default'] : $rtwRule[$cityId];
        $this->load->model('redis_model'); 

        //设置rediskey
        $avgStopDelayKey = "new_its_realtime_avg_stop_delay_{$cityId}_{$date}";
        $junctionSurveyKey = "new_its_realtime_pretreat_junction_survey_{$cityId}_{$date}_{$hour}";
        $todayJamCurveKey = "new_its_realtime_today_jam_curve_{$cityId}_{$date}";
        $junctionListKey = "new_its_realtime_pretreat_junction_list_{$cityId}_{$date}_{$hour}";
        $lastHourKey = "new_its_realtime_lasthour_{$cityId}";
        $lastScheduleKey = "new_its_schedule_lasthour_{$cityId}";
        $realTimeAlarmRedisKey = "new_its_realtime_alarm_{$cityId}";
        $realTimeAlarmBakKey = "new_its_realtime_alarm_{$cityId}_{$date}_{$hour}";
        //高优先级设置
        $this->redis_model->setEx($lastScheduleKey, $hour, 24 * 3600);

        //平均延误曲线数据
        //每次只取一个批次进行追加缓存。
        $avgStopDelayList = $this->realtime_model->avgStopdelay($cityId, $date, $hour);
        if (empty($avgStopDelayList)) {
            $message= "[INFO] " . date("Y-m-d\TH:i:s") . " city_id={$cityId}||date={$date}||hour={$hour}||traceId={$traceId}||didi_trace_id=" . get_traceid() . "||message=生成平均延误曲线数据异常退出 avg(stop_delay) group by hour failed!\n\r";
            $this->adapt_model->insertAdaptLog(["type"=>4, "rel_id"=>$cityId, "log"=>$message, "trace_id"=>$traceId, "dltag"=>"calculate.avgStopdelay", "log_time"=>date("Y-m-d H:i:s"),]);
            echo $message;
            return;
        }
        $esStopDelay = $this->redis_model->getData($avgStopDelayKey);
        if (!empty($esStopDelay)) {
            $esStopDelay = json_decode($esStopDelay, true);
        }
        $esStopDelay[] = $avgStopDelayList;

        $realtimeJunctionList = $realTimeAlarmsInfoResult = [];
        if ($ctype == 0) {
            //获取实时数据延迟缓冲
            sleep(10);
            //这里可能对南昌数据再做特殊处理

            //获取实时指标数据
            $realtimeJunctionList = $this->realtime_model->getRealTimeJunctions($cityId, $date, $hour);
            $message = "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour={$hour}" . "||realtimeJunctionCount=" . count($realtimeJunctionList) . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=calculate.getRealTimeIndex\n\r";
            $this->adapt_model->insertAdaptLog(["type"=>4, "rel_id"=>$cityId, "log"=>$message, "trace_id"=>$traceId, "dltag"=>"calculate.getRealTimeIndex", "log_time"=>date("Y-m-d H:i:s"),]);
            echo $message;
            
            /**
             * 计算路口总数
             * 为什么拿原始数据来计算，是因为如果处理后再统计，因为有的路口不在路网，
             * 会导致丢失，这样就和拥堵概览的路口总数匹配不上了  
             */
            $countData = array_column($realtimeJunctionList, 'traj_count', 'logic_junction_id');
            $junctionTotal = count($countData);

            //junctionTotal针对个别城市取近7日离线排重路口数
            $redisKey = sprintf("itstool_offline_juncnum_%s",$cityId);
            $offlineJuncNum = $this->redis_model->getData($redisKey);
            if(!empty($offlineJuncNum) && $offlineJuncNum>200){
                $junctionTotal = $offlineJuncNum;
            }
            $realTimeAlarmsInfoResultOrigal = $this->alarmanalysis_model->getRealTimeAlarmsInfoFromEs($cityId, $date, $hour);
            com_log_notice('getRealTimeAlarmsInfoFromEs_Count', ["count"=>count($realTimeAlarmsInfoResultOrigal),"cityId"=>$cityId,"date"=>$date,"hour"=>$hour,]);
            $message= "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour={$hour}" . "||alarm_movement_count=" . count($realTimeAlarmsInfoResultOrigal) . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=calculate.getRealTimeAlarms||grep_message=grep '_com_http_success' /home/xiaoju/php7/logs/cloud/itstool/didi.log | grep '_search' | grep '{$hour}' | grep 'city_id\":{\"query\":{$cityId},'\n\r";
            $this->adapt_model->insertAdaptLog(["type"=>4, "rel_id"=>$cityId, "log"=>$message, "trace_id"=>$traceId, "dltag"=>"calculate.getRealTimeAlarms", "log_time"=>date("Y-m-d H:i:s"),]);
            echo $message;

            /**
             * 聚合路口数据
             * (路网数据、实时指标、报警数据) 路口列表合并报警信息
             *
             * 轨迹数大于5的报警路口
             * 过滤济南持续时间<3分钟数据
             */
            $cityQuotaList = $this->getCityQuotaList($cityId,$date,$hour,10000);
            $junctionList = $this->getJunctionListResult($cityId, $realtimeJunctionList, $realTimeAlarmsInfoResultOrigal, $cityQuotaList, $hour);
            $jDataList = $junctionList['dataList'] ?? [];
            $message= "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour={$hour}" . "||jDataListCount=" . count($jDataList) . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=calculate.getJunctionAggResult\n\r";
            $this->adapt_model->insertAdaptLog(["type"=>4, "rel_id"=>$cityId, "log"=>$message, "trace_id"=>$traceId, "dltag"=>"calculate.getJunctionAggResult", "log_time"=>date("Y-m-d H:i:s"),]);
            echo $message;
            
            /**
             * 路口概览 确认和路口列表数据一致
             */
            $result = [];
            $result['junction_total'] = $junctionTotal;
            $result['alarm_total'] = 0;
            $result['congestion_total'] = 0;
            $result['amble_total'] = 0;
            $alarmJunctionIDS = [];
            $alarmFlowTypes = [];
            foreach ($jDataList as $datum) {
                // 报警数
                $result['alarm_total'] += $datum['alarm']['is'] ?? 0;
                // 拥堵数
                $result['congestion_total'] += (int)(($datum['status']['key'] ?? 0) == 3);
                // 缓行数
                $result['amble_total'] += (int)(($datum['status']['key'] ?? 0) == 2);
                if(isset($datum['alarm']['is']) && $datum['alarm']['is']){
                    $alarmJunctionIDS[] = $datum["jid"];
                }
                if(isset($datum['alarm_flow_type'])){
                    foreach ($datum['alarm_flow_type'] as $val) {
                        $alarmFlowTypes[] = $val["flow_id"].$val["cate"];
                    }
                } 
            }
            $junctionSurvey = $result;

            /**
             * 实时报警数据过滤
             */
            $realTimeAlarmsInfoResult = [];
            foreach ($realTimeAlarmsInfoResultOrigal as $item) {
                if (!in_array($item['logic_flow_id'].$item['type'],$alarmFlowTypes)){
                    continue;
                }
                $realTimeAlarmsInfoResult[$item['logic_flow_id'] . $item['type']] = $item;
            }
            $realTimeAlarmsInfoResult = array_values($realTimeAlarmsInfoResult);
            $message= "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||hour={$hour}" . "||realTimeAlarmsInfoResultCount=" . count($realTimeAlarmsInfoResult) . "||trace_id=" . $traceId . "||didi_trace_id=" . get_traceid() . "||message=calculate.realTimeAlarmsResult_Count\n\r";
            $this->adapt_model->insertAdaptLog(["type"=>4, "rel_id"=>$cityId, "log"=>$message, "trace_id"=>$traceId, "dltag"=>"calculate.realTimeAlarmsResult_Count", "log_time"=>date("Y-m-d H:i:s"),]);
            echo $message;
            //<========计算缓存数据end==========
        }

        //写入分组数据
        $groupIds = $this->userperm_model->getUserPermAllGroupid();
        if (empty($groupIds)) {
            com_log_warning("getUserPermAllGroupid_Empty", 0, "", array("groupids" => $groupIds));
        }
        foreach ($groupIds as $groupId) {
            $this->dealGroupData($cityId, $date, $hour, $traceId, $groupId, $realtimeJunctionList, $realTimeAlarmsInfoResult, $esStopDelay, $ctype);
        }

        // 写入缓存数据
        // 平均延误数据
        // 最新指标批次的hour
        if ($ctype == 1) {
            $this->redis_model->setEx($avgStopDelayKey, json_encode($esStopDelay), 6 * 3600);
        } elseif ($ctype == 0) {
            // 路口概览数据
            $this->redis_model->setEx($junctionSurveyKey, json_encode($junctionSurvey), 6 * 3600);
            // 当日拥堵概览曲线 
            $todayJamCurve = [];
            $todayJamCurveData = $this->redis_model->getData($todayJamCurveKey);
            if(!empty($todayJamCurveData)){
                $todayJamCurve = json_decode($todayJamCurveData,true);
                $todayJamCurve[$hour] = $junctionSurvey;
            }
            $todayJamCurve[$hour] = $junctionSurvey;
            $this->redis_model->setEx($todayJamCurveKey, json_encode($todayJamCurve), 60 * 24 * 3600);
            // 缓存诊断路口列表数据
            $this->redis_model->setEx($junctionListKey, json_encode($junctionList), 6 * 3600);
            // 缓存实时报警路口数据
            $this->redis_model->setEx($realTimeAlarmRedisKey, json_encode($realTimeAlarmsInfoResult), 6 * 3600);
            // 冗余缓存实时报警路口数据,每一个批次一份
            $this->redis_model->setEx($realTimeAlarmBakKey, json_encode($realTimeAlarmsInfoResult), 6 * 3600); 


            // 缓存最新hour
            sleep(0.5);
            if ($this->redis_model->getData($junctionSurveyKey) && $this->redis_model->getData($junctionListKey)
                && $this->redis_model->getData($realTimeAlarmRedisKey) && $this->redis_model->getData($realTimeAlarmBakKey)
            ) {
                $this->redis_model->setEx($lastHourKey, $hour, 24 * 3600);
            } else {
                sleep(2);
                $this->redis_model->setEx($lastHourKey, $hour, 24 * 3600);
            }
        }
    }

    public function dealGroupData($cityId, $date, $hour, $traceId, $groupId, $realtimeJunctionListOri, $realTimeAlarmsInfoResultOri, $esStopDelayOri, $ctype = 0)
    {
        $cityIds = $this->userperm_model->getCityidByGroup($groupId);
        $junctionIds = $this->userperm_model->getJunctionidByGroup($groupId, $cityId);

        $message= "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||cityIds=" . implode(",", $cityIds) . "||group_id=" . $groupId . "||junctionIdNum=" . count($junctionIds) . "||trace_id=" . $traceId . "||message=dealGroupData.start\n\r";
        $this->adapt_model->insertAdaptLog(["type"=>4, "rel_id"=>$cityId, "log"=>$message, "trace_id"=>$traceId, "dltag"=>"dealGroupData.start", "log_time"=>date("Y-m-d H:i:s"),]);
        echo $message;

        //有城市权限则路口数据为空
        if (in_array($cityId, $cityIds)) {
            $junctionIds = [];
        }

        //设置rediskey
        $avgStopDelayKey = "new_its_usergroup_realtime_avg_stop_delay_{$groupId}_{$cityId}_{$date}";
        $junctionSurveyKey = "new_its_usergroup_realtime_pretreat_junction_survey_{$groupId}_{$cityId}_{$date}_{$hour}";
        $todayJamCurveKey = "new_its_usergroup_realtime_today_jam_curve_{$cityId}_{$date}";
        $junctionListKey = "new_its_usergroup_realtime_pretreat_junction_list_{$groupId}_{$cityId}_{$date}_{$hour}";
        $realTimeAlarmRedisKey = "new_its_usergroup_realtime_alarm_{$groupId}_{$cityId}";
        $realTimeAlarmBakKey = "new_its_usergroup_realtime_alarm_{$groupId}_{$date}_{$hour}";

        //生成平均延误曲线数据
        //因为ES直接查询当天所有批次会影响到集群（真弱鸡！）所有要每次只取一个批次进行追加缓存。
        $avgStopDelayList = $this->realtime_model->avgStopdelayByJunctionId($cityId, $date, $hour, $junctionIds);
        if (empty($avgStopDelayList)) {
            $message= "[INFO] " . date("Y-m-d\TH:i:s") . " city_id=" . $cityId . "||cityIds=" . implode(",", $cityIds) . "||group_id=" . $groupId . "||junctionIdNum=" . count($junctionIds) . "||trace_id=" . $traceId . "||message=生成 usergroup avg(stop_delay) group by hour failed!\n\r";
            $this->adapt_model->insertAdaptLog(["type"=>4, "rel_id"=>$cityId, "log"=>$message, "trace_id"=>$traceId, "dltag"=>"dealGroupData.avgStopdelayByJunctionId", "log_time"=>date("Y-m-d H:i:s"),]);
            echo $message;
            $avgStopDelayList = [];
        }
        $esStopDelay = $this->redis_model->getData($avgStopDelayKey);
        if (!empty($esStopDelay)) {
            $esStopDelay = json_decode($esStopDelay, true);
        }
        //没有数据权限
        if (!in_array($cityId, $cityIds) && empty($junctionIds)) {
            $avgStopDelayList = [];
        }
        if (!empty($avgStopDelayList)) {
            $esStopDelay[] = $avgStopDelayList;
        }
        if (in_array($cityId, $cityIds)) {
            $esStopDelay = $esStopDelayOri;
        }

        if ($ctype == 0) {
            //过滤实时指标数据
            $realtimeJunctionList = [];
            foreach ($realtimeJunctionListOri as $k => $realtimeJunctionItem) {
                if (in_array($realtimeJunctionItem["logic_junction_id"], $junctionIds) || in_array($cityId, $cityIds)) {
                    $realtimeJunctionList[$k] = $realtimeJunctionItem;
                }
            }
            $countData = array_column($realtimeJunctionList, 'traj_count', 'logic_junction_id');
            $junctionTotal = count($countData);

            //junctionTotal针对个别城市取近7日离线排重路口数
            $redisKey = sprintf("itstool_offline_juncnum_%s",$cityId);
            $offlineJuncNum = $this->redis_model->getData($redisKey);
            if(!empty($offlineJuncNum) && $offlineJuncNum>200){
                $junctionTotal = $offlineJuncNum;
            }

            //过滤实时报警表数据
            $realTimeAlarmsInfoResult = [];
            foreach ($realTimeAlarmsInfoResultOri as $k => $rtItem) {
                if (in_array($rtItem["logic_junction_id"], $junctionIds) || in_array($cityId, $cityIds)) {
                    $realTimeAlarmsInfoResult[$k] = $rtItem;
                }
            }
            $realTimeAlarmsInfoResult = array_values($realTimeAlarmsInfoResult);


            //聚合路口数据(路网数据、实时指标、报警数据)
            $realTimeAlarmsInfo = [];
            foreach ($realTimeAlarmsInfoResult as $item) {
                $realTimeAlarmsInfo[$item['logic_flow_id'] . $item['type']] = $item;
            }
            $cityQuotaList = $this->getCityQuotaList($cityId,$date,$hour,10000);
            $junctionList = $this->getJunctionListResult($cityId, $realtimeJunctionList, $realTimeAlarmsInfo, $cityQuotaList, $hour);


            //计算junctionSurvey 数据
            $jDataList = $junctionList['dataList'] ?? [];
            $result = [];
            $result['junction_total'] = $junctionTotal;
            $result['alarm_total'] = 0;
            $result['congestion_total'] = 0;
            $result['amble_total'] = 0;
            foreach ($jDataList as $datum) {
                // 报警数
                $result['alarm_total'] += $datum['alarm']['is'] ?? 0;
                // 拥堵数
                $result['congestion_total'] += (int)(($datum['status']['key'] ?? 0) == 3);
                // 缓行数
                $result['amble_total'] += (int)(($datum['status']['key'] ?? 0) == 2);
            }
            $junctionSurvey = $result;
        }

        // 平均延误数据
        if ($ctype == 1) {
            $this->redis_model->setEx($avgStopDelayKey, json_encode($esStopDelay), 6 * 3600);
        } elseif ($ctype == 0) {
            // 路口概览数据
            $this->redis_model->setEx($junctionSurveyKey, json_encode($junctionSurvey), 6 * 3600);
            // 当日拥堵概览曲线
            $todayJamCurve = [];
            $todayJamCurveData = $this->redis_model->getData($todayJamCurveKey);
            if(!empty($todayJamCurveData)){
                $todayJamCurve = json_decode($todayJamCurveData,true);
                $todayJamCurve[$hour] = $junctionSurvey;
            }
            $todayJamCurve[$hour] = $junctionSurvey;
            $this->redis_model->setEx($todayJamCurveKey, json_encode($todayJamCurve), 60 * 24 * 3600);
            // 缓存诊断路口列表数据
            $this->redis_model->setEx($junctionListKey, json_encode($junctionList), 6 * 3600);
            // 缓存实时报警路口数据
            $this->redis_model->setEx($realTimeAlarmRedisKey, json_encode($realTimeAlarmsInfoResult), 6 * 3600);
            // 冗余缓存实时报警路口数据,每一个批次一份
            $this->redis_model->setEx($realTimeAlarmBakKey, json_encode($realTimeAlarmsInfoResult), 6 * 3600);
        }
    }


    //================以下方法全部为数据处理方法=====================//
    /**
     * 处理从数据库中取出的原始数据并返回
     *
     * @param $cityId
     * @param $realtimeJunctionList 指标数据
     * @param $realTimeAlarmsInfoResultOrigal   报警数据
     * @param $cityQuotaList   城市指标数据
     *
     * @return array
     */
    private function getJunctionListResult($cityId, $realtimeJunctionList, $realTimeAlarmsInfoResultOrigal, $cityQuotaList, $hour)
    {
        $realTimeAlarmsInfo = [];
        //基于报警数据的过滤
        foreach ($realTimeAlarmsInfoResultOrigal as $item) {
            //过滤济南<3分钟数据
            if ($cityId == "12" && strtotime($item["last_time"]) - strtotime($item["start_time"]) < 180) {
                continue;
            }

            //过滤南昌70路口
            $ncFilterJuncIDS = $this->config->item('nanchang_filter_junction_ids');
            if($cityId=="38" && !in_array($item['logic_junction_id'], $ncFilterJuncIDS)){
                continue;
            }
            $realTimeAlarmsInfo[$item['logic_flow_id'] . $item['type']] = $item;
        }
        // print_r($realTimeAlarmsInfo);exit;
        //获取路口信息的自定义返回格式
        $junctionsInfo = $this->waymap_model->getAllCityJunctions($cityId, 0);
        $junctionsInfo = array_column($junctionsInfo, null, 'logic_junction_id');

        //获取需要报警的全部路口ID
        $alarmJunctionIdArr = array_unique(array_column($realTimeAlarmsInfo, 'logic_junction_id'));
        asort($alarmJunctionIdArr);
        
        //获取需要报警的全部路口的全部方向的信息
        $flowsInfo = [];
        try {
            $chunkJunctions = array_chunk($alarmJunctionIdArr,100);
            foreach ($chunkJunctions as $partJuncs) {
                $alarmJunctionIDs = implode(',', $partJuncs);
                $tmpInfo = $this->waymap_model->getFlowsInfo($alarmJunctionIDs,false);
                $flowsInfo = array_merge($flowsInfo,$tmpInfo);
            }
        } catch (\Exception $e) {
            $flowsInfo = [];
        }

        //数组初步处理，去除无用数据
        //基于指标数据的过滤
        $newRealtimeFlowList = [];
        foreach ($realtimeJunctionList as $item) {
            $logicFlowID = $item['logic_flow_id'];
            $logicJunctionID = $item['logic_junction_id'];
            $trajNum = 5;
            if($cityId==175){
                $trajNum = 1;
            }

            $alarmCategory = $this->config->item('flow_alarm_category');
            $alarmInfo = [];
            $alarmFlowType = [];
            $flowList = [];

            //一个flow对应的多个报警问题
            if (isset($flowsInfo[$logicJunctionID][$logicFlowID])) {
                foreach ($alarmCategory as $key => $value) {
                    $alarmKey = $logicFlowID . $key;
                    if(isset($realTimeAlarmsInfo[$alarmKey])){
                        $comment = $flowsInfo[$logicJunctionID][$logicFlowID] .'-' . $value['name'];
                        $alarmInfo[] = $comment; 
                        $alarmFlowType[] = ["flow_id"=>$logicFlowID, "cate"=>$key]; 
                    }
                }
            }
            //轨迹数大于某值 或者 报警数据不为空
            if ($item['traj_count'] >= $trajNum || !empty($alarmInfo)) {
                $newRealtimeFlowList[] = [
                    "logic_junction_id"=>$logicJunctionID,
                    "logic_flow_id"=>$logicFlowID,
                    "alarm_flow_type"=>$alarmFlowType,
                    "alarm_info"=>$alarmInfo,
                ];
            }
        }

        //路口数据聚合
        $newRealtimeJunctionList = [];
        foreach ($newRealtimeFlowList as $item) {
            $logicFlowID = $item['logic_flow_id'];
            $logicJunctionID = $item['logic_junction_id'];
            $alarmInfo = [];
            $alarmFlowType = [];
            $flowList = [];
            if(isset($newRealtimeJunctionList[$logicJunctionID])){
                $alarmInfo = $newRealtimeJunctionList[$logicJunctionID]['alarm_info'];
                $flowList = $newRealtimeJunctionList[$logicJunctionID]['flow_ids'];
                $alarmFlowType = $newRealtimeJunctionList[$logicJunctionID]['alarm_flow_type'];
            }
            $flowList[] = $item["logic_flow_id"];
            $alarmInfo = array_merge($alarmInfo, $item["alarm_info"]);
            $alarmFlowType = array_merge($alarmFlowType, $item["alarm_flow_type"]);
            // $quota = $this->getFinalQuotaInfo($item, $cityQuotaList);
            $newRealtimeJunctionList[$logicJunctionID] = [
                'logic_junction_id' => $logicJunctionID,
                'flow_ids' => $flowList,
                'alarm_flow_type' => $alarmFlowType,
                'alarm_info' => $alarmInfo,
            ];
        }

        $dataList = [];
        foreach ($newRealtimeJunctionList as $item) {
            $logicJunctionID = $item['logic_junction_id'];
            $alarmInfo = $item["alarm_info"];
            $alarmFlowType = $item["alarm_flow_type"];
            $flowList = $item["flow_ids"];
            $quota = $this->getFinalQuotaInfo($item, $cityQuotaList);
            $dataList[$logicJunctionID] = [
                'jid' => $logicJunctionID,
                'flow_ids' => $flowList,
                'name' => $junctionsInfo[$logicJunctionID]['name'] ?? '',
                'lng' => $junctionsInfo[$logicJunctionID]['lng'] ?? '',
                'lat' => $junctionsInfo[$logicJunctionID]['lat'] ?? '',
                'quota' => $quota,
                'alarm_flow_type' => $alarmFlowType,
                'alarm' => [
                    'is' => (int)!empty($alarmInfo),
                    'comment' => $alarmInfo,
                ],
                'status' => $this->getJunctionStatus($quota,$cityId),
            ];
        }
        $lngs = array_filter(array_column($dataList, 'lng'));
        $lats = array_filter(array_column($dataList, 'lat'));
        $center['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
        $center['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));
        return [
            'dataList' => array_values($dataList),
            'center' => $center,
            'hour' => $hour,
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
                    $comment = $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] .
                        '-' . $value['name'];
                    $result[] = ["comment"=>$comment,"flow_id"=>$item['logic_flow_id'],]; 
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
        if (isset($target['alarm_info'])) {
            $target['alarm_info'] = array_merge($target['alarm_info'], $item['alarm_info']) ?? [];
        }
        return $target;
    }

    /**
     * 获取全城路口聚合指标
     * @param $cityID
     * @param $date
     * @param $timePoint
     * @param $limit
     * @return array
     */
    private function getCityQuotaList($cityID,$date,$timePoint,$limit){
        $params = [
            "city_id"=>$cityID,
            "quota_key"=>"stop_delay",
            "date"=>$date, 
            "time_point"=>$timePoint,
            "limit"=>$limit,
        ];
        if(!isset($this->cityQuotaList["stop_delay"])){
            $params["quota_key"] = "stop_delay";
            $sortList = $this->realtime_model->getJunctionQuotaSortList($params);
            $sortList = $sortList["dataList"];
            $sortList = array_column($sortList,null,"logic_junction_id");
            $this->cityQuotaList["stop_delay"] = $sortList;

            $params["quota_key"] = "stop_time_cycle";
            $sortList = $this->realtime_model->getJunctionQuotaSortList($params);
            $sortList = $sortList["dataList"];
            $sortList = array_column($sortList,null,"logic_junction_id");
            $this->cityQuotaList["stop_time_cycle"] = $sortList;
        }
        return $this->cityQuotaList;
    }

    /**
     * 获取最终指标信息
     *
     * @param $item
     *
     * @return array
     */
    private function getFinalQuotaInfo($item,$cityQuotaList)
    {
        //实时指标配置文件
        $realTimeQuota = $this->config->item('real_time_quota');
        $junctionID = $item["logic_junction_id"];
        $stopDelayQuota = $this->cityQuotaList["stop_delay"][$junctionID]["quota_value"] ?? 0;
        $stopTimeCycle = $this->cityQuotaList["stop_time_cycle"][$junctionID]["quota_value"] ?? 0;
        return [
            'stop_delay' => [
                'name' => '平均延误',
                'value' => $realTimeQuota['stop_delay']['round']($stopDelayQuota),
                'unit' => $realTimeQuota['stop_delay']['unit'],
            ],
            'stop_time_cycle' => [
                'name' => '最大停车次数',
                'value' => $realTimeQuota['stop_time_cycle']['round']($stopTimeCycle),
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
    function getJunctionStatus($quota,$cityId)
    {
        $junctionStatus = $this->config->item('junction_status');
        $junctionStatusFormula = $this->config->item('junction_status_formula');
        //这里从db中读取信息
        $res = $this->db->select("*")
        ->from("optimized_parameter_config_limits")
        ->where('city_id', $cityId)
        ->order_by('id', 'DESC')
        ->get();
        $limit = $res instanceof CI_DB_result ? $res->row_array() : $res;
        if(!empty($limit)){
            $junctionStatusFormula = function ($val) use($limit){
                if ($val >= $limit["congestion_level_lower_limit"]) {
                    return 3; // 拥堵
                } elseif ($val < $limit["congestion_level_lower_limit"] && $val >= $limit["slow_down_level_lower_limit"]) {
                    return 2; // 缓行
                } else {
                    return 1; // 畅通
                }
            };
        }
        return $junctionStatus[$junctionStatusFormula($quota['stop_delay']['value'])];
    }

    //================以上方法全部为数据处理方法=====================//

}
