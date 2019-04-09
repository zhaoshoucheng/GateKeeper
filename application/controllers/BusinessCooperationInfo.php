<?php
/**
 * 日志记录类
 */
use \Services\BusinessCooperationInfoService;
defined('BASEPATH') OR exit('No direct script access allowed');

class BusinessCooperationInfo extends CI_Controller{
    protected $businessService;
    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('http');
        $this->businessService = new BusinessCooperationInfoService();
    }

    protected function validate($rules)
    {
        foreach ($rules as $field => $rule) {
            $this->form_validation->set_rules($field, $field, $rule);
        }

        if ($this->form_validation->run() == false) {
            $errmsg = current($this->form_validation->error_array());
            throw new Exception($errmsg, ERR_PARAMETERS);
        }
    }

    protected function response($data, $errno = 0, $errmsg = '')
    {
        $output = [
            'errno' => $errno,
            'errmsg' => $errmsg,
            'data' => $data,
            'traceid' => get_traceid(),
        ];
        header("Content-Type:application/json;charset=UTF-8");
        echo json_encode($output);
    }

    public function insert()
    {
        $params = $this->input->post(NULL,true);
        try{
            $this->validate([
                'trade' => 'trim|min_length[1]',
                'product' => 'trim|min_length[1]',
                'name' => 'trim|required|min_length[1]',
                'company' => 'trim|min_length[1]',
                'job' => 'trim|min_length[1]',
                'address' => 'trim|min_length[1]',
                'phone' => 'trim|required|min_length[1]',
                'email' => 'trim|valid_email',
            ]);
        }catch (\Exception $e){
            $this->response("",10011,"参数错误");
            return;
        }
        $this->businessService->insert($params);
        $this->response("");
    }
}