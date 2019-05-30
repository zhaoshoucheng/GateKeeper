<?php

/**
 * 异步调用方法controller
 */
class Callfunc extends AsyncTask_Controller
{
    function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $params = $this->input->post();
        $this->validate([
            'class' => 'required|min_length[1]',
            'function' => 'required|min_length[1]',
            'params' => 'required|min_length[1]',
        ]);
        try {
            $params['params'] = json_decode($params['params'],true);
            $className = substr($params['class'],strripos($params['class'],'/',0));
            com_log_notice('Callfunc_exec',['paramArr'=>$params['params'],'className'=>$params['class'],'function'=>$params['function'],]);
            $this->load->model($params['class']);
            $instance = $this->$className;
            $ret = call_user_func_array(array($instance,$params['function']), $params['params']);
            //写入到 itstool_adapt_log kafka
            return $this->response($ret);
        } catch (\Exception $e) {
            com_log_warning('_asynctask_index_error', 0, $e->getMessage(), []);
            return $this->response(array(), ERR_PARAM, $e->getMessage());
        }
    }
}