<?php
/***************************************************************
# 评估类
# user:ningxiangbing@didichuxing.com
# date:2018-07-25
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Evaluate extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('evaluate_model');
        $this->load->config('realtime_conf');
    }

    /**
     * 获取全城路口列表
     * @param city_id    interger Y 城市ID
     * @return json
     */
    public function getCityJunctionList()
    {

    }

    /**
     * 获取指标列表
     * @param city_id    interger Y 城市ID
     * @return json
     */
    public function getQuotaList()
    {

    }

    /**
     * 获取相位（方向）列表
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @return json
     */
    public function getDirectionList()
    {

    }

    /**
     * 获取路口指标排序列表
     * @param city_id     interger Y 城市ID
     * @param quota_key   string   Y 指标KEY
     * @param date        string   N 日期 格式：Y-m-d 默认当前日期
     * @param time_point  string   N 时间 格式：H:i:s 默认当前时间
     * @return json
     */
    public function getJunctionQuotaSortList()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'   => 'min:1',
                'quota_key' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '指标 ' . html_escape($params['quota_key']) . ' 不存在！';
            return;
        }

        $data = [
            'city_id'    => intval($params['city_id']),
            'quota_key'  => strip_tags(trim($params['quota_key'])),
            'date'       => date('Y-m-d'),
            'time_point' => date('H:i:s'),
        ];

        if (!empty($params['date'])) {
            $data['date'] = date('Y-m-d', strtotime(strip_tags(trim($params['date']))));
        }

        if (!empty($params['time_point'])) {
            $data['time_point'] = date('H:i:s', strtotime(strip_tags(trim($params['time_point']))));
        }

        $result = $this->evaluate_model->getJunctionQuotaSortList($data);

        return $this->response($result);
    }

    /**
     * 获取指标趋势图
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @param quota_key   string   Y 指标KEY
     * @param flow_id     string   Y 相位ID
     * @param date        string   N 日期 格式：Y-m-d 默认当前日期
     * @param time_point  string   N 时间 格式：H:i:s 默认当前时间
     * @return json
     */
    public function getQuotaTrend()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
                'quota_key'   => 'nullunable',
                'flow_id'     => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!array_key_exists($params['quota_key'], $this->config->item('real_time_quota'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '指标 ' . html_escape($params['quota_key']) . ' 不存在！';
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'quota_key'   => strip_tags(trim($params['quota_key'])),
            'flow_id'     => strip_tags(trim($params['flow_id'])),
            'date'        => date('Y-m-d'),
            'time_point'  => date('H:i:s'),
        ];

        if (!empty($params['date'])) {
            $data['date'] = date('Y-m-d', strtotime(strip_tags(trim($params['date']))));
        }

        if (!empty($params['time_point'])) {
            $data['time_point'] = date('H:i:s', strtotime(strip_tags(trim($params['time_point']))));
        }

        $result = $this->evaluate_model->getQuotaTrend($data);

        return $this->response($result);
    }

    /**
     * 获取路口地图数据
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @return json
     */
    public function getJunctionMapData()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
        ];

        $result = $this->evaluate_model->getJunctionMapData($data);

        return $this->response($result);
    }

    /**
     * 指标评估对比
     * @param city_id         interger Y 城市ID
     * @param junction_id     string   Y 路口ID
     * @param quota_key       string   Y 指标KEY
     * @param flow_id         string   Y 相位ID
     * @param base_start_time string   N 基准开始时间 格式：yyyy-mm-dd hh:ii:ss
     * 例：2018-08-06 00:00:00 默认：上一周工作日开始时间（上周一 yyyy-mm-dd 00:00:00）
     * @param base_end_time   string   N 基准结束时间 格式：yyyy-mm-dd hh:ii:ss
     * 例：2018-08-07 23:59:59 默认：上一周工作日结束时间（上周五 yyyy-mm-dd 23:59:59）
     * @param evaluate_time   string   N 评估时间 有可能会有多个评估时间段，固使用json格式的字符串
     * evaluate_time 格式：
     * [
     *     {
     *         "start_time": "2018-08-01 00:00:00", // 开始时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-06 00:00:00
     *         "end_time": "2018-08-07 23:59:59"    // 结束时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-07 23:59:59
     *     },
     *     ......
     * ]
     * @return json
     */
    public function quotaEvaluateCompare()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params, [
                'city_id'     => 'min:1',
                'junction_id' => 'nullunable',
                'quota_key'   => 'nullunable',
                'flow_id'     => 'nullunable',
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data = [
            'city_id'     => intval($params['city_id']),
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'quota_key'   => strip_tags(trim($params['quota_key'])),
            'flow_id'     => strip_tags(trim($params['flow_id'])),
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
            $baseEndTime = strtotime('monday this week') - 2 * 24 * 3600;
        } else {
            $baseEndTime = strtotime($params['base_end_time']);
        }

        // 计算基准时间段具体每天日期
        for ($i = $baseStartTime; $i < $baseEndTime; $i += 24 * 3600) {
            $data['base_time'][] = $i;
        }

        echo "<pre>base_time = ";print_r($data['base_time']);

        if (empty($params['evaluate_time'])) {
            // 开始时间 本周一开始时间
            $startTime = strtotime('monday this week');

            // 当前星期几 如果星期一，结束时间要到当前时间 如果大于星期一，结束时间要前一天 如果是周日则向前推两天
            $week = date('w');
            if ($week == 0) { // 周日
                $endTime = strtotime(date('Y-m-d') . '-2 days');
            } else if ($week == 1) { // 周一
                $endTime = time();
            } else {
                $endTime = strtotime(date('Y-m-d') . '-1 days');
            }

            $params['evaluate_time'][] = [
                'start_time' => $startTime,
                'end_time'   => $endTime,
            ];
        } else {
            // 解析json
            $params['evaluate_time'] = json_decode($params['evaluate_time'], true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数 evaluate_time 非json格式的文本！';
                return;
            }

            foreach ($params['evaluate_time'] as $k=>$v) {
                $params['evaluate_time'][$k] = [
                    'start_time' => strtotime($v['start_time']),
                    'end_time' => strtotime($v['end_time']),
                ];
            }
        }

        // 处理评估时间，计算各评估时间具体日期
        foreach ($params['evaluate_time'] as $k=>$v) {
            for ($i = $v['start_time']; $i <= $v['end_time']; $i += 24 * 3600) {
                $data['evaluate_time'][$k][$i] = $i;
            }
        }

        echo "<pre> data = ";print_r($data);

        $result = $this->evaluate_model->quotaEvaluateCompare($data);

        return $this->response($result);
    }

    /**
     * 下载评估对比数据
     * @param
     * @return json
     */
    public function downloadEvaluateData()
    {

    }
}
