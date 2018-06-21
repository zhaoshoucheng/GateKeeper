<?php
/**
* 时段优化数据模型
*/
use Didi\Cloud\ItsMap\Todsplit_vendor;

date_default_timezone_set('Asia/Shanghai');
class Timeframeoptimize_model extends CI_Model
{
    private $tb = 'junction_index';
    private $db = '';

    function __construct() {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $this->load->model('timing_model');
        $this->load->model('taskdateversion_model');
    }

    /**
     * 优化-单点时段优化路口列表
     * @param data['task_id']      interger 任务ID
     * @param data['city_id']      interger 城市ID
     * @return array 转换为json的格式:{"dataList":[{"logic_junction_id":"2017030116_4861479","lng":"117.16051","lat":"36.66729","name":"经十东路-凤山路"}],"junctionTotal":1}
     */
    public function getAllJunctions($data)
    {
        // 获取全城路口模板 没有模板就没有lng、lat = 画不了图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($data['city_id']);
        if (count($allCityJunctions) < 1 || !$allCityJunctions || !is_array($allCityJunctions)) {
            return [];
        }
        // 获取任务路口ids
        $where = 'task_id = ' . $data['task_id'] . ' and type = 0';
        $query = $this->db->select('*')
            ->from($this->tb)
            ->where($where)
            ->group_by('junction_id')
            ->get();
        $newDataJids = [];
        foreach ($query->result_array() as $v) {
            $newDataJids[] = $v['junction_id'];
        }
        // 验证路口是否存在
        $result_data['dataList'] = array_reduce($allCityJunctions,function($v,$w) use ($newDataJids){
            if(empty($v)) {
                $v = [];
            }
            if(isset($w["logic_junction_id"]) && in_array($w["logic_junction_id"],$newDataJids)){
                $v[] = array(
                    "logic_junction_id"=>$w["logic_junction_id"],
                    "lng"=>$w["lng"],
                    "lat"=>$w["lat"],
                    "name"=>$w["name"],
                );
            }
            return $v;
        });

        $result_data['junctionTotal'] = count($result_data['dataList']);
        $result_data['center'] = $this->getJunctionCenter($result_data['dataList']);
        return $result_data;
    }

    // 计算地图中心坐标
    private function getJunctionCenter($dataList){
        $count_lng = 0;
        $count_lat = 0;
        $qcount = count($dataList);
        foreach ($dataList as $v){
            $count_lng += $v['lng'];
            $count_lat += $v['lat'];
        }
        return ["lng"=>round($count_lng / $qcount, 6), "lat"=>round($count_lat / $qcount, 6), ];
    }

    /**
    * 获取路口相位集合 按nema排序
    * @param $data['task_id']     interger Y 任务ID
    * @param $data['junction_id'] string   Y 路口ID
    * @param $data['dates']       array    Y 评估/诊断日期
    * @param $data['time_range']  string   Y 任务时间段
    * @param $data[timingType']   interger Y 配时数据源 0，全部；1，人工；2，配时反推；3，信号机上报
    * @return array
    */
    public function getJunctionMovements($data)
    {
        if (empty($data)) {
            return [];
        }

        $this->db->select('movements');
        $this->db->from($this->tb);

        $where = 'task_id = ' . $data['task_id'] . " and junction_id = '{$data['junction_id']}' and type = 0";
        $this->db->where($where);

        $list = $this->db->get();
        if (!$list) {
            return [];
        }

        $list = $list->result_array();

        $result = $this->formatDataForJunctionMovementsByNema($list, $data);

        return $result;
    }

    /**
    * 对路口相位集合按nema排序进行格式化
    * @param $list                array    Y 一个任务一个路口的全任务时段集合
    * @param $data['task_id']     interger Y 任务ID
    * @param $data['junction_id'] string   Y 路口ID
    * @param $data[dates']        array    Y 评估/诊断日期
    * @param $data[time_range']   string   Y 任务时间段
    * @param $data[timingType']   interger Y 配时数据源 0，全部；1，人工；2，配时反推；3，信号机上报
    */
    private function formatDataForJunctionMovementsByNema($list, $data)
    {
        // 获取相位ID=>相位名称
        $flowIdName = $this->timing_model->getFlowIdToName($data);

        foreach ($list as $k=>&$v) {
            $v['movements'] = json_decode($v['movements'], true);
            foreach ($v['movements'] as $vv) {
                $result[$vv['movement_id']] = $flowIdName[$vv['movement_id']];
            }
        }

        // NEMA排序 南左、北直、西左、东直、北左、南直、东左、西直
        $phase = [
                '南左' => 10,
                '北直' => 20,
                '西左' => 30,
                '东直' => 40,
                '北左' => 50,
                '南直' => 60,
                '东左' => 70,
                '西直' => 80
            ];

        $newRes = [];
        foreach ($result as $k=>$v) {
            if (array_key_exists($v, $phase) && !array_key_exists($phase[$v], $newRes)) {
                $newRes[$phase[$v]]['logic_flow_id'] = $k;
                $newRes[$phase[$v]]['name'] = $v;
            } else {
                $newRes[mt_rand(100, 900) + mt_rand(1, 99)] = [
                    'logic_flow_id' => $k,
                    'name'          => $v
                ];
            }
        }
        ksort($newRes);
        $newRes = array_values($newRes);

        return $newRes;
    }

    /**
    * 获取路口信息
    * @param $data['task_id']      interger Y 任务ID
    * @param $data['junction_id']  string   Y 路口ID
    * @return array
    */
    public function getJunctionInfoForScatter($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $select = 'task_id, junction_id, dates, clock_shift';
        $where  = "task_id = {$data['task_id']} and junction_id = '{$data['junction_id']}' and type = 1";

        $result = $this->db->select($select)
                            ->from($this->tb)
                            ->where($where)
                            ->limit(1)
                            ->get();
        if (!$result) {
            return [];
        }

        $result = $result->result_array();

        return $result;
    }

    /**
    * 获取时段划分优化方案
    * @param $data['task_id']     interger Y 任务ID
    * @param $data['junction_id'] string   Y 路口ID
    * @param $data['dates']       array    Y 评估/诊断日期
    * @param $data['movements']   array    Y 路口相位集合
    * @param $data['divide_num']  interger Y 划分数量
    * @return array
    */
    public function getTodOptimizePlan($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        // 获取 mapversion
        $mapversions = $this->taskdateversion_model->select($data['task_id'], $data['dates']);
        if (!$mapversions) {
            return [];
        }

        // 组织thrift所需version数组
        foreach ($mapversions as $k=>$v) {
            $version[$k]['map_version'] = $v['map_version_md5'];
            $version[$k]['date'] = $v['date'];
        }

        $data = [
            'dates' => implode(',', $data['dates']),
            'junction_movements' => [
                $data['junction_id'] => $data['movements'],
            ],
            'tod_cnt' => $data['divide_num'],
            'version' => $version,
        ];

        $service = new Todsplit_vendor();

        $res = $service->getTodPlan($data);
        var_dump($res);exit;

    }
}
