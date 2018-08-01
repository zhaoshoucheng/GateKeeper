<?php
/***************************************************************
# 路口类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Junction extends MY_Controller
{
    private $eamil_to = 'ningxiangbing@didichuxing.com';
    public function __construct()
    {
        parent::__construct();
        $this->load->model('junction_model');
        $this->load->model('timing_model');
        $this->load->config('nconf');
    }

    /**
    * 评估-获取全城路口指标信息
    * @param task_id     interger  Y 任务ID
    * @param city_id     interger  Y 城市ID
    * @param type        interger  Y 指标计算类型 1：统合 0：时间点
    * @param time_point  string    N 评估时间点 指标计算类型为1时非空
    * @param confidence  interger  Y 置信度 0:全部 1:高 2:低
    * @param quota_key   string    Y 指标key
    * @return json
    */
    public function getAllCityJunctionInfo()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'    => 'min:1',
                'type'       => 'min:0',
                'city_id'    => 'min:1',
                'quota_key'  => 'nullunable',
                'confidence' => 'min:0'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data['task_id'] = (int)$params['task_id'];
        $data['type'] = (int)$params['type'];
        $data['city_id'] = $params['city_id'];

        // type == 0时 time_point为必传项
        if ($data['type'] == 0 && empty(trim($params['time_point']))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The time_point cannot be empty.';
            return;
        }
        if ($data['type'] == 0) {
            $data['time_point'] = trim($params['time_point']);
        }

        // 判断置信度是否存在
        if (!array_key_exists($params['confidence'], $this->config->item('confidence'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of confidence ' . $params['confidence'] . ' is wrong.';
            return;
        }
        $data['confidence'] = $params['confidence'];

        // 判断指标KEY是否正确
        $data['quota_key'] = strtolower(trim($params['quota_key']));
        if (!array_key_exists($data['quota_key'], $this->config->item('junction_quota_key'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of quota_key ' . $data['quota_key'] . ' is wrong.';
            return;
        }

        // 获取全城路口指标信息
        $data = $this->junction_model->getAllCityJunctionInfo($data);

        return $this->response($data);
    }

    /**
    * 获取路口指标详情
    * @param task_id         interger Y 任务ID
    * @param dates           array    Y 评估/诊断日期 [20180102,20180103,....]
    * @param junction_id     string   Y 逻辑路口ID
    * @param search_type     interger Y 查询类型 1：按方案查询 0：按时间点查询
    * @param time_point      string   N 时间点 当search_type = 0 时 必传
    * @param time_range      string   N 方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
    * @param type            interger Y 详情类型 1：指标详情页 2：诊断详情页
    * @param task_time_range string   Y 评估/诊断任务开始结束时间 格式："06:00-09:00"
    * @return json
    */
    public function getJunctionQuotaDetail()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'          => 'min:1',
                'junction_id'      => 'nullunable',
                'task_time_range'  => 'nullunable',
                'type'             => 'min:1',
                'search_type'      => 'min:0'
            ]
        );

        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if ((int)$params['search_type'] == 1) { // 按方案查询
            if(empty($params['time_range'])){
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_range cannot be empty.';
                return;
            }
            $time_range = array_filter(explode('-', $params['time_range']));
            if (empty($time_range[0]) || empty($time_range[1])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_range is wrong.';
                return;
            }
            $data['time_range'] = strip_tags(trim($params['time_range']));
        } else {
            if (empty($params['time_point'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_point cannot be empty.';
                return;
            }
            $data['time_point'] = strip_tags(trim($params['time_point']));
        }

        $task_time_range = array_filter(explode('-', $params['task_time_range']));
        if (empty($task_time_range[0]) || empty($task_time_range[1])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The task_time_range is wrong.';
            return;
        }

        if (!is_array($params['dates']) || empty($params['dates'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The dates cannot be empty and must be array.';
            return;
        }

        $data['task_id'] = intval($params['task_id']);
        $data['dates'] = $params['dates'];
        $data['junction_id'] = strip_tags(trim($params['junction_id']));
        $data['search_type'] = intval($params['search_type']);
        $data['type'] = intval($params['type']);
        $data['task_time_range'] = strip_tags(trim($params['task_time_range']));
        $data['timingType'] = $this->timingType;

        // 获取路口指标详情
        $res = $this->junction_model->getFlowQuotas($data);

        return $this->response($res);
    }

    /**
    * 获取诊断列表页简易路口详情
    * @param task_id         interger Y 任务ID
    * @param dates           array    Y 评估/诊断日期 [20180102,20180103,....]
    * @param junction_id     string   Y 逻辑路口ID
    * @param time_point      string   Y 时间点
    * @param task_time_range string   Y 评估/诊断任务开始结束时间 格式："06:00-09:00"
    * @param diagnose_key    array    Y 诊断问题KEY
    * @return json
    */
    public function getDiagnosePageSimpleJunctionDetail()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'         => 'min:1',
                'junction_id'     => 'nullunable',
                'task_time_range' => 'nullunable',
                'time_point'      => 'nullunable'
            ]
        );

        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $task_time_range = array_filter(explode('-', $params['task_time_range']));
        if (empty($task_time_range[0]) || empty($task_time_range[1])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The task_time_range is wrong.';
            return;
        }

        if (!is_array($params['dates']) || empty($params['dates'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The dates cannot be empty and must be array.';
            return;
        }

        if (empty($params['diagnose_key']) || !is_array($params['diagnose_key'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The diagnose_key cannot be empty and must be array.';
            return;
        }

        $diagnoseConf = $this->config->item('diagnose_key');
        foreach ($params['diagnose_key'] as $v) {
            if (!array_key_exists($v, $diagnoseConf)) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The diagnose_key is error.';
                return;
            }
        }

        $data = [
            'task_id'         => intval($params['task_id']),
            'dates'           => $params['dates'],
            'junction_id'     => strip_tags(trim($params['junction_id'])),
            'time_point'      => strip_tags(trim($params['time_point'])),
            'task_time_range' => strip_tags(trim($params['task_time_range'])),
            'diagnose_key'    => $params['diagnose_key'],
            'timingType'      => $this->timingType
        ];

        // 获取诊断列表页简易路口详情
        $res = $this->junction_model->getDiagnosePageSimpleJunctionDetail($data);

        return $this->response($res);
    }

    /**
    * 获取路口问题趋势图
    * @param task_id         interger Y 任务ID
    * @param junction_id     string   Y 路口ID
    * @param time_point      string   Y 时间点
    * @param task_time_range string   Y 任务时间段
    * @param diagnose_key    array    N 诊断问题KEY 当路口正常状态时可为空
    * @return json
    */
    public function getJunctionQuestionTrend()
    {
        $params = $this->input->post();

        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'         => 'min:1',
                'junction_id'     => 'nullunable',
                'task_time_range' => 'nullunable',
                'time_point'      => 'nullunable'
            ]
        );

        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $task_time_range = array_filter(explode('-', $params['task_time_range']));
        if (empty($task_time_range[0]) || empty($task_time_range[1])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The task_time_range is wrong.';
            return;
        }

        $diagnose_key = [];
        if (!empty($params['diagnose_key'])) {
            if (!is_array($params['diagnose_key'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The diagnose_key cannot be empty and must be array.';
                return;
            }

            $diagnoseConf = $this->config->item('diagnose_key');
            foreach ($params['diagnose_key'] as $v) {
                if (!array_key_exists($v, $diagnoseConf)) {
                    $this->errno = ERR_PARAMETERS;
                    $this->errmsg = 'The diagnose_key is error.';
                    return;
                }
            }
            $diagnose_key = $params['diagnose_key'];
        }

        $data = [
            'task_id'         => intval($params['task_id']),
            'junction_id'     => strip_tags(trim($params['junction_id'])),
            'time_point'      => strip_tags(trim($params['time_point'])),
            'task_time_range' => strip_tags(trim($params['task_time_range'])),
            'diagnose_key'    => $diagnose_key,
        ];

        // 获取诊断列表页简易路口详情
        $res = $this->junction_model->getJunctionQuestionTrend($data);

        return $this->response($res);
    }

    /**
    * 获取配时方案及配时详情
    * @param dates           array  Y 评估/诊断日期
    * @param junction_id     string Y 路口ID
    * @param task_time_range string Y 任务时间段
    * @return json
    */
    public function getJunctionTiming()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'junction_id'      => 'nullunable',
                'task_time_range'  => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if (!is_array($params['dates']) || empty($params['dates'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The dates cannot be empty and must be array.';
            return;
        }
        $data['dates'] = $params['dates'];
        $data['junction_id'] = strip_tags(trim($params['junction_id']));
        $data['time_range'] = strip_tags(trim($params['task_time_range']));
        $data['timingType'] = $this->timingType;
        $timing = $this->timing_model->getJunctionsTimingInfo($data);

        return $this->response($timing);
    }

    /**
    * 诊断-获取全城路口诊断问题列表
    * @param task_id        interger  Y 任务ID
    * @param city_id        interger  Y 城市ID
    * @param type           interger  Y 指标计算类型 1：统合 0：时间点
    * @param time_point     string    N 时间点 指标计算类型为1时非空
    * @param confidence     interger  Y 置信度 0:全部 1:高 2:低
    * @param diagnose_key   array     Y 诊断key
    * @return json
    */
    public function getAllCityJunctionsDiagnoseList()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'    => 'min:1',
                'city_id'    => 'min:1',
                'type'       => 'min:0',
                'confidence' => 'min:0'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $data['task_id'] = (int)$params['task_id'];
        $data['city_id'] = $params['city_id'];
        $data['type'] = (int)$params['type'];

        if ($data['type'] == 0 && empty(trim($params['time_point']))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The time_point cannot be empty.';
            return;
        }
        if ($data['type'] == 0) {
            $data['time_point'] = trim($params['time_point']);
        }

        if (!array_key_exists($params['confidence'], $this->config->item('confidence'))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of confidence ' . $params['confidence'] . ' is wrong.';
            return;
        }
        $data['confidence'] = $params['confidence'];

        if (!empty($params['diagnose_key'])) {
            $diagnoseKeyConf = $this->config->item('diagnose_key');
            foreach ($params['diagnose_key'] as $v) {
                if (!array_key_exists($v, $diagnoseKeyConf)) {
                    $this->errno = ERR_PARAMETERS;
                    $this->errmsg = 'The value of diagnose_key ' . html_escape($v) . ' is wrong.';
                    return;
                }
            }
        } else {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The diagnose_key cannot be empty and must be array.';
            return;
        }
        $data['diagnose_key'] = $params['diagnose_key'];

        $res = $this->junction_model->getJunctionsDiagnoseList($data);

        return $this->response($res);
    }

    /**
    * 获取问题趋势
    * @param task_id    interger Y 任务ID
    * @param confidence interger Y 置信度
    * @return json
    */
    public function getQuestionTrend()
    {
        $params = $this->input->post();
        if (!isset($params['task_id']) || $params['task_id'] < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The task_id is error.';
            return;
        }
        $data['task_id'] = intval($params['task_id']);
        $data['confidence'] = $params['confidence'] ?? 0;

        $result = [];

        $result = $this->junction_model->getQuestionTrend($data);

        return $this->response($result);
    }

    /**
    * 诊断-诊断问题排序列表
    * @param task_id       interger Y 任务ID
    * @param city_id       interger Y 城市ID
    * @param time_point    string   Y 时间点
    * @param diagnose_key  array    Y 诊断key
    * @param confidence    interger Y 置信度
    * @param orderby       interger N 诊断问题排序 1：按指标值正序 2：按指标值倒序 默认2
    * @return json
    */
    public function getDiagnoseRankList()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'task_id'    => 'min:1',
                'time_point' => 'nullunable',
                'city_id'    => 'min:1',
                'confidence' => 'min:0'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        $res = [];

        if (empty($params['diagnose_key']) || !is_array($params['diagnose_key'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The diagnose_key cannot be empty and must be array.';
            return;
        }

        $diagnoseKeyConf = $this->config->item('diagnose_key');
        $params['diagnose_key'] = array_filter($params['diagnose_key']);
        foreach ($params['diagnose_key'] as $k=>$v) {
            if (!array_key_exists($v, $diagnoseKeyConf)) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The value of diagnose_key ' . html_escape($v) . ' is wrong.';
                return;
            }
        }

        if (!isset($params['orderby'])) {
            $params['orderby'] = 2;
        }

        $data = [
            'task_id'      => intval($params['task_id']),
            'city_id'      => intval($params['city_id']),
            'time_point'   => strip_tags(trim($params['time_point'])),
            'diagnose_key' => $params['diagnose_key'],
            'confidence'   => intval($params['confidence']),
            'orderby'      => intval($params['orderby'])
        ];

        $res = $this->junction_model->getDiagnoseRankList($data);
        return $this->response($res);
    }

    /**
    * 获取路口地图绘制数据
    * @param junction_id     string   Y 逻辑路口ID
    * @param dates           string   Y 评估/诊断任务日期 ['20180102','20180103']
    * @param search_type     interger Y 查询类型 1：按方案查询 0：按时间点查询
    * @param time_point      string   N 时间点 格式 00:00 PS:当search_type = 0 时 必传
    * @param time_range      string   N 方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传 时间段
    * @param task_time_range string   Y 评估/诊断任务开始结束时间 格式 00:00-24:00
    * @return json
    */
    public function getJunctionMapData()
    {
        $params = $this->input->post();
        // 校验参数
        $validate = Validate::make($params,
            [
                'junction_id'     => 'nullunable',
                'search_type'     => 'min:0',
                'task_time_range' => 'nullunable'
            ]
        );
        if (!$validate['status']) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = $validate['errmsg'];
            return;
        }

        if ((int)$params['search_type'] == 1) { // 按方案查询
            if (empty($params['time_range'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_range cannot be empty.';
                return;
            }
            $time_range = array_filter(explode('-', $params['time_range']));
            if (empty($time_range[0]) || empty($time_range[1])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_range is wrong.';
                return;
            }
            $data['time_range'] = strip_tags(trim($params['time_range']));
        } else {
            if (empty($params['time_point'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'The time_point cannot be empty.';
                return;
            }
            $data['time_point'] = strip_tags(trim($params['time_point']));
        }

        $task_time_range = array_filter(explode('-', $params['task_time_range']));
        if (empty($task_time_range[0]) || empty($task_time_range[1])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The task_time_range is wrong.';
            return;
        }

        if (!is_array($params['dates']) || count($params['dates']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The dates cannot be empty and must be array.';
            return;
        }

        $data['dates'] = $params['dates'];
        $data['junction_id'] = strip_tags(trim($params['junction_id']));
        $data['search_type'] = intval($params['search_type']);
        $data['task_time_range'] = strip_tags(trim($params['task_time_range']));
        $data['timingType'] = $this->timingType;

        $result = $this->junction_model->getJunctionMapData($data);

        return $this->response($result);
    }

    /**
    * 测试登录
    */
    public function testLogin()
    {
        echo "welcome!";
        sendMail($this->eamil_to, '测试', 'ok');
        exit;
    }
}
