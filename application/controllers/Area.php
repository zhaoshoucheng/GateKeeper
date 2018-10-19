<?php
/***************************************************************
 * # 区域管理
 * # user:niuyufu@didichuxing.com
 * # date:2018-08-23
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AreaService;

class Area extends MY_Controller
{
    protected $areaService;

    /**
     * Area constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->areaService = new AreaService();
    }

    /**
     * 添加区域
     *
     * @throws Exception
     */
    public function addAreaWithJunction()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'area_name' => 'min:1',
            'city_id' => 'min:1',
            'junction_ids' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        // 数据格式化
        $params['city_id'] = intval($params['city_id']);

        $data = $this->areaService->addArea($params);

        $this->response($data);
    }

    /**
     * 更新区域及路口
     *
     * @throws Exception
     */
    public function updateAreaWithJunction()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'area_name' => 'min:1',
            'city_id' => 'min:1',
            'junction_ids' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        // 数据格式化
        $params['city_id'] = intval($params['city_id']);

        $data = $this->areaService->updateArea($params);

        $this->response($data);
    }

    /**
     * 区域删除
     *
     * @throws Exception
     */
    public function delete()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'area_id' => 'min:1',
        ]);

        // 数据格式化
        $params['area_id'] = intval($params['area_id']);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->areaService->deleteArea($params);

        $this->response($data);
    }

    /**
     * 获取区域列表
     *
     * @throws Exception
     */
    public function getList()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        // 数据格式化
        $params['city_id'] = intval($params['city_id']);

        $data = $this->areaService->getList($params);

        $this->response($data);
    }

    /**
     * 区域路口列表
     *
     * @throws Exception
     */
    public function getAreaJunctionList()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'area_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        // 数据格式化
        $params['area_id'] = intval($params['area_id']);

        $data = $this->areaService->getAreaDetail($params);

        $this->response($data);
    }

    /**
     * 获取城市全部区域的详细信息
     *
     * @throws Exception
     */
    public function getAllAreaJunctionList()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        // 数据格式化
        $params['city_id'] = intval($params['city_id']);

        $data = $this->areaService->getCityAreaDetail($params);

        $this->response($data);
    }

    /**
     * 获取区域评估指标
     */
    public function getQuotas()
    {
        $data = $this->areaService->getQuotas();

        $this->response($data);
    }

    /**
     * 区域评估
     *
     * @throws Exception
     */
    public function comparison()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'area_id' => 'min:1',
            'quota_key' => 'min:1',
            'base_start_date' => 'date:Y-m-d',
            'base_end_date' => 'date:Y-m-d',
            'evaluate_start_date' => 'date:Y-m-d',
            'evaluate_end_date' => 'date:Y-m-d',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $params['city_id'] = intval($params['city_id']);
        $params['area_id'] = intval($params['area_id']);

        $data = $this->areaService->comparison($params);

        $this->response($data);
    }

    /**
     * 获取数据下载链接
     *
     * @throws Exception
     */
    public function downloadEvaluateData()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'download_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->areaService->downloadEvaluataData($params);

        $this->response($data);
    }

    /**
     * Excel 文件下载
     *
     * @throws Exception
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function download()
    {
        $params = $this->input->get();

        $validate = Validate::make($params, [
            'download_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $this->areaService->download($params);
    }
}
