<?php
/***************************************************************
# 区域管理
# user:niuyufu@didichuxing.com
# date:2018-08-23
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Area extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('area_model');
    }

    /**
     * 添加区域
     */
    public function add()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'area_name' => 'min:1',
            'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->addArea([
                'area_name' => $params["area_name"],
                'city_id' => intval($params['city_id']),
            ]);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * 区域更名
     */
    public function rename()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'area_name' => 'min:1',
            'area_id' => 'min:1',
            //'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->rename([
                'area_name' => $params["area_name"],
                'area_id' => intval($params['area_id']),
            ]);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * 区域删除
     */
    public function delete()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'area_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->delete(intval($params['area_id']));
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * 区域列表
     */
    public function getList()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->getList(intval($params['city_id']));
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response(["list"=>$data]);
    }


    /**
     * 更新区域路口
     */
    public function updateAreaJunction()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'area_id' => 'min:1',
            'logic_junction_id' => 'min:1',
            'type' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->updateAreaJunction($params['area_id'], $params['logic_junction_id'], $params['type']);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }


    /**
     * 区域路口列表
     */
    public function getAreaJunctionList()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'area_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->getAreaJunctionList(intval($params['city_id']), $params['area_id']);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response(["list"=>$data]);
    }
}
