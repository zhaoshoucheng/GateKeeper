<?php

class AsyncTask_Controller extends CI_Controller
{
    public $errno = 0;
    public $errmsg = '';
    public $output_data = [];
    public $templates = [];
    public $routerUri = '';
    public $username = 'unknown';
    public $userPerm = [];
    protected $controllerType = "asynctask";

    public function __construct(){
        parent::__construct();
        $this->load->helper('async');
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

    public function response($data, $errno = 0, $errmsg = '')
    {
        $this->output_data = $data;
        $this->errno       = $errno;
        $this->errmsg      = $errmsg;
        $this->output->set_content_type('application/json');
    }

    protected function setTimingType()
    {
        try {
            $this->load->model('junction_model');
            $back_timing_roll = $this->config->item('back_timing_roll');
            $taskId           = $this->input->get_post('task_id', true);
            $taskUser         = $this->junction_model->getTaskUser($taskId);
            if (in_array($taskUser, $back_timing_roll, true)) {
                $this->timingType = 2;
            }
        } catch (\Exception $e) {
            com_log_warning('my_controller_set_timingtype_error', 0, $e->getMessage(), compact("taskId"));
        }
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

    private function _validateURI()
    {
        $ret = $this->user->isAuthorizedUri($this->routerUri);
        if (!$ret) {
            $this->errno = ERR_AUTH_URI;
            return false;
        }
        return true;
    }

    protected function convertJsonToPost(){
        //json格式转换为post格式
        $params = file_get_contents("php://input");
        if(!empty(json_decode($params,true))){
            $_POST = json_decode($params,true);
        }
    }

    public function _output()
    {
        $this->benchmark->mark('api_end');

        if ($this->errno > 0 && empty($this->errmsg)) {
            $errmsgMap    = $this->config->item('errmsg');
            $this->errmsg = $errmsgMap[$this->errno];
        }

        if (!empty($this->templates)) {
            foreach ($this->templates as $t) {
                echo $this->load->view($t, [], true);
            }
        } else {
            $output = [
                'errno' => $this->errno,
                'errmsg' => $this->errmsg,
                'data' => $this->output_data,
                'traceid' => get_traceid(),
                'username' => $this->username,
                'time' => [
                    'a' => $this->benchmark->elapsed_time('api_start', 'api_end') . '秒',
                    's' => $this->benchmark->elapsed_time('service_start', 'service_end') . '秒',
                ],
            ];
            header("Content-Type:application/json;charset=UTF-8");
            echo json_encode($output);
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
            $this->load->helper('async');
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
}