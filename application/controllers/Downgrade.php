<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Downgrade extends Inroute_Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Shanghai');
        parent::__construct();
        $this->load->model('Downgrade_model');
    }

    public function getOpen(){
        $params = array_merge($this->input->get(), $this->input->post());
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $output = array(
                'errno' => ERR_PARAMETERS,
                'errmsg' => $validate['errmsg'],
            );
            echo json_encode($output);
            return;
        }
        $cityId = intval($params['city_id']);
        $openInfo = $this->Downgrade_model->getOpen($cityId);
        $output = array(
            'errno' => ERR_SUCCESS,
            'errmsg' => "",
            'data' => $openInfo,
            'traceid' => get_traceid(),
        );
        echo json_encode($output);
        return;
    }

    public function open()
    {
        $params = array_merge($this->input->get(), $this->input->post());
        $validate = Validate::make($params, [
            'open' => 'min:0',
            'expired' => 'min:1',
            'notice' => 'min:1',
            'city_ids' => 'min:0',
        ]);
        if (!$validate['status']) {
            $output = array(
                'errno' => ERR_PARAMETERS,
                'errmsg' => $validate['errmsg'],
            );
            echo json_encode($output);
            return;
        }

        if(!isset($params['open'])){
            $output = array(
                'errno' => ERR_PARAMETERS,
                'errmsg' => 'the open must set.',
            );
            echo json_encode($output);
            return;
        }

        if(!preg_match('/\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}/ims',$params["expired"])){
            $output = array(
                'errno' => ERR_PARAMETERS,
                'errmsg' => 'expired format error.',
            );
            echo json_encode($output);
            return;
        }

        //权限验证
        if(ENVIRONMENT!='development'){
            $this->authToken($params);
        }
        $output = array(
            'errno' => ERR_SUCCESS,
            'errmsg' => "",
            'data' => $params,
            'traceid' => get_traceid(),
        );
        $this->Downgrade_model->saveOpen($params);
        echo json_encode($output);
        return;
    }

}
