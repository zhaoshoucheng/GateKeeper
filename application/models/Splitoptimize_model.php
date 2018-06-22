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
        echo "<pre>";print_r($timing);exit;

        $signal = [
            [
                'logic_flow_id'=> '2017030116_i_74479370_2017030116_o_74186480',
                'green_start'=> [0],
                'green_duration'=>[52],
                'yellow'=>[3],
                'red_clean'=>[0]
            ],
            [
                'logic_flow_id'=>'2017030116_i_74479370_2017030116_o_74188361',
                'green_start'=>[0],
                'green_duration'=>[52],
                'yellow'=>[3],
                'red_clean'=>[0]
            ]
        ];

        $ndata = [
            'dates' => implode(',', $data['dates']),
            'logic_junction_id' => $data['junction_id'],
            'start_time' => '00:00:00',
            'end_time' => '05:00:00',
            'cycle' => 80,
            'offset' => 31,
            'clock_shift'=> 0,
            'signal'=>$signal,
            'version'=>$version
        ];

        $service = new Todsplit_vendor();

        $res = $service->getSplitPlan($ndata);
        if (empty($res)) {
            return [];
        }

        $res = (array)$res;
        $res['green_split_opt_signal_plan'] = (array)$res['green_split_opt_signal_plan'];
        $res['green_split_opt_signal_plan']['signal'] = (array)$res['green_split_opt_signal_plan']['signal'];

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
                'comment'       => '';
            ];
            foreach ($v['green_start'] as $kk=>$vv) {
                $result['movements'][$k]['signal'][$kk] = [
                    'g_start_time' => $vv,
                    'g_duration'   => $v['green_duration'][$kk],
                    'yellowLight'  => $v['yellow'][$kk],
                ];
            }
        }

        return $result;
    }
}
