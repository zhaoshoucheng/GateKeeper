<?php
class OperateLog_model extends CI_Model
{
    private $tb = 'user_operate_log';
    private $db;
    private $urlMap;

    public function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $isExisted = $this->db->table_exists($this->tb);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }

        $this->urlMap = [
            //路口管理
            "junction/saveJunctionName"=>["修改路口名称","路口管理"],
            "Road/addRoad"=>["新增干线","路口管理"],
            "Road/editRoad"=>["修改干线","路口管理"],
            "Road/delete"=>["删除干线","路口管理"],
            "Area/addAreaWithJunction"=>["新增区域","路口管理"],
            "Area/updateAreaWithJunction"=>["修改区域","路口管理"],
            "Area/delete"=>["删除区域","路口管理"],
            "AdpArea/addAreaWithJunction"=>["新增自适应区域","路口管理"],
            "AdpArea/updateAreaWithJunction"=>["修改自适应区域","路口管理"],
            "AdpArea/delete"=>["删除自适应区域","路口管理"],

            //flow管理
            "Mapflow/editFlow"=>["编辑flow名称","flow管理"],

            //配时管理
            "signal-control/signal-control/signalinout/import"=>["配时导入","配时管理"],
            "signal-control/signal-control/signalinout/export"=>["配时导出","配时管理"],
            "signal-control/signal-control/signalversion/releasetiming"=>["配时发布","配时管理"],

            //用户管理
            "passport-service/login/nanjing_loginout"=>["退出","用户管理"],
            "passport-service/login/login"=>["登陆","用户管理"],
            "passport-service/login/adminlogin"=>["管理员登陆","用户管理"],

            //参数管理
            "parametermanage/editparam"=>["编辑参数","用户管理"],

            //报告
            "signalpro-report/api/report/create"=>["新增","报告"],

            //任务管理
            "optroadtask/update"=>["新增、修改干线协调优化任务","任务管理"],
            "opttask/action"=>["更新状态","任务管理"],

            //工单管理
            "alarmWorksheet/submit"=>["新增","报告"],
            "alarmWorksheet/deal"=>["工单处理","报告"],
            "alarmWorksheet/valuation"=>["工单评价","报告"],
        ];
    }

    /**
     * 删除日志
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    /**
     * 删除日志
     * @param $deleteDay Y 删除几天前
     * @return bool
     */
    public function deleteLog($deleteDay="-3 day")
    {
        //筛选第100000条数据
        $result = $this->db->select('*')
            ->from($this->tb)
            ->limit(1,99999)
            ->order_by("id asc")
            ->get()
            ->result_array();
        if(strtotime(end($result)["operation_time"])<strtotime($deleteDay)){
            $this->db->where('id<', end($result)["id"])->delete($this->tb);
        }
        return true;
    }

    /**
     * add log
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function insertLog($params)
    {
        return $this->db->insert($this->tb, $params);
    }

    /**
     * 获取日志列表
     *
     * @param $data array Y 字段键值对
     * @return bool
     */
    public function pageList($params)
    {
        if(!empty($params["city_id"])){
            $this->db->where("city_id",$params["city_id"]);
        }
        if(!empty($params["user_name"])){
            $this->db->where("user_name",$params["user_name"]);
        }
        if(!empty($params["module"])){
            $this->db->where("module",$params["module"]);
        }
        if(!empty($params["action"])){
            $this->db->where("action",$params["action"]);
        }
        if(!empty($params["action_type"])){
            $this->db->where("action_type",$params["action_type"]);
        }
        if(!empty($params["start_time"])){
            $this->db->where("operation_time >",$params["start_time"]);
        }
        if(!empty($params["end_time"])){
            $this->db->where("operation_time <",$params["end_time"]);
        }
        $this->db->from($this->tb);
        $total = $this->db->count_all_results();

        $offset = $params["page_size"]*($params["page_num"]-1);
        if(!empty($params["city_id"])){
            $this->db->where("city_id",$params["city_id"]);
        }
        if(!empty($params["user_name"])){
            $this->db->where("user_name",$params["user_name"]);
        }
        if(!empty($params["module"])){
            $this->db->where("module",$params["module"]);
        }
        if(!empty($params["action"])){
            $this->db->where("action",$params["action"]);
        }
        if(!empty($params["action_type"])){
            $this->db->where("action_type",$params["action_type"]);
        }
        if(!empty($params["start_time"])){
            $this->db->where("operation_time >",$params["start_time"]);
        }
        if(!empty($params["end_time"])){
            $this->db->where("operation_time <",$params["end_time"]);
        }
        $result = $this->db->select('*')
            ->from($this->tb)
            ->limit($params["page_size"], $offset)
            ->forceMaster()
            ->order_by("operation_time desc,id desc")
            ->get();
        $result = !empty($result)? $result->result_array():[];
        foreach($result as $rk=>$rt){
            $requestIN = json_decode($rt["request_in"],true);
            if(!empty($requestIN["REQUEST"])){
                $requestIN = $requestIN["REQUEST"];
            }
            if(!empty($requestIN["REQUEST"])){
                $requestIN = $requestIN["REQUEST"];
            }
            if(!is_array($requestIN)){
                $requestIN = json_decode($requestIN,true);
            }
            $requestFormat = "";
            if(!empty($requestIN)){
                foreach ($requestIN as $key => $value) {
                    if(empty($requestFormat)){
                        $requestFormat=$key."=".json_encode($value);
                    }else{
                        $requestFormat=$requestFormat."||".$key."=".json_encode($value);                        
                    }
                }
            }
            // $result[$rk]["request_in"] = "【".$result[$rk]["action"]."】 ".$requestFormat;
            $result[$rk]["request_in"] = "【".$result[$rk]["action"]."】 ".$result[$rk]["action_log"];
            // var_dump(json_decode($rt["request_in"],true));exit;
        }
        return ["total"=>$total,"list"=>$result];
    }
}