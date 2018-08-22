<?php
/**
 * 路口分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class JunctionReport extends MY_Controller
{
    protected $weeks = [1,2,3,4,5,6,7];

    protected $quotas = [
        'stop_delay' => [
            'name'      => '停车延误',
            'unit'      => '秒',
        ],
        'stop_time_cycle' => [
            'name'      => '停车次数', // 指标名称
            'unit'      => '次',
        ],
        'spillover_rate' => [
            'name'      => '溢流指标',
            'unit'      => '',
        ],
        'queue_length' => [
            'name'      => '排队长度',
            'unit'      => '米',
        ],
        'stop_rate' => [
            'name'      => '失调指标',
            'unit'      => '',
        ],
        'speed' => [
            'name'      => '通过速度',
            'unit'      => '',
        ]
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('junctionreport_model');
    }

    public function queryQuotaInfo()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        if(!isset($params['logic_junction_id']) || empty(trim($params['logic_junction_id']))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of logic_junction_id is empty.';
            return;
        }

        if(!isset($params['start_date']) || date('Y-m-d', strtotime($params['start_date'])) !== $params['start_date']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of start_date is wrong.';
            return;
        }

        if(!isset($params['end_date']) || date('Y-m-d', strtotime($params['end_date'])) !== $params['end_date']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of end_date is wrong.';
            return;
        }

        if(!isset($params['week']) || !in_array($params['week'], $this->weeks)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of week is wrong.';
            return;
        }

        if(!isset($params['schedule_start']) || date('H:i:s', strtotime($params['schedule_start'])) !== $params['schedule_start']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        if(!isset($params['schedule_end']) || date('H:i:s', strtotime($params['schedule_end'])) !== $params['schedule_end']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        if(!isset($params['key']) || !array_key_exists($params['key'], $this->quotas)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of key is wrong.';
            return;
        }

        $data = $this->junctionreport_model->queryQuotaInfo($params);

        return $this->response($data);
    }
}