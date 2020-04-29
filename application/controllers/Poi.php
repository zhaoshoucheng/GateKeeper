<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2020/4/22
 * Time: 上午9:38
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\PoiService;

//poi相关功能接口
class Poi extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->poiService = new PoiService();

    }

    //查询poi类别列表
    public function queryPoiTypeList(){
        $params = $this->input->get(NULL,true);
        $cityID = $params['city_id'];
        $data = $this->poiService->getPoiTypeList();
        $this->response($data);
    }

    //查询单个路口的poi关联信息
    public function queryJunctionPoi(){
        $params = $this->input->get(NULL,true);
        $cityID  = $params['city_id'];
        $logicJunctionID = $params['logic_junction_id'];
        $data = $this->poiService->queryJunctionPoi($cityID,$logicJunctionID);
        $this->response($data);

    }

    public function saveJunctionPoi(){
        $params = $this->input->post(NULL,true);
        $cityID  = $params['city_id'];
        $logicJunctionID = $params['logic_junction_id'];
        $poiList = $params['poi_list'];

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'logic_junction_id' => 'required',
            'poi_list'=>'required',
        ]);

        $this->poiService->saveJunctionPoi($cityID,$logicJunctionID,explode(",",$poiList) );

        $this->response("success");
    }
//
//    public function updateJunctionPoi(){
//        $params = $this->input->post(NULL,true);
//        $cityID  = $params['city_id'];
//        $logicJunctionID = $params['logic_junction_id'];
//        $poiType = $params['poi_type'];
//        $id = 0;
//        if (isset($params['id'])){
//            $id = $params['id'];
//        }
//        $ret =  $this->poiService->saveJunctionPoi($id,$cityID,$logicJunctionID,$poiType);
//
//        $this->response($ret);
//    }

    //新增poi信息
    public function addJunctionPoi(){
        $params = $this->input->post(NULL,true);
        $cityID  = $params['city_id'];
        $logicJunctionID = $params['logic_junction_id'];
        $poiTypes = $params['poi_types'];

        $ret = $this->poiService->addJunctionPoi($cityID,$logicJunctionID,$poiTypes);
        if ($ret){
            $this->response("success");
            return;
        }
        $this->errno = ERR_DATABASE;
        $this->errmsg= "添加分类失败";
        return;

    }

    //删除poi信息
    public function deleteJunctionPoi(){
        $params = $this->input->post(NULL,true);
        $cityID  = $params['city_id'];
        $logicJunctionID = $params['logic_junction_id'];
        $poiTypes = $params['poi_types'];

        $ret = $this->poiService->deleteJunctionPoi($cityID,$logicJunctionID,$poiTypes);

        $this->response($ret);
    }



}