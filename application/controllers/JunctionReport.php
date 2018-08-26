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

        $data = $this->junctionreport_model->queryQuotaInfo($params);

        return $this->response($data);
    }
}