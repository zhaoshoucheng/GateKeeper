<?php
/********************************************
# desc:    散点图模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-04-13
********************************************/

use Didi\Cloud\ItsMap\Track_vendor;

class Scatter_model extends CI_Model
{

    private $email_to = 'ningxiangbing@didichuxing.com';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('timeframeoptimize_model');
        $this->load->model('taskdateversion_model');
        $this->load->model('timing_model');
    }

    /**
    * 获取散点图轨迹数据
    * @param $data['task_id']     interger 任务ID
    * @param $data['junction_id'] string   城市ID
    * @param $data['dates']       array    评估/诊断日期
    * @param $data['time_range']  string   任务时间段
    * @param $data['flow_id']     string   相位ID （flow_id）
    * @param $data['timingType']  interger 配时数据源 0，全部；1，人工；2，配时反推；3，信号机上报
    * @return json
    */
    public function getTrackData($data)
    {
        if (empty($data)) {
            return [];
        }

        // 获取 mapversion
        $mapversions = $this->taskdateversion_model->select($data['task_id'], $data['dates']);
        if (!$mapversions) {
            return [];
        }

        // 获取配时信息 周期 相位差 绿灯开始结束时间 所有相位最大周期
        $timingData = [
            'junction_id' => $data['junction_id'],
            'dates'       => $data['dates'],
            'time_range'  => $data['time_range'],
            'flow_id'     => trim($data['flow_id']),
            'timingType'  => $data['timingType']
        ];
        $timing = $this->timing_model->gitFlowTimingByOptimizeScatter($timingData);
        if (!$timing) {
            return [];
        }

        // 组织thrift所需rtimeVec数组
        foreach ($mapversions as $k=>$v) {
            $rtimeVec[$k]['mapVersion'] = $v['map_version_md5'];
            $rtimeVec[$k]['startTS'] = strtotime($v['date'] . ' 00:00');
            $rtimeVec[$k]['endTS'] = strtotime($v['date'] . ' 00:00');
        }

        // 组织thrift所需filterData数组
        $sample_data = [
            [
                'xType' => 1,
                'xData' => [
                    'all' => true
                ],
                'yType' => 1,
                'yData' => [
                    'all' => true
                ],
                'num'   => 3000
            ]
        ];

        $vals = [
            'junctionId' => trim($data['junction_id']),
            'flowId'     => trim($data['flow_id']),
            'rtimeVec'   => $rtimeVec,
            'filterData' => $sample_data
        ];

        $result_data = $this->getScatterMtraj($vals, $timing);

        return $result_data;
    }

    /**
    * 获取散点图轨迹数据
    */
    private function getScatterMtraj($vals, $timing)
    {
        $track_mtraj = new Track_vendor();
        $res = $track_mtraj->getScatterMtraj($vals);
        $res = (array)$res;
        if ($res['errno'] != 0) {
            return [];
        }

        if (!empty($res['scatterPoints'])) {
            foreach ($res['scatterPoints'] as $k=>&$v) {
                $v = (array)$v;
                $time = $v['stopLineTimestamp'];
                $temp_time = date("H:i:s", $time);
                // 时间
                $result_data['dataList'][$time][0] = $temp_time;
                // 值
                $result_data['dataList'][$time][1] = round($v['stopDelayBefore']);
            }
        }

        if (!empty($result_data['dataList'])) {
            ksort($result_data['dataList']);
            $result_data['dataList'] = array_values($result_data['dataList']);
        }

        $result_data = [
            'planList' => $timing['planList'],
            'info'     => [
                'id'      => $timing['info']['logic_flow_id'],
                'comment' => $timing['info']['comment'],
                'x'       => [
                    'min' => '00:00:00',
                    'max' => '23:59:00',
                ],
                'y'       => [
                    'min' => 0,
                    'max' => intval($timing['maxCycle']) * 2,
                ],
            ],
        ];

        return $result_data;
    }
}
