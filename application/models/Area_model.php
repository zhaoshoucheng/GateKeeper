<?php

/********************************************
 * # desc:    区域数据模型
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-08-23
 ********************************************/
class Area_model extends CI_Model
{
    private $tb = 'area';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            return [];
        }
        $this->load->model('waymap_model');
        $this->load->config('nconf');
    }

    public function getList($city_id){
        $this->db->where('city_id', $city_id);
        $this->db->where('delete_at', "1970-01-01 00:00:00");
        $this->db->order_by('id', 'DESC');
        $result = $this->db->from($this->tb)->get()->result_array();
        $formatResult = function ($result){
            return $result;
        };
        return $formatResult($result);
    }

    public function addArea($data)
    {
        $this->replaceArea([
            "create_at"=>date("Y-m-d H:i:s"),
            "city_id"=>$data["city_id"],
            "area_name"=>$data["area_name"],
        ]);
        return true;
    }

    public function rename($data)
    {
        $this->replaceArea([
            "id"=>$data["area_id"],
            "area_name"=>$data["area_name"],
        ]);
        return true;
    }

    public function delete($areaId)
    {
        $this->replaceArea([
            "id"=>$areaId,
            "delete_at"=>date("Y-m-d H:i:s"),
        ]);
        return true;
    }

    /**
     * 更新的区域内路口
     * @param $areaId   int     区域id
     * @param $logicJunctionId  int     路口id
     * @param $type     int     操作类型 1=新增 2=删除
     */
    public function updateAreaJunction($areaId, $logicJunctionId, $type){
        if($type==1){
            $data = [
                "area_id"=>$areaId,
                "junction_id"=>$logicJunctionId,
                "update_at"=>date("Y-m-d H:i:s"),
                "create_at"=>date("Y-m-d H:i:s"),
            ];
            $this->db->insert("area_junction_relation", $data);
        }
        if($type==2){
            $this->db->where('area_id', $areaId);
            $this->db->where('junction_id', $logicJunctionId);
            $this->db->delete('area_junction_relation');
        }
        return true;
    }

    /**
     * 获取城市、区域内路口列表
     * @param $cityId   int     城市id
     * @param $areaId   int     区域id
     */
    public function getAreaJunctionList($cityId, $areaId){
        $this->db->from('area');
        $this->db->join('area_junction_relation', 'area.id = area_junction_relation.area_id');
        $this->db->where('area.delete_at', "1970-01-01 00:00:00");
        //$this->db->where('area.city_id', $cityId);
        $this->db->where('area.id', $areaId);
        $this->db->where('area.delete_at', "1970-01-01 00:00:00");
        $this->db->select('area.id as area_id,area.city_id,junction_id');
        $query = $this->db->get();
        $result = $query->result_array();

        $areaInfo = $this->db->from($this->tb)->where('id', $areaId)->get()->row_array();
        $junctionCenterFunc = function ($dataList) {
            $count_lng = 0;
            $count_lat = 0;
            $qcount = count($dataList);
            foreach ($dataList as $v) {
                $count_lng += $v['lng'];
                $count_lat += $v['lat'];
            }
            return ["lng" => round($count_lng / $qcount, 6), "lat" => round($count_lat / $qcount, 6),];
        };
        $formatResult = function ($result) use($areaInfo,$junctionCenterFunc){
            $formatResult = [];
            $junctionIds = array_reduce($result, function ($carry, $item) {
                if(!empty($item["junction_id"])){
                    $carry[] = $item["junction_id"];
                }
                return $carry;
            }, []);
            $junctionList = $this->waymap_model->getJunctionInfo(implode(",",$junctionIds));
            if (!empty($junctionList)){
                $junctionCenter = $junctionCenterFunc($junctionList);
                $formatResult['center_lat'] = (string)$junctionCenter["lat"];
                $formatResult['center_lng'] = (string)$junctionCenter["lng"];
            }
            $formatResult['area_id'] = $areaInfo['id'];
            $formatResult['area_name'] = $areaInfo['area_name'];
            $formatResult['junction_list'] = $junctionList;
            return $formatResult;
        };
        return $formatResult($result);
    }

    //自动替换model数据
    private function replaceArea($data)
    {
        if(!empty($data["id"])){
            $this->db->from($this->tb);
            $row = $this->db->where('id', $data["id"])->get()->row_array();

            //数据补全
            foreach ($row as $var=>$val){
                if(!isset($data[$var])){
                    $data[$var] = $val;
                }
            }
        }
        $data["update_at"] = date("Y-m-d H:i:s");   //全局更新
        if(empty($data["id"])) {
            $this->db->insert($this->tb, $data);
        }else{
            $this->db->where('id', $data["id"]);
            unset($data["id"]);
            $this->db->update($this->tb, $data);

        }
    }
}
