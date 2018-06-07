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
        $this->load->model('junction_model');
        $this->load->model('taskdateversion_model');
        $this->load->model('timing_model');
    }

    /**
    * 获取散点图轨迹数据
    * @param $data['task_id']     interger 任务ID
    * @param $data['junction_id'] string   城市ID
    * @param $data['flow_id']     string   相位ID （flow_id）
    * @param $data['search_type'] interger 搜索类型 查询类型 1：按方案查询 0：按时间点查询
    * @param $data['time_point']  string   时间点 当search_type = 0 时 必传 格式：00:00
    * @param $data['time_range']  string   时间段 当search_type = 1 时 必传 格式：00:00-00:30
    * @param $data['timingType']  interger 配时来源 1：人工 2：反推
    * @param $type                string   获取轨迹类型
    * @return json
    */
    public function getTrackData($data)
    {
        if (empty($data)) {
            return [];
        }

        // 获取路口详情 dates start_time end_time movements
        $junction_info = $this->junction_model->getJunctionInfoForScatter($data);
        if (!$junction_info) {
            return [];
        }

        // 获取 mapversion
        $mapversions = $this->taskdateversion_model->select($junction_info[0]['task_id'], explode(',', $junction_info[0]['dates']));
        if (!$mapversions) {
            return [];
        }

        // 获取 配时信息 周期 相位差 绿灯开始结束时间
        /*$timing_data = [
            'junction_id' => $junction_info['junction_id'],
            'dates'       => explode(',', $junction_info['dates']),
            'time_range'  => $junction_info['start_time'] . '-' . date("H:i", strtotime($junction_info['end_time']) - 60),
            'flow_id'     => trim($data['flow_id']),
            'timingType'  => $data['timingType']
        ];
        $timing = $this->timing_model->getFlowTimingInfoForTheTrack($timing_data);
        if (!$timing) {
            return [];
        }*/

        // 组织thrift所需rtimeVec数组
        foreach ($mapversions as $k=>$v) {
            $rtimeVec[$k]['mapVersion'] = $v['map_version_md5'];
            $rtimeVec[$k]['startTS'] = strtotime($v['date'] . ' 00:00');
            $rtimeVec[$k]['endTS'] = strtotime($v['date'] . ' 00:00');
        }

        // 组织thrift所需filterData数组
        $af_condition = [];
        $bf_condition = [];
        $num = [];
        if (!empty($junction_info['af_condition'])) {
            $af_condition = explode(',', trim($junction_info['af_condition']));
        }
        if (!empty($junction_info['bf_condition'])) {
            $bf_condition = explode(',', trim($junction_info['bf_condition']));
        }
        if (!empty($junction_info['num'])) {
            $num = explode(',', trim($junction_info['num']));
        }

        $sample_data[0]['xType'] = 1;
        $sample_data[0]['xData']['all'] = true;
        $sample_data[0]['yType'] = 1;
        $sample_data[0]['yData']['all'] = true;

        $vals = [
            'junctionId' => trim($junction_info[0]['junction_id']),
            'flowId'     => trim($data['flow_id']),
            'rtimeVec'   => $rtimeVec,
            'filterData' => $sample_data
        ];

        $result_data = $this->$type($vals, $junction_info);

        return $result_data;
    }

    /**
    * 获取散点图轨迹数据
    */
    private function getScatterMtraj($vals, $junction_info)
    {
        $track_mtraj = new Track_vendor();
        $res = $track_mtraj->getScatterMtraj($vals);
        $res = (array)$res;
        echo "<pre>res = ";print_r($res);exit;
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

        // 绿灯时长
        $green_time = 0;
        foreach ($timing['signal'] as $k=>$v) {
            // 绿灯
            if ($v['state'] == 1) $green_time += $v['duration'];
        }
        $result_data['signal_detail']['cycle'] = (int)$timing['cycle'];
        $result_data['signal_detail']['red_duration'] = (int)$timing['cycle'] - $green_time;
        $result_data['signal_detail']['green_duration'] = $green_time;

        $result_data['info']['id'] = trim($junction_info['flow_id']);
        $result_data['info']['comment'] = $timing['comment'];
        $result_data['info']['x']['min'] = $junction_info['start_time'];
        $result_data['info']['x']['max'] = $junction_info['end_time'];
        $result_data['info']['y']['min'] = 0;
        $result_data['info']['y']['max'] = (int)$timing['cycle'] * 2;
        return $result_data;
    }
}
