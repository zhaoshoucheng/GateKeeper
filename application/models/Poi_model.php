<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2020/4/22
 * Time: 上午9:39
 */


/**
 * Class Poi_model
 */
class Poi_model extends CI_Model{
    public function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $this->tb = "junction_poi_relation";
    }

    //查询路口poi相关属性
    public function queryJuncPoiRelation($cityID,$logicJunctionID){

        $this->db->from('junction_poi_relation');
        $this->db->where('city_id', $cityID)->where('logic_junction_id',$logicJunctionID);
        $result = $this->db->get()->row_array();

        return $result;
    }

    //根据分类类型返回中文描述
    public function queryCategoryStr($poiType){
        switch ($poiType){
            case 1:
                return "类型1";
            case 2:
                return "类型2";
            default:
                return "其他";
        }

    }

    public function getCategoryStr(){
        return [
            '1'=>'类型1',
            '2'=>'类型2',
            '3'=>'类型3',
        ];
    }

    //新增类别
    public function addCategory($cityID,$logicJunctionID,$poiType){
        $data['city_id'] = $cityID;
        $data['logic_junction_id'] = $logicJunctionID;
        $data['poi_type'] = $poiType;

        $this->db->insert($this->tb, $data);

        return $this->db->insert_id();
    }

    //修改类别
    public function updateCategory($id,$poiType){
        $data = [
            'poi_type'=>$poiType
        ];
        return $this->db->where('id', $id)
            ->update($this->tb, $data);
    }

    public function delCategory($cityID,$logicJunctionID,$poiTypelist){
        $this->db->where('city_id', $cityID)
            ->where('logic_junction_id',$logicJunctionID)
            ->where_in('poi_type', $poiTypelist)
            ->delete($this->tb);

    }
}