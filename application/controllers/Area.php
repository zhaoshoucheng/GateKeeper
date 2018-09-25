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
     * v2
     * 添加区域
     */
    public function addAreaWithJunction()
    {
        $params = $this->input->post(null,true);
        $validate = Validate::make($params, [
            'area_name' => 'min:1',
            'city_id' => 'min:1',
            'junction_ids' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $params['junction_ids'] = !empty($params['junction_ids']) ? $params['junction_ids'] : [];
            $data = $this->area_model->addAreaWithJunction([
                'area_name' => $params["area_name"],
                'city_id' => intval($params['city_id']),
                'junction_ids' => $params['junction_ids'],
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
     * v2
     * 更新区域及路口
     */
    public function updateAreaWithJunction()
    {
        $params = $this->input->post(null,true);
        $validate = Validate::make($params, [
            'area_id' => 'min:1',
            'area_name' => 'min:1',
            'junction_ids' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $params['junction_ids'] = !empty($params['junction_ids']) ? $params['junction_ids'] : [];
            $data = $this->area_model->updateAreaWithJunction([
                'area_id' => intval($params['area_id']),
                'area_name' => $params["area_name"],
                'junction_ids' => $params['junction_ids'],
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
     * v2
     * 删除区域路口
     */
    public function deleteJunction()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            'area_id' => 'min:1',
            'logic_junction_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $data = $this->area_model->updateAreaJunction($params['area_id'], $params['logic_junction_id'], 2);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }


    /**
     * v2
     * 区域路口列表
     */
    public function getAreaJunctionList()
    {
        $params = $this->input->post();
        $validate = Validate::make($params, [
            //'city_id' => 'min:1',
            'area_id' => 'min:1',
        ]);
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        try{
            $params['city_id'] = !empty($params['city_id']) ? $params['city_id'] : 0;
            $data = $this->area_model->getAreaJunctionList(intval($params['city_id']), $params['area_id']);
        }catch (\Exception $e){
            com_log_warning('_itstool_'.__CLASS__.'_'.__FUNCTION__.'_error', 0, $e->getMessage(), compact("params","data"));
            $this->errno = ERR_HTTP_FAILED;
            $this->errmsg = $e->getMessage();
            return;
        }
        return $this->response($data);
    }

    /**
     * 区域评估
     */
    public function comparison()
    {
        $params = $this->input->post();

        //数据校验
        $validator = Validator::make($params, [
            'city_id' => 'required;numeric',
            'area_id' => 'required;numeric',
            'quota_key' => 'required',
            'base_start_date' =>'required;date:Y-m-d',
            'base_end_date' =>'required;date:Y-m-d',
            'evaluate_start_date' =>'required;date:Y-m-d',
            'evaluate_end_date' =>'required;date:Y-m-d',
        ]);

        if($validator->fail()) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }

        //异常处理
        try {
            $data = $this->area_model->comparison($params);
            return $this->response($data);
        } catch (Exception $e) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validator->firstError();
            return;
        }
    }
}
