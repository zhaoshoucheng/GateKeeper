<?php

/***************************************************************
 * # 区域管理
 * # user:niuyufu@didichuxing.com
 * # date:2018-08-23
 ***************************************************************/

defined('BASEPATH') or exit('No direct script access allowed');

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
        $this->load->model('waymap_model');
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
        //操作日志
        $juncNames = $this->waymap_model->getJunctionNames(implode(",", $params["junction_ids"]));
        $actionLog = sprintf("区域ID：%s，区域名称：%s，区域路口列表：%s", $data, $params["area_name"], implode(",", $juncNames));
        $this->insertLog("路口管理", "新增区域", "新增", $params, $actionLog);
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

        //操作日志
        $areaInfo = $this->areaService->getAreaDetail($params);

        $oldJuncIds = array_column($areaInfo["junction_list"], "logic_junction_id");
        $newJuncIds = $params["junction_ids"];
        // print_r($areaInfo);exit;
        // print_r($newJuncIds);
        // exit;
        $interJuncIds = array_intersect($oldJuncIds, $newJuncIds);
        $delJuncIds = [];
        $addJuncIds = [];
        foreach ($oldJuncIds as $oldJuncId) {
            if (!in_array($oldJuncId, $newJuncIds)) {
                $delJuncIds[] = $oldJuncId;
            }
        }
        foreach ($newJuncIds as $newJuncId) {
            if (!in_array($newJuncId, $oldJuncIds)) {
                $addJuncIds[] = $newJuncId;
            }
        }

        $addJuncNames = $this->waymap_model->getJunctionNames(implode(",", $addJuncIds));
        $delJuncNames = $this->waymap_model->getJunctionNames(implode(",", $delJuncIds));
        // print_r($delJuncIds);
        // print_r($delJuncNames);exit; 
        $actionLog = sprintf("区域ID：%s，区域名称：%s，新增路口：%s，删除路口：%s", $params["area_id"], $params["area_name"], implode(",", $addJuncNames), implode(",", $delJuncNames));
        $this->insertLog("路口管理", "编辑区域路口", "编辑", $params, $actionLog);

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

        //操作日志
        $areaInfo = $this->areaService->getAreaDetail($params);
        $actionLog = sprintf("区域ID：%s，区域名称：%s", $params["area_id"], $areaInfo["area_name"]);
        $this->insertLog("路口管理", "删除区域", "删除", $params, $actionLog);

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
        $dataList = $data["list"] ?? [];
        // 根据权限过滤区域
        if (!empty($this->userPerm) && empty($this->userPerm["city_id"])) {
            $areaIds = $this->userPerm['area_id'];
            if (!empty($areaIds)) {
                $dataList = array_values(array_filter($dataList, function ($item) use ($areaIds) {
                    if (in_array($item['id'], $areaIds)) {
                        return true;
                    }
                    return false;
                }));
            } else {
                $dataList = [];
            }
        }
        $data["list"] = $dataList;
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
     * 获取城市Scats区域的详细信息
     * @param $params['city_id'] int 城市ID
     * @throws Exception
     */
    public function getAllScatsAreaJunctionList()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
        ]);

        $data = $this->areaService->getCityAreaDetail($params);

        //根据路口数过滤
        $data = array_values(array_filter($data, function ($item) use ($areaIds) {
            if(count($item['junction_list'])>100){
                return false;
            }
            return true;
        }));
        
        // 根据权限过滤区域
        if (!empty($this->userPerm) && empty($this->userPerm["city_id"])) {
            $areaIds = $this->userPerm['area_id'];
            if (!empty($areaIds)) {
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
        if (!empty($this->userPerm) && empty($this->userPerm["city_id"])) {
            $areaIds = $this->userPerm['area_id'];
            if (!empty($areaIds)) {
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
     * 区域时段评估
     *
     * @throws Exception
     */
    public function intervalComparison()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'area_id' => 'required|is_natural_no_zero',
            'quota_key' => 'required|min_length[1]',
            'base_dates' => 'required|min_length[10]',
        ]);
        $data = $this->areaService->comparison($params);
        unset($data["info"]);
        unset($data["evaluate"]);
        unset($data["base"]);
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
