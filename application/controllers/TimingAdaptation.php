<?php

use \Services\TimingAdaptionService;

class TimingAdaptation extends MY_Controller
{
    protected $timingAdaptService;

    public function __construct()
    {
        parent::__construct();

        $this->timingAdaptService = new TimingAdaptionService();
    }

    /**
     * 自适应配时详情
     * @param $params['logic_junction_id'] string Y 路口ID
     * @param $params['city_id']           int    Y 城市ID
     * @throws Exception
     */
    public function getAdaptTimingInfo()
    {
        $params = $this->input->post();

        $this->validate([
            'logic_junction_id' => 'required|min_length[1]',
            'city_id' => 'required|is_natural_no_zero'
        ]);

        try{
            $data = $this->timingAdaptService->getAdaptTimingInfo($params);
        }catch (\Exception $e){
            $this->response([],1000008,"暂时无法获取自适应配时详情");
            return;
        }
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

        try{
            $data = $this->timingAdaptService->getAdapteStatus($params);
        }catch (\Exception $e){
            $this->response([],1000008,"暂时无法获取自适应配时状态");
            return;
        }
        $this->response($data);
    }
}