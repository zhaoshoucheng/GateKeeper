<?php
/***************************************************************
 * # 评估类
 * # user:ningxiangbing@didichuxing.com
 * # date:2018-07-25
 ***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\EvaluateService;

class Evaluate extends MY_Controller
{
    protected $evaluateService;

    public function __construct()
    {
        parent::__construct();

        $this->evaluateService = new EvaluateService();
    }

    /**
     * 获取全城路口列表
     *
     * @throws Exception
     */
    public function getCityJunctionList()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $params['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluateService->getCityJunctionList($params);

        $this->response($data);
    }

    /**
     * 获取指标列表
     *
     * @throws Exception
     */
    public function getQuotaList()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->evaluateService->getQuotaList();

        $this->response($data);
    }

    /**
     * 获取相位（方向）列表
     *
     * @throws Exception
     */
    public function getDirectionList()
    {
        $params = $this->input->post(null, true);

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'junction_id' => 'min:1',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = $this->evaluateService->getDirectionList($params);

        $this->response($data);
    }

    /**
     * 获取路口指标排序列表
     *
     * @throws Exception
     */
    public function getJunctionQuotaSortList()
    {
        $params = $this->input->post(null, true);
        // 校验参数
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'quota_key' => 'nullunable',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            throw new \Exception('指标 ' . html_escape($params['quota_key']) . ' 不存在！', ERR_PARAMETERS);
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'quota_key' => strip_tags(trim($params['quota_key'])),
            'date' => date('Y-m-d'),
            'time_point' => date('H:i:s'),
        ];

        if (!empty($params['date'])) {
            $data['date'] = date('Y-m-d', strtotime(strip_tags(trim($params['date']))));
        }

        if (!empty($params['time_point'])) {
            $data['time_point'] = date('H:i:s', strtotime(strip_tags(trim($params['time_point']))));
        }

        $result = $this->evaluateService->getJunctionQuotaSortList($data);

        return $this->response($result);
    }

    /**
     * 获取指标趋势图
     *
     * @throws Exception
     */
    public function getQuotaTrend()
    {
        $params = $this->input->post(null, true);
        // 校验参数
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'junction_id' => 'nullunable',
            'quota_key' => 'nullunable',
            'flow_id' => 'nullunable',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            throw new \Exception('指标 ' . html_escape($params['quota_key']) . ' 不存在！', ERR_PARAMETERS);
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'quota_key' => strip_tags(trim($params['quota_key'])),
            'flow_id' => strip_tags(trim($params['flow_id'])),
            'date' => date('Y-m-d'),
            'time_point' => date('H:i:s'),
        ];

        if (!empty($params['date'])) {
            $data['date'] = date('Y-m-d', strtotime(strip_tags(trim($params['date']))));
        }

        if (!empty($params['time_point'])) {
            $data['time_point'] = date('H:i:s', strtotime(strip_tags(trim($params['time_point']))));
        }

        $result = $this->evaluateService->getQuotaTrend($data);

        $this->response($result);
    }

    /**
     * 获取路口地图数据
     *
     * @throws Exception
     */
    public function getJunctionMapData()
    {
        $params = $this->input->post(null, true);
        // 校验参数
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'junction_id' => 'nullunable',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
        ];

        $result = $this->evaluateService->getJunctionMapData($data);

        $this->response($result);
    }

    /**
     * 指标评估对比
     *
     * @throws Exception
     */
    public function quotaEvaluateCompare()
    {
        $params = $this->input->post(null, true);
        // 校验参数
        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'junction_id' => 'nullunable',
            'quota_key' => 'nullunable',
            'flow_id' => 'nullunable',
        ]);

        if (!$validate['status']) {
            throw new \Exception($validate['errmsg'], ERR_PARAMETERS);
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            throw new \Exception('指标 ' . html_escape($params['quota_key']) . ' 不存在！', ERR_PARAMETERS);
        }

        $data = [
            'city_id' => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'quota_key' => strip_tags(trim($params['quota_key'])),
            'flow_id' => strip_tags(trim($params['flow_id'])),
        ];

        /**
         * 如果基准时间没有传，则默认：上周工作日
         * 如果评估时间没有传，则默认：本周工作日
         */
        if (empty($params['base_start_time'])) {
            // 上周一作为开始时间 Y-m-d H:i:s
            $baseStartTime = strtotime('monday last week');
        } else {
            $baseStartTime = strtotime($params['base_start_time']);
        }

        if (empty($params['base_end_time'])) {
            // 上周五作为结束时间 本周减去2天减1秒
            $baseEndTime = strtotime('monday this week') - 2 * 24 * 3600 - 1;
        } else {
            $baseEndTime = strtotime($params['base_end_time']);
        }

        // 用于返回
        $data['base_time_start_end'] = [
            'start' => date('Y-m-d H:i:s', $baseStartTime),
            'end' => date('Y-m-d H:i:s', $baseEndTime),
        ];

        // 计算基准时间段具体每天日期
        for ($i = $baseStartTime; $i < $baseEndTime; $i += 24 * 3600) {
            $data['base_time'][] = $i;
        }

        if (empty($params['evaluate_time']) || !is_array($params['evaluate_time'])) {
            // 开始时间 本周一开始时间
            $startTime = strtotime('monday this week');

            // 当前星期几 如果星期一，结束时间要到当前时间 如果大于星期一，结束时间要前一天 如果是周日则向前推两天
            $week = date('w');
            if ($week == 0) { // 周日
                $endTime = strtotime(date('Y-m-d') . '-2 days') + 24 * 3600 - 1;
            } elseif ($week == 1) { // 周一
                $endTime = time();
            } else {
                $endTime = strtotime(date('Y-m-d') . '-1 days') + 24 * 3600 - 1;
            }

            $params['evaluate_time'][] = [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        } else {
            foreach ($params['evaluate_time'] as $k => $v) {
                $params['evaluate_time'][$k] = [
                    'start_time' => isset($v['start_time']) ? strtotime($v['start_time']) : 0,
                    'end_time' => isset($v['end_time']) ? strtotime($v['end_time']) : 0,
                ];
            }
        }

        // 用于返回
        $data['evaluate_time_start_end'] = [];

        // 处理评估时间，计算各评估时间具体日期
        foreach ($params['evaluate_time'] as $k => $v) {
            for ($i = $v['start_time']; $i <= $v['end_time']; $i += 24 * 3600) {
                $data['evaluate_time'][$k][$i] = $i;
            }
            $data['evaluate_time_start_end'][$k] = [
                'start' => date('Y-m-d H:i:s', $v['start_time']),
                'end' => date('Y-m-d H:i:s', $v['end_time']),
            ];
        }

        $result = $this->evaluateService->quotaEvaluateCompare($data);

        $this->response($result);
    }

    /**
     * 获取评估对比数据下载地址
     *
     * @throws Exception
     */
    public function downloadEvaluateData()
    {
        $params = $this->input->post(null, true);

        if (!isset($params['download_id'])) {
            throw new Exception('download_id 的值不能为空', ERR_PARAMETERS);
        }

        $data = $this->evaluateService->downloadEvaluateData($params);

        $this->response($data);
    }

    /**
     * 评估数据下载地址
     *
     * @throws Exception
     * @throws PHPExcel_Exception
     */
    public function download()
    {
        $params = $this->input->get();

        if (!isset($params['download_id'])) {
            throw new Exception('download_id 的值不能为空', ERR_PARAMETERS);
        }

        $this->evaluateService->download($params);
    }
}
