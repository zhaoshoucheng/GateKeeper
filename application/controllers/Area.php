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

        // 根据权限过滤区域
        if (!empty($this->userPerm) && !empty($this->userPerm['city_id'])) {
            if (in_array($params['city_id'], $this->userPerm['city_id'])) {
                $areaIds = $this->userPerm['area_id'];
                $data = array_values(array_filter($data, function ($item) use ($areaIds) {
                    if (in_array($item['area_id'], $areaIds)) {
                        return true;
                    }
                    return false;
                }));
            } else {
                $data = [];
            }
        }


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

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|is_natural_no_zero',
            'quota_key' => 'required|min_length[1]',
            'base_start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'base_end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'evaluate_start_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'evaluate_end_date' => 'required|exact_length[10]|regex_match[/\d{4}-\d{2}-\d{2}/]',
        ]);

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

        $this->validate([
            'download_id' => 'required|min_length[1]'
        ]);

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

        if (empty($params['download_id'])) {
            throw new \Exception('参数download_id不能为空！', ERR_PARAMETERS);
        }

        $this->areaService->download($params);
    }
}
