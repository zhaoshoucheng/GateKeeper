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
    public function isOverFlow($record){
        if(!empty($record["spillover_rate"]) && $record["spillover_rate"]>=0.2){
            return true;
        }
        return false;
    }

    /**
     * 是否过饱和
     * @param $record
     * @return bool
     */
    public function isSAT($record){
        if(empty($record["twice_stop_rate"] || $record["queue_length"] || $record["stop_delay"])){
            return false;
        }
        if($record["twice_stop_rate"]>=0.2 && $record["queue_length"]>=180 && $record["stop_delay"]>=50){
            return true;
        }
        return false;
    }

    public function updateWarning($val, $type, $date, $cityId, $traceId){
        //验证路口问题
        $logicJunctionId = $val['logic_junction_id'];
        $logicFlowId = $val['logic_flow_id'];
        $realtimeUpatetime = $val['updated_at'];
        $this->db->reconnect();
        $this->db->trans_begin();
        try {
            //判断数据是否存在?
            $warnRecord = $this->db->select("id, start_time, last_time")->from('real_time_alarm')
                ->where('logic_junction_id', $logicJunctionId)
                ->where('logic_flow_id', $logicFlowId)
                ->where('date', $date)
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
            $this->db->trans_commit();
        } catch (\Exception $e) {
            $this->db->trans_rollback();
            echo "[ERROR] " . date("Y-m-d\TH:i:s") . " trace_id=".$traceId."||junction_id=".$logicJunctionId."||flow_id=".$logicFlowId."||message=".$e->getMessage()."\n\r";
        }
        return true;
    }

    public function process($cityId, $date, $hour, $traceId)
    {
        $tableName = "real_time_" . $cityId;
        $isExisted = $this->db->table_exists($tableName);
        if (!$isExisted) {
            echo "{$tableName} not exists!\n\r";
            exit;
        }

        $currentId = 0;
        while (1) {
            $sql = "SELECT * FROM `{$tableName}` WHERE `updated_at`>\"{$date}\" and hour=\"{$hour}\" and id>{$currentId} order by id asc limit 2";
            $query = $this->db->query($sql);
            $result = $query->result_array();
            if (empty($result)) {
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=$traceId||sql=$sql||message=loop over!\n\r";
                exit;
            }
            foreach ($result as $var => $val) {
                $currentId = $val["id"];
                if($this->isOverFlow($val)){
                    $this->updateWarning($val, 1, $date, $cityId, $traceId);
                }
                if($this->isSAT($val)){
                    $this->updateWarning($val, 2, $date, $cityId, $traceId);
                }
                //sleep(10);
            }
        }
        echo "processed";
    }
}
