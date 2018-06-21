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
    * 获取时段划分优化方案
    * @param $data['task_id']     interger Y 任务ID
    * @param $data['junction_id'] string   Y 路口ID
    * @param $data['dates']       array    Y 评估/诊断日期
    * @param $data['movements']   array    Y 路口相位集合
    * @param $data['divide_num']  interger Y 划分数量
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
        var_dump($res);exit;

    }
}
