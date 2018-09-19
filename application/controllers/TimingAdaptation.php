<?php

class TimingAdaptation extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('timingadaptation_model');
    }

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
}