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
        $this->load->model('waymap_model');
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

        //操作日志
        $juncNames = $this->waymap_model->getJunctionNames(implode(",",$params["junction_ids"]));
        $actionLog = sprintf("区域ID：%s，区域名称：%s，区域路口列表：%s",$data,$params["area_name"],implode(",",$juncNames));
        $this->insertLog("路口管理","新增自适应区域","新增",$params,$actionLog);
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
        $oldJuncIds = array_column($areaInfo["junction_list"],"logic_junction_id");
        $newJuncIds = $params["junction_ids"];
        $interJuncIds=array_intersect($oldJuncIds,$newJuncIds);
        $delJuncIds = [];
        $addJuncIds = [];
        foreach($oldJuncIds as $oldJuncId){
            if(!in_array($oldJuncId,$newJuncIds)){
                $delJuncIds[] = $oldJuncId;
            }
        }
        foreach($newJuncIds as $newJuncId){
            if(!in_array($newJuncId,$oldJuncIds)){
                $addJuncIds[] = $newJuncId;
            }
        }
        $addJuncNames = $this->waymap_model->getJunctionNames(implode(",",$addJuncIds));
        $delJuncNames = $this->waymap_model->getJunctionNames(implode(",",$delJuncIds));
        $actionLog = sprintf("区域ID：%s，区域名称：%s，新增路口：%s，删除路口：%s",$params["area_id"],$params["area_name"],implode(",",$addJuncNames),implode(",",$delJuncNames));
        $this->insertLog("路口管理","编辑自适应区域路口","编辑",$params,$actionLog);

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
        $actionLog = sprintf("区域ID：%s，区域名称：%s",$params["area_id"],$areaInfo["area_name"]);
        $this->insertLog("路口管理","删除自适应区域","删除",$params,$actionLog);
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
        $dataList = $data["list"]??[];
        // 根据权限过滤区域
        if (!empty($this->userPerm) && empty($this->userPerm["city_id"])) {
            $areaIds = $this->userPerm['admin_area_id'];
            if(!empty($areaIds)){
                $dataList = array_values(array_filter($dataList, function ($item) use ($areaIds) {
                    if (in_array($item['id'], $areaIds)) {
                        return true;
                    }
                    return false;
                }));
            }else{
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

    // 不知道干嘛用的，先不做
    // /**
    //  * 获取城市全部区域的详细信息
    //  * @param $params['city_id'] int 城市ID
    //  * @throws Exception
    //  */
    // public function getAllAreaJunctionList()
    // {
    //     $params = $this->input->post(null, true);

    //     $this->validate([
    //         'city_id' => 'required|is_natural_no_zero',
    //     ]);

    //     $data = $this->areaService->getCityAreaDetail($params);

    //     $this->response($data);
    // }
}
