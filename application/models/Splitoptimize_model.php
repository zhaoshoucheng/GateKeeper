<?php
/**
* 绿信比数据模型
*/
use Didi\Cloud\ItsMap\Todsplit_vendor;

date_default_timezone_set('Asia/Shanghai');
class Splitoptimize_model extends CI_Model
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
    * 获取绿信比优化方案
    * @param $data['task_id']          interger Y 任务ID
    * @param $data['junction_id']      string   Y 路口ID
    * @param $data['dates']            array    Y 评估/诊断日期
    * @param $data['time_range']       string   Y 方案开始结束时间 00:00-09:00
    * @param $data['task_time_range']  string   Y 任务时段 00:00-09:00
    * @param $data['yellowLight']      interger Y 黄灯时长
    * @param $data['timingType']       interger Y 配时来源 1：人工 2：反推
    * @return array
    */
    public function getSplitOptimizePlan($data)
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

        $tdata = [
            'dates'       => $data['dates'],
            'junction_id' => strip_tags(trim($data['junction_id'])),
            'time_range'  => strip_tags(trim($data['task_time_range'])),
            'yellowLight' => $data['yellowLight'],
            'timingType'  => $data['timingType'],
        ];
        $timing = $this->timing_model->getTimingPlan($tdata);
        if (empty($timing)) {
            return [];
        }

        $timeRangeArr = explode('-', $data['time_range']);
        list($start, $end) = $timeRangeArr;

        // 转为时间戳用于比较
        $start = strtotime($start);
        $end = strtotime($end);

        $signal = [];
        $flowIdName = [];
        foreach ($timing as $v) {
            if (strtotime($v['plan']['start_time']) == $start && strtotime($v['plan']['end_time']) == $end) {
                // thrift参数
                $ndata['start_time'] = $v['plan']['start_time'];
                $ndata['end_time'] = $v['plan']['end_time'];
                $ndata['cycle'] = $v['plan']['cycle'];
                $ndata['offset'] = $v['plan']['offset'];
                $ndata['clock_shift'] = 0;
                foreach ($v['movements'] as $kk=>$vv) {
                    $signal[$kk]['logic_flow_id'] = $vv['info']['logic_flow_id'];
                    $flowIdName[$vv['info']['logic_flow_id']] = $vv['info']['comment'];
                    foreach ($vv['signal'] as $kkk=>$vvv) {
                        $signal[$kk]['signal_of_green'][$kkk]['green_start'] = $vvv['g_start_time'];
                        $signal[$kk]['signal_of_green'][$kkk]['green_duration'] = $vvv['g_duration'];
                        $signal[$kk]['signal_of_green'][$kkk]['yellow'] = $vvv['yellowLight'];
                        $signal[$kk]['signal_of_green'][$kkk]['red_clean'] = 0;
                    }
                }
            }
        }

        if (empty($signal)) {
            return [];
        }
        $ndata['dates'] = implode(',', $data['dates']);
        $ndata['logic_junction_id'] = strip_tags(trim($data['junction_id']));
        $ndata['signal'] = $signal;
        $ndata['version'] =$version;

        $service = new Todsplit_vendor();

        $res = $service->getSplitPlan($ndata);
        if (empty($res)) {
            return [];
        }

        $res = (array)$res;
        $res['green_split_opt_signal_plan'] = (array)$res['green_split_opt_signal_plan'];

        if ($res['errno'] != 0 || empty($res['green_split_opt_signal_plan']['signal'])) {
            return [];
        }

        $result['plan'] = [
            'start_time' => $res['green_split_opt_signal_plan']['start_time'],
            'emd_time'   => $res['green_split_opt_signal_plan']['end_time'],
            'cycle'      => $res['green_split_opt_signal_plan']['cycle'],
            'offset'     => $res['green_split_opt_signal_plan']['offset'],
        ];
        foreach ($res['green_split_opt_signal_plan']['signal'] as $k=>&$v) {
            $v = (array)$v;
            $result['movements'][$k]['info'] = [
                'logic_flow_id' => $v['logic_flow_id'],
                'comment'       => $flowIdName[$v['logic_flow_id']],
            ];
            foreach ($v['signal_of_green'] as $kk=>&$vv) {
                $vv = (array)$vv;
                $result['movements'][$k]['signal'][$kk] = [
                    'g_start_time' => $vv['green_start'],
                    'g_duration'   => $vv['green_duration'],
                    'yellowLight'  => $vv['yellow'],
                ];
            }
        }

        // 绿信比优化建议模板
        $splitOptSuggestConf = $this->config->item('split_opt_suggest');
        // 优化建议
        $result['suggest'] = [];
        $res['advice_mes'] = (array)$res['advice_mes'];

        foreach ($splitOptSuggestConf as $k=>$v) {
            if (!empty($res['advice_mes'][$k])) {
                foreach ($res['advice_mes'][$k] as $kk=>$vv) {
                    $result['suggest'][$k][$kk]
                        = str_replace(':movement', $flowIdName[$vv], $v);
                }
            }
        }
        if (!empty($result['suggest'])) {
            $result['suggest'] = array_values($result['suggest']);
        }

        return $result;
    }
}
