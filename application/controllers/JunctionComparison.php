<?php
/***************************************************************
# 单点路口优化对比报告类
# user:ningxiangbing@didichuxing.com
# date:2018-08-23
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class JunctionComparison extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('junctioncomparison_model');
        $this->load->config('junctioncomparison_conf');
    }

    /**
     * 获取单点路口优化对比
     * @param logic_junction_id   string   Y 路口ID
     * @param city_id             interger Y 城市ID
     * @param base_start_date     string   Y 基准开始日期 格式：yyyy-mm-dd
     * @param base_end_date       string   Y 基准结束日期 格式：yyyy-mm-dd
     * @param evaluate_start_date string   Y 评估开始日期 格式：yyyy-mm-dd
     * @param evaluate_end_date   string   Y 评估结束日期 格式：yyyy-mm-dd
     * @param week                array    Y 星期 0-6
     * @param schedule_start      string   Y 时段开始时间 例：00:00
     * @param schedule_end        string   Y 时段结束时间 例：00:30
     * @param quota_key           array    Y 指标key 例['queue_length', 'stop_delay']
     * @return json
     */
    public function queryQuotaInfo()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params, [
                'city_id'             => 'min:1',
                'logic_junction_id'   => 'nullunable',
                'base_start_date'     => 'nullunable',
                'base_end_date'       => 'nullunable',
                'evaluate_start_date' => 'nullunable',
                'evaluate_end_date'   => 'nullunable',
                'schedule_start'      => 'nullunable',
                'schedule_end'        => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $base_end_date = $params['base_end_date'] . ' 23:59:59';
        $base_start_date = $params['base_start_date'] . ' 00:00:00';
        if (strtotime($base_end_date) - strtotime($base_start_date) < 24 * 3600 - 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '基准日期段最小为1天！';
            return;
        }
        if (strtotime($base_end_date) - strtotime($base_start_date) > 31 * 24 * 3600 - 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '基准日期段最大为31天！';
            return;
        }

        $evaluate_end_date = $params['evaluate_end_date'] . '23:59:59';
        $evaluate_start_date = $params['evaluate_start_date'] . '00:00:00';
        if (strtotime($evaluate_end_date) - strtotime($evaluate_start_date) < 24 * 3600 - 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '评估日期段最小为1天！';
            return;
        }
        if (strtotime($evaluate_end_date) - strtotime($evaluate_start_date) > 31 * 24 * 3600 - 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '评估日期段最大为31天！';
            return;
        }

        if (strtotime($params['schedule_end']) - strtotime($params['schedule_start']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '时段结束时间须大于时段开始开始时间！';
            return;
        }

        if (empty($params['week']) || !is_array($params['week'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数 week 须为数组且不能为空！';
            return;
        }

        if (empty($params['quota_key']) || !is_array($params['quota_key'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数 quota_key 须为数组且不能为空！';
            return;
        }

        $quotaConf = $this->config->item('quotas');
        foreach ($params['quota_key'] as $v) {
            if (!array_key_exists(strip_tags(trim($v)), $quotaConf)) {
                $msg = $quotaConf[strip_tags(trim($v))]['name'] ?? strip_tags(trim($v));
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '路口优化对比报告没有 ' . $msg . ' 指标！';
                return;
            }
        }

        $data = [
            'city_id'             => intval($params['city_id']),
            'logic_junction_id'   => strip_tags(trim($params['logic_junction_id'])),
            'base_start_date'     => strip_tags(trim($params['base_start_date'])),
            'base_end_date'       => strip_tags(trim($params['base_end_date'])),
            'evaluate_start_date' => strip_tags(trim($params['evaluate_start_date'])),
            'evaluate_end_date'   => strip_tags(trim($params['evaluate_end_date'])),
            'schedule_start'      => strip_tags(trim($params['schedule_start'])),
            'schedule_end'        => strip_tags(trim($params['schedule_end'])),
            'week'                => $params['week'],
        ];

        $result = [];

        foreach ($params['quota_key'] as $v) {
            $data['quota_key'] = strip_tags(trim($v));
            $result[html_escape(trim($v))] = $this->junctioncomparison_model->getQuotaInfo($data);
        }

        return $this->response($result);
    }
}
