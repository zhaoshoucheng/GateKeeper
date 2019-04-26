<?php

class AsyncTask_Controller extends MY_Controller
{
    protected $controllerType = "asynctask";

    public function __construct(){
        parent::__construct();
        try{
            $this->authToken();
        }catch (\Exception $e){
            if(empty(get_traceid())){
                gen_traceid();
            }
            com_log_warning('_asynctask_index_error', 0, $e->getMessage(), []);
            $this->response(array(), 900001, $e->getMessage());
            $this->_output();
            exit;
        }
    }

    private function authToken(){
        $params = $this->input->post();

        $this->load->config('async_call');
        $allow_host_ip = $this->config->item('async_allow_host_ip');
        $deny_host_ip = $this->config->item('async_deny_host_ip');
        $salt_token = $this->config->item('async_salt_token');
        $remote_ip = $_SERVER["REMOTE_ADDR"];

        //优先验证签名==>其次验证白名单
        if(!empty($params['current_time']) && !empty($params['sig'])){
            $salt_token = $salt_token;
            if(!checkSign($params,$salt_token)){
                throw new \Exception('no_permission_checkSign');
            }
            if(time()-$params['current_time']>3600){
                throw new \Exception('no_permission_expired');
            }
        }else{
            if (!empty($allow_host_ip) && !in_array($remote_ip, $allow_host_ip)) {
                throw new \Exception('no_permission_allow_host');
            }
            if (!empty($deny_host_ip) && in_array($remote_ip, $deny_host_ip)) {
                throw new \Exception('no_permission_deny_host');
            }
        }
    }

    public function getControllerType()
    {
        return $this->controllerType;
    }

    /**
     *
     * @param $rules ['city_id' => 'required|is_natural_no_zero', ... ]
     *
     * @throws Exception
     */
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
}