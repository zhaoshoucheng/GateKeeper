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

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('nconf');
        $this->load->helper('http');
        $this->token = $this->config->item('waymap_token');
        $this->userid = $this->config->item('waymap_userid');
    }

    /**
     * 是否溢流
     * @param $record
     * @return bool
     */
    public function isOverFlow($record,$rule){
        if(!isset($rule['isOverFlow']['spillover_rate']) || !isset($rule['isOverFlow']['stop_delay'])){
            return false;
        }
        if(!isset($record["spillover_rate"]) || !isset($record["stop_delay"])){
            return false;
        }
        if($record["spillover_rate"]>=$rule['isOverFlow']['spillover_rate'] && $record["stop_delay"]>=$rule['isOverFlow']['stop_delay']){
            return true;
        }
        return false;
    }

    /**
     * 是否过饱和
     * @param $record
     * @return bool
     */
    public function isSAT($record,$rule){
        if(!isset($rule['isSAT']['twice_stop_rate']) || !isset($rule['isSAT']['queue_length']) || !isset($rule['isSAT']['stop_delay'])){
            return false;
        }
        if(!isset($record["twice_stop_rate"]) || !isset($record["queue_length"]) || !isset($record["stop_delay"])){
            return false;
        }
        if($record["twice_stop_rate"]>=$rule['isSAT']['twice_stop_rate'] && $record["queue_length"]>=$rule['isSAT']['queue_length'] && $record["stop_delay"]>=$rule['isSAT']['stop_delay']){
            return true;
        }
        return false;
    }

    public function updateWarning($val, $type, $date, $cityId, $traceId){
        //验证路口问题
        $logicJunctionId = $val['logic_junction_id'];
        $logicFlowId = $val['logic_flow_id'];
        $realtimeUpatetime = $val['updated_at'];
        //$this->db->reconnect();
        //$this->db->trans_begin();
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
                $data = array(
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
                );
                $this->db->insert('real_time_alarm', $data);
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=".$traceId."||junction_id=".$logicJunctionId."||flow_id=".$logicFlowId."||message=insert\n\r";
            } else {
                //判断warning表的最后一次更新时间点与实时表数据更新时间差是否小于10分钟?
                $diffTime = strtotime($realtimeUpatetime) - strtotime($warningLastTime);

                //小于等于0时,代表重复执行脚本,不执行操作
                if($diffTime<=0){
                    throw new \Exception("repeat_process");
                }

                //大于10分钟时, 代表非持续报警, 更新start_time
                if ($diffTime > 600) {
                    $this->db->set('start_time', $realtimeUpatetime);
                }
                $this->db->set('count', 'count+1', FALSE);
                $this->db->set('updated_at', date("Y-m-d H:i:s"));
                $this->db->set('last_time', $realtimeUpatetime);
                $this->db->where('id', $warningId);
                $this->db->update('real_time_alarm');
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=".$traceId."||junction_id=".$logicJunctionId."||flow_id=".$logicFlowId."||message=update\n\r";
            }
            //$this->db->trans_commit();
        } catch (\Exception $e) {
            //$this->db->trans_rollback();
            echo "[ERROR] " . date("Y-m-d\TH:i:s") . " trace_id=".$traceId."||junction_id=".$logicJunctionId."||flow_id=".$logicFlowId."||message=".$e->getMessage()."\n\r";
            com_log_warning('_realtimewarning_updatewarning_error', 0, $e->getMessage(), compact("val", "type", "date", "cityId", "traceId"));
        }
        return true;
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
            $query = $this->db->query($sql);
            $result = $query->result_array();
            if (empty($result)) {
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=$traceId||sql=$sql||message=loop over!\n\r";
                break;
            }
            foreach ($result as $var => $val) {
                $currentId = $val["id"];
                if($this->isOverFlow($val,$rtwRule)){
                    $this->updateWarning($val, 1, $date, $cityId, $traceId);
                }
                if($this->isSAT($val,$rtwRule)){
                    $this->updateWarning($val, 2, $date, $cityId, $traceId);
                }
                //sleep(10);
            }
        }
    }

    /**
     * 指标计算
     * @param $cityId
     * @param $date
     * @param $hour
     * @param $traceId
     */
    public function calculate($cityId, $date, $hour, $traceId)
    {
        $rtwRule = $this->config->item('realtimewarning_rule');
        $rtwRule = empty($rtwRule[$cityId]) ? $rtwRule['default'] : $rtwRule[$cityId];
        $tableName = "real_time_" . $cityId;
        $isExisted = $this->db->table_exists($tableName);
        if (!$isExisted) {
            echo "{$tableName} not exists!\n\r";
            exit;
        }

        //todo生成rediskey
        $this->load->model('redis_model');
        $redisKey = "its_realtime_lasthour_$cityId";
        $this->redis_model->setEx($redisKey, $hour, 24*3600);

        //生成 avg(stop_delay) group by hour
        $splitHour = explode(':',$hour);
        $limitMinus = [5,6,7];  //只在分钟级的5-7之间执行
        if(isset($splitHour[1][1]) && in_array($splitHour[1][1],$limitMinus)){
            $sql = " SELECT `hour`, sum(stop_delay * traj_count) / sum(traj_count) as avg_stop_delay FROM `{$tableName}` WHERE `updated_at` >= '{$date} 00:00:00' AND `updated_at` <= '{$date} 23:59:59' GROUP BY `hour`";
            $query = $this->db->query($sql);
            $result = $query->result_array();
            if (empty($result)) {
                echo "生成 avg(stop_delay) group by hour failed!\n\r{$cityId} {$date} {$hour}\n\r";
                exit;
            }
            $avgStopDelayKey = "its_realtime_avg_stop_delay_{$cityId}_{$date}";
            $this->redis_model->setEx($avgStopDelayKey, json_encode($result), 24*3600);
        }

        //缓存 指定 hour 实时指标全部信息
        // key = its_realtime_junction_list_{$cityId}_{$date}_{$hour}

        $result = [];
        $offset = 0;
        $value = 100;

        while (true) {
            $data = $this->db->select('*')
                ->from($tableName)
                ->where('hour', $hour)
                ->where('traj_count >=', 10)
                ->where('updated_at >=', $date . ' 00:00:00')
                ->where('updated_at <=', $date . ' 23:59:59')
                ->limit($value, $offset)
                ->get()->result_array();

            if(empty($data)) {
                break;
            }
            $offset+=100;
            $result = array_merge($result, $data);
        }

        $junctionListKey = "its_realtime_junction_list_{$cityId}_{$date}_{$hour}";
        $this->redis_model->setEx($junctionListKey, json_encode($result), 3 * 60);
    }
}
