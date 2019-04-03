<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:19
 */

class Adapt_model extends CI_Model
{
    private $tb = 'adapt_timing_mirror';

    /**
     * @var \CI_DB_query_builder
     */
    protected $db;

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        $isExisted = $this->db->table_exists($this->tb);

        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     * 获取指定路口的自适应配时信息
     *
     * @param string $logicJunctionId
     * @param string $select
     *
     * @return array
     */
    public function getAdaptByJunctionId($logicJunctionId, $select = '*')
    {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('logic_junction_id', $logicJunctionId)
            ->get();
        return $res instanceof CI_DB_result ? $res->row_array() : $res;
//
//        $newData = $res instanceof CI_DB_result ? $res->row_array() : $res;
//        $newTiming = $newData["timing_info"];
//        $newTiming = json_decode($newTiming,true);
//        //TODO 改成旧格式
//        $oldData = [];
//        $oldData['tod']=[];
//        $structure = $newTiming['data']['structure'];
//        $stageMap = [];
//        if($structure == 2){//stage
//            //自适应优化只有一个tod
//            $todDetail = $newTiming['data']['schedule'][0]['tod'][0];
//            $extraTime = array(
//                "cycle" => $todDetail['cycle'],
//                "offset"=> $todDetail['offset'],
//                "tod_end_time"=> $todDetail['start_time'],
//                "tod_start_time"=> $todDetail['end_time']
//            );
//            $oldData['tod'][0]['extra_time'] = $extraTime;
//            $stageLength = [];//记录每个stage的时长
//            foreach ($todDetail['vehicle_phase'] as $k=>$vp){
//                if ($vp['all_green']==1){
//                    $stageLength[$vp['sequence_num']]=$vp['green'];
//                }else{
//                    $stageLength[$vp['sequence_num']]=$vp['green']+$vp['yellow']+$vp['red_clear'];
//                }
//            }
//            ksort($stageLength);
//            foreach ($todDetail['vehicle_phase'] as $k=>$vp){
//                //计算start_time
//                $start_time=0;
//                for($i=1;$i<$vp['sequence_num'];$i++){
//                    $start_time += $stageLength[$i];
//                }
//                $movementTiming =array(
//                    "all_red" => $vp['red_clear'],
//                    "channel"=>$vp["sg_id"],
//                    "movement_id"=>$vp["sg_id"],
//                    "flow"=> array(
//                        "comment"=>$vp["sg_name"],
//                        "logic_flow_id"=> @$vp["flow_info"][0]["logic_flow_id"],
//                        "type"=>0
//                    ),
//                    "phase_id"=> $vp["phase_num"],
//                    "phase_seq"=> $vp["phase_num"],
//                    "ring_id"=> $vp["ring_index"],
//                    "timing" =>[array(
//                        "duration"=> $vp["green"],
//                        "max"=> $vp["max_green"],
//                        "min"=> $vp["min_green"],
//                        "start_time"=> $start_time,
//                        "state"=> 1
//                    )],
//                    "yellow" => $vp["yellow"],
//                );
//                $oldData['tod'][0]['movement_timing'][] = $movementTiming;
//
//                $stageMap[$vp['sequence_num']] = array(
//                    "allred_length"=> $vp['red_clear'],
//                    "green_length"=> $vp["green"],
//                    "green_max"=> $vp["max_green"],
//                    "green_min"=> $vp["min_green"],
//                    "num"=> $vp['sequence_num'],
//                    "phase_id"=> $vp["phase_num"],
//                    "phase_seq"=> $vp["phase_num"],
//                    "ring_id"=> $vp["ring_index"],
//                    "start_time"=> $start_time,
//                    "yellow" => $vp["yellow"],
//                );
//                $stageMap[$vp['sequence_num']]['channel'][] = $vp["sg_id"];
//                $stageMap[$vp['sequence_num']]['movements'][] = array(
//                    "channel"=>$vp["sg_id"],
//                    "flow"=>array(
//                        "comment"=>$vp["sg_name"],
//                        "logic_flow_id"=>@$vp["flow_info"][0]["logic_flow_id"],
//                        "type"=>0,
//                    )
//                );
//            }
//            foreach ($stageMap as $sk => $sv){
//                $oldData['tod'][0]['stage'][] = $sv;
//            }
//        }else{//ring
//
//        }
//
//
//        $newRet = array(
//            "timing_info"=>json_encode(array(
//                'data'=>$oldData
//            ))
//        );
//        return $newRet;
    }


    /**
     * 更新自适应配时信息表
     *
     * @param $logicJunctionId
     * @param $data
     *
     * @return bool
     */
    public function updateAdapt($logicJunctionId, $data)
    {
        return $this->db->where('logic_junction_id', $logicJunctionId)
            ->update('adapt_timing_mirror', $data);
    }

    /**
     * 删除优化日志
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    /**
     * 删除优化日志
     * @param $deleteDay Y 删除几天前
     * @return bool
     */
    public function deleteAdaptLog($deleteDay="-3 day")
    {
        //筛选第1000条数据
        $result = $this->db->select('*')
            ->from('adapt_timing_log')
            ->limit(1,999)
            ->order_by("id asc")
            ->get()
            ->result_array();
        if(strtotime(end($result)["log_time"])<strtotime($deleteDay)){
            $this->db->where('id<', end($result)["id"])->delete('adapt_timing_log');
        }
        return true;
    }

    /**
     * 写入优化日志
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function insertAdaptLog($params)
    {
        $data = [
            'created_at' => date("Y-m-d H:i:s"),
        ];
        $data = array_merge($params,$data);
        return $this->db->insert('adapt_timing_log', $data);
    }


    /**
     * 获取调度数据列表
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function pageList($params)
    {
        $this->deleteAdaptLog("-3 day");    //触发删除
        if(!empty($params["trace_id"])){
            $this->db->where("trace_id",$params["trace_id"]);
        }
        if(!empty($params["dltag"])){
            $this->db->where("dltag",$params["dltag"]);
        }
        if(!empty($params["rel_id"])){
            $this->db->where("rel_id",$params["rel_id"]);
        }
        $this->db->where("type",$params["type"]);
        $this->db->from('adapt_timing_log');
        $total = $this->db->count_all_results();

        $offset = $params["per_page"] ?? 0;
        $this->db->where("type",$params["type"]);
        if(!empty($params["trace_id"])){
            $this->db->where("trace_id",$params["trace_id"]);
        }
        if(!empty($params["dltag"])){
            $this->db->where("dltag",$params["dltag"]);
        }
        if(!empty($params["rel_id"])){
            $this->db->where("rel_id",$params["rel_id"]);
        }
        $this->db->where("type",$params["type"]);
        $result = $this->db->select('*')
            ->from('adapt_timing_log')
            ->limit($params["page_size"], $offset)
            ->order_by("log_time desc,id desc")
            ->get()
            ->result_array();
        return [$total,$result];
    }
}