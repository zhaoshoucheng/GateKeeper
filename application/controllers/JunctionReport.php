<?php
/**
 * 路口分析报告模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class JunctionReport extends MY_Controller
{
    protected $weeks = [1,2,3,4,5,6,7];

    protected $quotas;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('junctionreport_model');
        $this->config->load('report_conf');

        $this->quotas = $this->config->item('quotas');
    }

    /**
     * 单点路口分析 - 数据获取
     */
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

        if(!isset($params['evaluate_start_date']) || date('Y-m-d', strtotime($params['evaluate_start_date'])) !== $params['evaluate_start_date']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of evaluate_start_date is wrong.';
            return;
        }

        if(!isset($params['evaluate_end_date']) || date('Y-m-d', strtotime($params['evaluate_end_date'])) !== $params['evaluate_end_date']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of evaluate_end_date is wrong.';
            return;
        }

        if(!isset($params['week']) || !is_array($params['week'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of week is wrong.';
            return;
        }

        if(!isset($params['schedule_start']) || date('H:i', strtotime($params['schedule_start'])) !== $params['schedule_start']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of schedule_start is wrong.';
            return;
        }

        if(!isset($params['schedule_end']) || date('H:i', strtotime($params['schedule_end'])) !== $params['schedule_end']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of schedule_end is wrong.';
            return;
        }

        if(!isset($params['quota_key']) || !array_key_exists($params['quota_key'], $this->quotas)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of quota_key is wrong.';
            return;
        }

        if(!isset($params['type']) || !in_array($params['type'], [1,2])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of type is wrong.';
            return;
        }

        $dates = $this->getDates($params);

        if(empty($dates)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '选择的日期范围不能为空';
            return;
        }

        $hours = $this->getHours($params);

        if(empty($hours)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '选择的时间范围不能为空';
            return;
        }

        $data = $this->junctionreport_model->queryQuotaInfo($params, $dates, $hours);

        return $this->response($data);
    }

    /**
     * 获取指定时间段内的半小时划分集合
     * @param $data
     * @return array
     */
    private function getHours($data)
    {
        $start = strtotime($data['schedule_start']);
        $end = strtotime($data['schedule_end']);

        $results = [];

        $time = $start;
        while($time <= $end) {
            $results[] = date('H:i', $time);
            $time += (30 * 60);
        }

        return $results;
    }

    /**
     * 获取指定时间段内指定星期的日期集合
     * @param $data
     * @return array
     */
    private function getDates($data)
    {
        $start = strtotime($data['evaluate_start_date']);
        $end = strtotime($data['evaluate_end_date']);
        $weeks = $data['week'];

        $results = [];

        $time = $start;
        while($time <= $end) {
            if(in_array(date('w', $time), $weeks)) {
                $results[] = date('Y-m-d', $time);
            }
            $time += (60 * 60 * 24);
        }

        return $results;
    }

}