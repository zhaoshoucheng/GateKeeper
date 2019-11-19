<?php

use \Services\RealtimeQuotaService;

class RealtimeQuota extends MY_Controller
{
    protected $realtimeQuotaService;
    public function __construct()
    {
        parent::__construct();

        $this->realtimeQuotaService = new RealtimeQuotaService();
        $this->load->model('redis_model');
    }

    /**
     * 获取路口flow粒度实时数据
     * @param $params['junction_id'] string Y 路口ID
     * @param $params['quota_keys']  string Y 以逗号间隔的指标key
     * @throws Exception
     */
    public function flow()
    {
        $params = $this->input->post(NULL,true);

        $this->validate([
            'city_id' => 'required|min_length[1]',
            'logic_junction_id' => 'required|min_length[1]',
            'quota_keys' => 'required|min_length[1]'
        ]);
        $quotaKey = $params["quota_keys"];
        $allKeys = ["stop_delay_up","queue_length_up","avg_stop_num_up","stop_delay_down","queue_length_down","avg_stop_num_down","avg_speed_up","free_flow_speed_up"];
        foreach (explode(",",$quotaKey) as $value){
            if(!in_array($value,$allKeys)){
                $this->errno = 30001;
                $this->errmsg="quota_keys error";
                return;
            }
        }
        $data = $this->realtimeQuotaService->getFlowQuota($params["city_id"],[$params["logic_junction_id"]],explode(",",$quotaKey),$this->userPerm);
        $this->response($data);
    }

    /**
     * 基准配时详情
     *
     * @throws Exception
     */
    public function getCurrentTimingInfo()
    {
        $params = $this->input->post();

        $this->validate([
            'logic_junction_id' => 'required|min_length[1]',
        ]);

        $data = $this->timingAdaptService->getCurrentTimingInfo($params);

        $this->response($data);
    }

    /**
     * 基准配时下发
     *
     * @throws Exception
     */
    public function updateCurrentTiming()
    {
        $params = $this->input->post();

        $this->validate([
            'logic_junction_id' => 'required|min_length[1]',
        ]);

        $data = $this->timingAdaptService->updateCurrentTiming($params);

        $this->response($data);
    }

    /**
     * 获取基准配时状态
     *
     * @throws Exception
     */
    public function getCurrentStatus()
    {
        $params = $this->input->post();

        $this->validate([
            'logic_junction_id' => 'required|min_length[1]',
        ]);

        $data = $this->timingAdaptService->getCurrentStatus($params);

        $this->response($data);
    }

    /**
     * 获取自适应配时状态
     *
     * @throws Exception
     */
    public function getAdapteStatus()
    {
        $params = $this->input->post();

        $this->validate([
            'logic_junction_id' => 'required|min_length[1]',
            'is_open' => 'required|in_list[0,1]'
        ]);

        $data = $this->timingAdaptService->getAdapteStatus($params);

        $this->response($data);
    }

    /**
     * 获取实时指标数据10分钟间隔
     * for 中控SaaS
     */
    public function GetQuotaFlowData(){
        $this->convertJsonToPost();
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required',
        ]);
        $params = [];
        $params["city_id"] = intval($this->input->post("city_id", true));
        $params["logic_junction_id"] = $this->input->post("logic_junction_id", true);
        $quotaKeys = ["stop_delay_up","avg_stop_num_up","avg_speed_up"];

        //10分钟一个路口只能调用一次
        $res = $this->redis_model->getData("zk_realtime_quota_".$params["logic_junction_id"]);
        if(!empty($res)){
            throw new \Exception("单路口请求频率太快");
        }
        $this->redis_model->setEx("zk_realtime_quota_".$params["logic_junction_id"], 1, 10);

        $data = $this->realtimeQuotaService->getFlowQuota($params["city_id"],[$params["logic_junction_id"]],$quotaKeys,$this->userPerm);
        foreach ($data["list"] as $key => $value) {
            unset($data["list"][$key]["confidence"]);
            unset($data["list"][$key]["movement_name"]);
        }
        $alarmList = $this->realtimeQuotaService->getRealTimeAlarmsInfo($params["city_id"],date("Y-m-d"));
        $alarmMap = [];
        foreach($alarmList as $key=>$value){
            if(empty($alarmMap[$value["logic_flow_id"]])){
                $alarmMap[$value["logic_flow_id"]] = [];
            }
            $alarmMap[$value["logic_flow_id"]][] = $value["type"];
        }
        // print_r($alarmMap);exit;

        //遍历报警数据
        foreach ($data["list"] as $key => $value) {
            $flowTypes = $alarmMap[$value["logic_flow_id"]] ?? [];
            if(in_array(3,$flowTypes)){
                $data["list"][$key]["is_empty"] = 1;
            }else{
                $data["list"][$key]["is_empty"] = 0;
            }
            if(in_array(1,$flowTypes)){
                $data["list"][$key]["is_oversaturation"] = 1;
            }else{
                $data["list"][$key]["is_oversaturation"] = 0;
            }
            if(in_array(2,$flowTypes)){
                $data["list"][$key]["is_spillover"] = 1;
            }else{
                $data["list"][$key]["is_spillover"] = 0;
            }
        }
        $this->response(["list"=>$data,]);
    }
}