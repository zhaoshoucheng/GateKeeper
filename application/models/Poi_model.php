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
        $result = $this->db->get()->result_array();

        return $result;
    }

    //根据分类类型返回中文描述
//    public function queryCategoryStr($poiType){
//        switch ($poiType){
//            case 1:
//                return "类型1";
//            case 2:
//                return "类型2";
//            default:
//                return "其他";
//        }
//
//    }

//房产小区
//公司企业
//购物
//机构团体
//基础设施
//教育学校
//酒店宾馆
//旅游景点
//美食
//文化场馆
//医疗保健
//银行金融
//娱乐休闲
//运动健身
//其它
    public function getCategoryStr(){
        return [
            '280000'=>'房产小区',
            '110000'=>'公司企业',
            '130000'=>'购物',
            '120000'=>'机构团体',
            '270000'=>'基础设施',
            '240000'=>'教育学校',
            '210000'=>'酒店宾馆',
            '220000'=>'旅游景点',
            '100000'=>'美食',
            '230000'=>'文化场馆',
            '200000'=>'医疗保健',
            '250000'=>'银行金融',
            '180000'=>'运动健身',
            '990000'=>'其它',
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