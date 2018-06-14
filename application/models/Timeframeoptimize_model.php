<?php
/**
* 时段优化数据模型
*/

date_default_timezone_set('Asia/Shanghai');
class Timeframeoptimize_model extends CI_Model
{
    private $tb = 'junction_index';
    private $db = '';

    function __construct() {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $this->load->model('timing_model');
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
        echo "<pre>result = ";print_r($newRes);
    }
}
