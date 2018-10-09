<?php

/********************************************
 * # desc:    区域数据模型
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-08-23
 ********************************************/

use Didi\Cloud\Collection\Collection;

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
        $this->load->model('redis_model');
        $this->load->config('nconf');
        $this->load->config('realtime_conf');
        $this->load->config('evaluate_conf');
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
        $areaId = $this->getAreaIdByCityAreaName($data["city_id"], $data["area_name"]);
        if($areaId>0){
            throw new \Exception("The area_name exists.");
        }
        return $this->replaceArea([
            "create_at"=>date("Y-m-d H:i:s"),
            "city_id"=>$data["city_id"],
            "area_name"=>$data["area_name"],
        ]);
    }

    public function addAreaWithJunction($data)
    {
        $areaId = $this->addArea($data);
        if(!empty($data['junction_ids'])){
            foreach ($data['junction_ids'] as $junction_id){
                $this->updateAreaJunction($areaId, $junction_id, 1);
            }
        }
        return $areaId;
    }

    public function updateAreaWithJunction($data)
    {
        $areaId = $this->updateArea($data);
        $this->deleteAreaJunction($areaId);
        if(!empty($data['junction_ids'])){
            foreach ($data['junction_ids'] as $junction_id){
                $this->updateAreaJunction($areaId, $junction_id, 1);
            }
        }
        return true;
    }

    public function updateArea($data)
    {
        //判断area_name是否重复
        $areaInfo = $this->db->from($this->tb)->where('delete_at', '1970-01-01 00:00:00')->where('id', $data["area_id"])->get()->row_array();
        if (empty($areaInfo)){
            throw new \Exception("The area not exists.");
        }
        $cityId = $areaInfo['city_id'];
        $areaId = $this->getAreaIdByCityAreaName($cityId, $data["area_name"]);
        if($areaId>0 && $areaId!=$data["area_id"]){
            throw new \Exception("The area_name exists.");
        }

        return $this->replaceArea([
            "id"=>$data["area_id"],
            "area_name"=>$data["area_name"],
        ]);
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
     * 删除区域相关路口
     * @param $areaId
     */
    public function deleteAreaJunction($areaId){
        $this->db->where('area_id', $areaId);
        $this->db->delete('area_junction_relation');
    }

    /**
     * 更新的区域内路口
     * @param $areaId   int     区域id
     * @param $logicJunctionId  int     路口id
     * @param $type     int     操作类型 1=新增 2=删除
     */
    public function updateAreaJunction($areaId, $logicJunctionId, $type){
        if($type==1){
            //记录是否存在?
            $junctionInfo = $this->db
                ->from('area_junction_relation')
                ->where('area_id', $areaId)
                ->where('junction_id', $logicJunctionId)->get()->row_array();
            if(!empty($junctionInfo)){
                return true;
            }

            //插入
            $data = [
                "area_id"=>$areaId,
                "junction_id"=>$logicJunctionId,
                "update_at"=>date("Y-m-d H:i:s"),
                "create_at"=>date("Y-m-d H:i:s"),
            ];
            $this->db->insert("area_junction_relation", $data);
        }
        if($type==2){
            //更新
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
        $areaInfo = $this->db->from($this->tb)->where('delete_at', '1970-01-01 00:00:00')->where('id', $areaId)->get()->row_array();
        if (empty($areaInfo)){
            throw new \Exception("The area not exists.");
        }
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

    public function getAllAreaJunctionList($params)
    {
        $areas = $this->db->select('area_name, id')
            ->from('area')
            ->where('city_id', $params['city_id'])
            ->where('delete_at', '1970-01-01 00:00:00')
            ->order_by('create_at desc')
            ->get()->result_array();

        $areaIds = array_column($areas, 'id');

        $areaIdNames = array_column($areas, 'area_name', 'id');

        $areaJunctions = $this->db->select('area_id, junction_id')
            ->from('area_junction_relation')
            ->where_in('area_id', $areaIds)
            ->where('delete_at', '1970-01-01 00:00:00')
            ->get()->result_array();

        $junctionIds = array_column($areaJunctions, 'junction_id');

        $junctionList = $this->waymap_model->getJunctionInfo(implode(",",$junctionIds));

        $junctionIdList = array_column($junctionList, null, 'logic_junction_id');

        $areaIdJunctionList = Collection::make($areaJunctions)
            ->groupBy('area_id', function ($v) {
                return array_column($v, 'junction_id');
            })->get();

        $results = [];

        foreach ($areaIdJunctionList as $areaId => $junctionIds) {
            $result = [
                'area_id' => $areaId,
                'area_name' => $areaIdNames[$areaId] ?? '',
            ];

            $cnt_lng = 0;
            $cnt_lat = 0;
            foreach ($junctionIds as $id) {
                $result['junction_list'][] = $junctionIdList[$id] ?? '';
                $cnt_lat += $junctionIdList[$id]['lat'] ?? 0;
                $cnt_lng += $junctionIdList[$id]['lng'] ?? 0;
            }

            $len = count($result['junction_list']);

            $result['center_lat'] = $len == 0 ? 0 : $cnt_lat / $len;
            $result['center_lng'] = $len == 0 ? 0 : $cnt_lng / $len;

            $results[$areaId] = $result;
        }

        krsort($results);

        return $results;
    }

    public function comparison($params)
    {
        // 指标算法映射
        $methods = [
            'speed' => 'round(avg(speed), 2) as speed',
            'stop_delay' => 'round(avg(stop_delay), 2) as stop_delay'
        ];

        $nameMaps = [
            'speed' => '区域平均速度',
            'stop_delay' => '区域平均延误'
        ];

        // 获取指标单位
        $units = array_column($this->config->item('area'), 'unit', 'key');

        // 指标不存在与映射数组中
        if(!isset($methods[$params['quota_key']])) {
            return [];
        }

        if(!($areaInfo = $this->getAreaInfo($params['area_id']))) {
            throw new Exception('该区域已被删除');
        }

        // 获取该区域全部路口ID
        $junctionList = $this->db->select('junction_id')
            ->from('area_junction_relation')
            ->where('area_id', $params['area_id'])
            ->where('delete_at', '1970-01-01 00:00:00')
            ->get()->result_array();

        // 数据获取失败 或者 数据为空
        if(!$junctionList || empty($junctionList)) {
            return [];
        }

        $junctionList = array_column($junctionList, 'junction_id');

        // 基准时间范围
        $baseDates = dateRange($params['base_start_date'], $params['base_end_date']);

        // 评估时间范围
        $evaluateDates = dateRange($params['evaluate_start_date'], $params['evaluate_end_date']);

        // 生成 00:00 - 23:30 间的 粒度为 30 分钟的时间集合数组
        $hours = hourRange('00:00', '23:30');

        // 获取数据
        $result = $this->db->select('date, hour, ' . $methods[$params['quota_key']])
            ->from('junction_hour_report')
            ->where_in('date', array_merge($baseDates, $evaluateDates))
            ->where_in('logic_junction_id', $junctionList)
            ->where_in('hour', $hours)
            ->where('city_id', $params['city_id'])
            ->group_by(['date', 'hour'])->get()->result_array();

        if(!$result || empty($result))
            return [];


        // 将数据按照 日期（基准 和 评估）进行分组的键名函数
        $baseOrEvaluateCallback = function ($item) use ($baseDates) {
            return in_array($item['date'], $baseDates)
                ? 'base'
                : 'evaluate';
        };

        // 数据分组后，将每组数据进行处理的函数
        $groupByItemFormatCallback = function ($item) use ($params, $hours) {
            $hourToNull = array_combine($hours, array_fill(0, 48, null));
            $item = array_column($item, $params['quota_key'], 'hour');
            $hourToValue = array_merge($hourToNull, $item);

            $result = [];

            foreach ($hourToValue as $hour => $value) {
                $result[] = [$hour, $value];
            }

            return $result;
        };

        // 数据处理
        $result = Collection::make($result)
            ->groupBy([$baseOrEvaluateCallback, 'date'], $groupByItemFormatCallback)
            ->get();

        $result['info'] = [
            'area_name' => $areaInfo['area_name'],
            'quota_name' => $nameMaps[$params['quota_key']] ?? '',
            'quota_unit' => $units[$params['quota_key']] ?? '',
            'base_time' => [$params['base_start_date'], $params['base_end_date']],
            'evaluate_time' => [$params['evaluate_start_date'], $params['evaluate_end_date']],
        ];

        $jsonResult = json_encode($result);

        $downloadId = md5($jsonResult);

        $result['info']['download_id'] = $downloadId;

        $redisKey = $this->config->item('quota_evaluate_key_prefix') . $downloadId;

        $this->redis_model->setData($redisKey, $jsonResult);

        $this->redis_model->setExpire($redisKey, 30 * 60);

        return $result;
    }

    private function getAreaInfo($areaId)
    {
        $result = $this->db->select('*')
            ->from('area')
            ->where('id', $areaId)
            ->where('delete_at', '1970-01-01')
            ->get()->first_row('array');

        if(!$result || empty($result)) {
            return false;
        }

        return $result;
    }

    //自动替换model数据
    private function replaceArea($data)
    {
        if(!empty($data["id"])){
            $this->db->from($this->tb);
            $row = $this->db
                ->where('id', $data["id"])
                ->where('delete_at', '1970-01-01 00:00:00')
                ->get()->row_array();

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
            return $this->db->insert_id();
        }else{
            $areaId = $data["id"];
            unset($data["id"]);
            $this->db->where('id', $areaId);
            $this->db->update($this->tb, $data);
            return $areaId;
        }
    }

    private function getAreaIdByCityAreaName($cityId, $areaName){
        $this->db->from($this->tb);
        $row = $this->db
            ->where('delete_at', '1970-01-01 00:00:00')
            ->where('city_id', $cityId)
            ->where('area_name', $areaName)
            ->get()->row_array();
        if($row){
            return $row['id'];
        }
        return 0;
    }
}
