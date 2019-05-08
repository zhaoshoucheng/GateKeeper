<?php
/***************************************************************
 * # 区域管理
 * # user:niuyufu@didichuxing.com
 * # date:2018-08-23
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AdpAreaService;

class AdpArea extends MY_Controller
{
    protected $areaService;

    /**
     * Area constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->areaService = new AdpAreaService();
    }

    /**
     * 添加区域
     *
     * @throws Exception
     */
    public function addAreaWithJunction()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_name' => 'required|trim|min_length[1]',
            'junction_ids[]' => 'required',
        ]);

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

        $this->validate([
            'area_id' => 'required|is_natural_no_zero',
            'area_name' => 'required|trim|min_length[1]',
            'junction_ids[]' => 'required',
        ]);

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

        $this->validate([
            'area_id' => 'required|is_natural_no_zero',
        ]);

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

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);

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

        $this->validate([
            'area_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->areaService->getAreaDetail($params);

        $this->response($data);
    }

    /**
     * 获取城市全部区域的详细信息
     * @param $params['city_id'] int 城市ID
     * @throws Exception
     */
    public function getAllAreaJunctionList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->areaService->getCityAreaDetail($params);

        $this->response($data);
    }
}
