<?php
/***************************************************************
# git文件上传类
# user:niuyufu@didichuxing.com
# date:2018-08-21
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Gift extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('gift_model');
    }

    public function Upload()
    {
        $params = $this->input->post();
        try{
            $data = $this->gift_model->Upload("file");
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response(["list"=>$data]);
    }

    public function getResourceKeyUrl()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'resource_key' => 'min:1',
            'mame_space' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->gift_model->getResourceUrl($params['resource_key'], $params['name_space']);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }
}
