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
     *
     * @throws Exception
     */
    public function getAdaptTimingInfo()
    {
        $params = $this->input->post();

        $validate = Validate::make($params, [
            'logic_junction_id' => 'min:1',
            'city_id' => 'min:1'
        ]);

        if(!$validate['status']) {
            throw new Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->timingAdaptService->getAdaptTimingInfo($params);

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

        $validate = Validate::make($params, [
            'logic_junction_id' => 'min:1',
        ]);

        if(!$validate['status']) {
            throw new Exception($validate['errmsg'], ERR_PARAMETERS);
        }

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

        $validate = Validate::make($params, [
            'logic_junction_id' => 'min:1',
        ]);

        if(!$validate['status']) {
            throw new Exception($validate['errmsg'], ERR_PARAMETERS);
        }

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

        $validate = Validate::make($params, [
            'logic_junction_id' => 'min:1',
        ]);

        if(!$validate['status']) {
            throw new Exception($validate['errmsg'], ERR_PARAMETERS);
        }

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

        $validate = Validate::make($params, [
            'logic_junction_id' => 'nullunable',
            'is_open' => 'nullunable'
        ]);

        if(!$validate['status']) {
            throw new Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->timingAdaptService->getAdapteStatus($params);

        $this->response($data);
    }
}