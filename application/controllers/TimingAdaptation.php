<?php

class TimingAdaptation extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('timingadaptation_model');
    }

    /**
     * 自适应配时详情
     */
    public function getAdaptTimingInfo()
    {
        $params = $this->input->post();

        $validator = Validator::make($params, [
            'logic_junction_id' => 'required',
            'city_id' => 'required'
        ]);

        if($validator->fail()) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }

        $data = $this->timingadaptation_model->getAdaptTimingInfo($params);

        $this->response($data);
    }

    /**
     * 基准配时详情
     */
    public function getCurrentTimingInfo()
    {
        $params = $this->input->post();

        $validator = Validator::make($params, [
            'logic_junction_id' => 'required',
        ]);

        if($validator->fail()) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }

        $data = $this->timingadaptation_model->getCurrentTimingInfo($params);

        $this->response($data);
    }

    public function updateCurrentTiming()
    {
        $params = $this->input->post();

        $data = $this->timingadaptation_model->updateCurrentTiming($params);

        $this->response($data);
    }

    public function getAdapteStatus()
    {
        $params = $this->input->post();

        $validator = Validator::make($params, [
            'logic_junction_id' => 'required',
            'is_open' => 'required'
        ]);

        if($validator->fail()) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }

        try {
            $data = $this->timingadaptation_model->getAdapteStatus($params);
            $this->response($data);
        } catch (Exception $e) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $e->getMessage();
        }
    }
}