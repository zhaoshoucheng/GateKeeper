<?php

/********************************************
 * # desc:    实时报警model
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-07-31
 ********************************************/
class Schedule_model extends CI_Model
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

    //检查是否存在区域配时任务?
    public function process($cityId, $traceId){
        $params = ['city_id'=>$cityId];
        $areaJson = httpPOST('http://10.95.100.106:8088/welcome/getAreaList', $params);
        $areaArr = json_decode($areaJson, true);
        $areaList = !empty($areaArr["data"]["areaList"]) ? $areaArr["data"]["areaList"] : [];
        foreach ($areaList as $var=>$val){
            $task = $this->db->from('schedule_task')
                ->where('city_id', $cityId)
                ->where('task_id', $val["area_id"])
                ->where('task_type', 1)
                ->where('delete_time', "1970-01-01 00:00:00")
                ->get()->result_array();
            if(empty($task)){
                //insert
                $data = array(
                    'city_id' => $cityId,
                    'task_id' => $val["area_id"],
                    'task_type' => 1,
                    'last_status' => 0,
                    'last_result' => '',
                    'last_create_time' => '1970-01-01 00:00:00',
                    'delete_time' => '1970-01-01 00:00:00',
                    'create_time' => date("Y-m-d H:i:s"),
                    'update_time' => date("Y-m-d H:i:s"),
                );
                $this->db->insert('schedule_task', $data);
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=".$traceId."||area_id=".$val["area_id"]."||message=insert\n\r";
            }else{
                //nothing
                echo "[INFO] " . date("Y-m-d\TH:i:s") . " trace_id=".$traceId."||area_id=".$val["area_id"]."||message=nothing\n\r";
            }
        }
    }
}
