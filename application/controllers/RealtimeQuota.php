<?php

use \Services\RealtimeQuotaService;

class RealtimeQuota extends MY_Controller
{
    protected $realtimeQuotaService;
    public function __construct()
    {
        parent::__construct();

        $this->realtimeQuotaService = new RealtimeQuotaService();
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
        $allKeys = ["stop_delay_up","queue_length_up","avg_stop_num_up","stop_delay_down","queue_length_down","avg_stop_num_down","avg_speed_up"];
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
}