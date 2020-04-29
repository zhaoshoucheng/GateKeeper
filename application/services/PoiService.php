<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2020/4/22
 * Time: 上午9:40
 */

namespace Services;

/**
 * Class Arterialtiming
 *
 * @property \Poi_model $poi_model
 */
class PoiService extends BaseService{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('poi_model');

    }

    public function getPoiTypeList(){
        $data  = $this->poi_model->getCategoryStr();
        return $data;
    }

    public function queryJunctionPoi($cityID,$logicJunctionID){
        $data = $this->poi_model->queryJuncPoiRelation($cityID,$logicJunctionID);
        return $data;
    }

    //更新保存新的映射关系
    public function saveJunctionPoi($cityID,$logicJunctionID,$poilist){


        $this->poi_model->delCategory($cityID,$logicJunctionID,$poilist);

        //批量删除,批量插入
        foreach ($poilist as $poi){
            $this->poi_model->addCategory($cityID,$logicJunctionID,$poi);
        }

        return ;

    }

    //路口新增Poi信息
    public function addJunctionPoi($cityID,$logicJunctionID,$poiTypes){
        $poiTypelist  = explode(",",$poiTypes);
        foreach ($poiTypelist as $poi){
            $ret =  $this->poi_model->addCategory($cityID,$logicJunctionID,$poi);
            if ($ret <=0) {
                return false;
            }
        }
        return true;

    }

    //删除路口poi信息
    public function deleteJunctionPoi($cityID,$logicJunctionID,$poiTypes){
        $poiTypelist  = explode(",",$poiTypes);
        return $this->poi_model->delCategory($cityID,$logicJunctionID,$poiTypelist);

    }
}