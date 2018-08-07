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
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getCityJunctionList($data);

        $this->response($data);
    }

    /**
     * 获取指标列表
     * @param city_id    interger Y 城市ID
     * @return json
     */
    public function getQuotaList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getQuotaList($data);

        $this->response($data);
    }

    /**
     * 获取相位（方向）列表
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @return json
     */
    public function getDirectionList()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        $data['city_id'] = $params['city_id'];

        if(!isset($params['junction_id']) || empty(trim($params['junction_id']))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of junction_id is empty.';
            return;
        }

        $data['junction_id'] = $params['junction_id'];

        $data['date'] = $params['date'] ?? date('Y-m-d');

        $data = $this->evaluate_model->getDirectionList($data);

        $this->response($data);
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

    }

    /**
     * 获取路口地图数据
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @return json
     */
    public function getJunctionMapData()
    {

    }

    /**
     * 指标评估对比
     * @param city_id     interger Y 城市ID
     * @param junction_id string   Y 路口ID
     * @param quota_key   string   Y 指标KEY
     * @param flow_id     string   Y 相位ID
     * @param base_start_time string N 基准开始时间 格式：yyyy-mm-dd hh:ii:ss
     *                                 例：2018-08-06 00:00:00 默认：当前日期前一天的前6天开始时间
     * @param base_end_time   string N 基准结束时间 格式：yyyy-mm-dd hh:ii:ss
     *                                 例：2018-08-06 00:00:00 默认：当前日期前一天结束时间
     * @param evaluate_time   string N 评估时间 有可能会有多个评估时间段，固使用json格式的字符串
     * evaluate_time 格式：
     *     [
     *         {
     *             "start_time": "2018-08-01", // 开始时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-06 00:00:00
     *             "end_time": "2018-08-03"  // 结束时间 格式：yyyy-mm-dd hh:ii:ss 例：2018-08-06 00:00:00
     *         },
     *         ......
     *     ]
     * @return json
     */
    public function quotaEvaluateCompare()
    {

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
