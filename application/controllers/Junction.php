<?php
/***************************************************************
# 路口类
# user:ningxiangbing@didichuxing.com
# date:2018-03-02
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\JunctionsService;

class Junction extends MY_Controller
{
    protected $junctionsService;

    public function __construct()
    {
        parent::__construct();

        $this->junctionsService = new junctionsService();

        $this->load->model('junction_model');
        $this->load->model('timing_model');
        $this->load->config('nconf');
        $this->setTimingType();
    }

    /**
     * 评估-获取全城路口指标信息
     * @param $params['task_id']     int       Y 任务ID
     * @param $params['city_id']     int       Y 城市ID
     * @param $params['type']        int       Y 指标计算类型 1：统合 0：时间点
     * @param $params['time_point']  string    N 评估时间点 指标计算类型为1时非空
     * @param $params['confidence']  int       Y 置信度 0:全部 1:高 2:低
     * @param $params['quota_key']   string    Y 指标key
     * @throws Exception
     * @return json
     */
    public function getAllCityJunctionInfo()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'task_id'    => 'required|is_natural_no_zero',
            'type'       => 'required|is_natural',
            'quota_key'  => 'required|trim|in_list[' . implode(',', array_keys($this->config->item('junction_quota_key'))) . ']',
            'confidence' => 'required|in_list[' . implode(',', array_keys($this->config->item('confidence'))) . ']',
            'city_id'    => 'required|is_natural_no_zero',
        ]);

        $data['task_id'] = (int)$params['task_id'];
        $data['type'] = (int)$params['type'];
        $data['city_id'] = $params['city_id'];
        $data['confidence'] = $params['confidence'];
        $data['quota_key'] = strtolower(trim($params['quota_key']));
        $data['time_point'] = '';

        // type == 0时 time_point为必传项
        if ($data['type'] == 0) {
            if (empty(trim($params['time_point']))) {
                throw new \Exception('参数time_point传递错误！', ERR_PARAMETERS);
            }
            $data['time_point'] = trim($params['time_point']);
        }

        // 获取全城路口指标信息
        $data = $this->junctionsService->getAllCityJunctionInfo($data);

        $this->response($data);
    }

    /**
     * 获取路口指标详情
     * @param $params['task_id']         int      Y 任务ID
     * @param $params['dates']           array    Y 评估/诊断日期 [20180102,20180103,....]
     * @param $params['junction_id']     string   Y 逻辑路口ID
     * @param $params['search_type']     int      Y 查询类型 1：按方案查询 0：按时间点查询
     * @param $params['time_point']      string   N 时间点 当search_type = 0 时 必传
     * @param $params['time_range']      string   N 方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
     * @param $params['type']            int      Y 详情类型 1：指标详情页 2：诊断详情页
     * @param $params['task_time_range'] string   Y 评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @return json
     */
    public function getJunctionQuotaDetail()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'task_id'         => 'required|is_natural_no_zero',
            'type'            => 'required|is_natural_no_zero',
            'junction_id'     => 'required|min_length[4]',
            'task_time_range' => 'required|exact_length[11]|regex_match[/\d{2}:\d{2}-\d{2}:\d{2}/]',
            'search_type'     => 'required|is_natural',
        ]);

        $data['time_range'] = '';
        $data['time_point'] = '';
        if ((int)$params['search_type'] == 1) { // 按方案查询
            if(empty($params['time_range'])){
                throw new \Exception('参数time_range不能为空！', ERR_PARAMETERS);
            }
            $time_range = array_filter(explode('-', $params['time_range']));
            if (empty($time_range[0]) || empty($time_range[1])) {
                throw new \Exception('参数time_range传递错误！', ERR_PARAMETERS);
            }
            $data['time_range'] = strip_tags(trim($params['time_range']));
        } else {
            if (empty($params['time_point'])) {
                throw new \Exception('参数time_point不能为空！', ERR_PARAMETERS);
            }
            $data['time_point'] = strip_tags(trim($params['time_point']));
        }

        if (empty($params['dates']) || !is_array($params['dates'])) {
            throw new \Exception('参数dates不为空且为数组格式！', ERR_PARAMETERS);
        }

        $data['task_id'] = intval($params['task_id']);
        $data['dates'] = $params['dates'];
        $data['junction_id'] = strip_tags(trim($params['junction_id']));
        $data['search_type'] = intval($params['search_type']);
        $data['type'] = intval($params['type']);
        $data['task_time_range'] = strip_tags(trim($params['task_time_range']));
        $data['timingType'] = $this->timingType;

        // 获取路口指标详情
        $res = $this->junctionsService->getFlowQuotas($data);

        $this->response($res);
    }

    /**
     * 获取诊断列表页简易路口详情
     * @param $params['task_id']         int      Y 任务ID
     * @param $params['dates']           array    Y 评估/诊断日期 [20180102,20180103,....]
     * @param $params['junction_id']     string   Y 逻辑路口ID
     * @param $params['time_point']      string   Y 时间点 06:00
     * @param $params['task_time_range'] string   Y 评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $params['diagnose_key']    array    Y 诊断问题KEY
     * @return json
     */
    public function getDiagnosePageSimpleJunctionDetail()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'task_id'         => 'required|is_natural_no_zero',
            'time_point'      => 'required|exact_length[5]|regex_match[/\d{2}:\d{2}/]',
            'junction_id'     => 'required|min_length[4]',
            'task_time_range' => 'required|exact_length[11]|regex_match[/\d{2}:\d{2}-\d{2}:\d{2}/]',
        ]);

        if (empty($params['dates']) || !is_array($params['dates'])) {
            throw new \Exception('参数dates不为空且为数组格式！', ERR_PARAMETERS);
        }

        if (empty($params['diagnose_key']) || !is_array($params['diagnose_key'])) {
            throw new \Exception('参数diagnose_key必须为数组且不可为空！', ERR_PARAMETERS);
        }

        $diagnoseConf = $this->config->item('diagnose_key');
        foreach ($params['diagnose_key'] as $v) {
            if (!array_key_exists($v, $diagnoseConf)) {
                throw new \Exception('参数diagnose_key传递错误！', ERR_PARAMETERS);
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
        $res = $this->junctionsService->getDiagnosePageSimpleJunctionDetail($data);

        $this->response($res);
    }

    /**
     * 获取路口问题趋势图
     * @param $params['task_id']         int      Y 任务ID
     * @param $params['junction_id']     string   Y 路口ID
     * @param $params['time_point']      string   Y 时间点 06:00
     * @param $params['task_time_range'] string   Y 任务时间段 格式："06:00-09:00"
     * @param $params['diagnose_key']    array    N 诊断问题KEY 当路口正常状态时可为空
     * @return json
     */
    public function getJunctionQuestionTrend()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'task_id'         => 'required|is_natural_no_zero',
            'time_point'      => 'required|exact_length[5]|regex_match[/\d{2}:\d{2}/]',
            'junction_id'     => 'required|min_length[4]',
            'task_time_range' => 'required|exact_length[11]|regex_match[/\d{2}:\d{2}-\d{2}:\d{2}/]',
        ]);

        $diagnose_key = [];
        if (!empty($params['diagnose_key'])) {
            if (!is_array($params['diagnose_key'])) {
                throw new \Exception('参数diagnose_key必须为数组且不可为空！', ERR_PARAMETERS);
            }

            $diagnoseConf = $this->config->item('diagnose_key');
            foreach ($params['diagnose_key'] as $v) {
                if (!array_key_exists($v, $diagnoseConf)) {
                    throw new \Exception('参数diagnose_key传递错误！', ERR_PARAMETERS);
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
        $res = $this->junctionsService->getJunctionQuestionTrend($data);

        $this->response($res);
    }

    /**
    * 获取配时方案及配时详情
    * @param dates           array  Y 评估/诊断日期
    * @param junction_id     string Y 路口ID
    * @param task_time_range string Y 任务时间段 07:00-09:30
    * @return json
    */
    public function getJunctionTiming()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'junction_id'     => 'required|min_length[4]',
            'task_time_range' => 'required|exact_length[11]|regex_match[/\d{2}:\d{2}-\d{2}:\d{2}/]',
        ]);

        if (!is_array($params['dates']) || empty($params['dates'])) {
            throw new \Exception('参数dates必须为数组且不可为空！', ERR_PARAMETERS);
        }

        $data = [
            'dates'       => $params['dates'],
            'junction_id' => strip_tags(trim($params['junction_id'])),
            'time_range'  => strip_tags(trim($params['task_time_range'])),
            'timingType'  => $this->timingType,
        ];
        $timing = $this->junctionsService->getJunctionsTimingInfo($data);

        return $this->response($timing);
    }

    /**
    * 诊断-获取全城路口诊断问题列表
    * @param $params['task_id']        int       Y 任务ID
    * @param $params['city_id']        int       Y 城市ID
    * @param $params['type']           int       Y 指标计算类型 1：统合 0：时间点
    * @param $params['time_point']     string    N 时间点 指标计算类型为1时非空
    * @param $params['confidence']     int       Y 置信度 0:全部 1:高 2:低
    * @param $params['diagnose_key']   array     Y 诊断key
    * @return json
    */
    public function getAllCityJunctionsDiagnoseList()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'task_id'    => 'required|is_natural_no_zero',
            'type'       => 'required|is_natural',
            'confidence' => 'required|in_list[' . implode(',', array_keys($this->config->item('confidence'))) . ']',
            'city_id'    => 'required|is_natural_no_zero',
        ]);

        $data = [
            'task_id'    => (int)$params['task_id'],
            'city_id'    => $params['city_id'],
            'confidence' => $params['confidence'],
            'type'       => (int)$params['type'],
        ];

        $data['time_point'] = '';
        if ($data['type'] == 0) {
            if (empty(trim($params['time_point']))) {
                throw new \Exception('参数time_point不能为空！', ERR_PARAMETERS);
            }
            $data['time_point'] = trim($params['time_point']);
        }

        if (!empty($params['diagnose_key'])) {
            $diagnoseKeyConf = $this->config->item('diagnose_key');
            foreach ($params['diagnose_key'] as $v) {
                if (!array_key_exists($v, $diagnoseKeyConf)) {
                    throw new \Exception('参数diagnose_key传递错误！', ERR_PARAMETERS);
                }
            }
        } else {
            throw new Exception("参数diagnose_key必须为数组且不可为空！", ERR_PARAMETERS);
        }
        $data['diagnose_key'] = $params['diagnose_key'];

        $res = $this->junctionsService->getJunctionsDiagnoseList($data);

        return $this->response($res);
    }

    /**
    * 获取问题趋势
    * @param $params['task_id']    int Y 任务ID
    * @param $params['confidence'] int Y 置信度
    * @return json
    */
    public function getQuestionTrend()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'task_id'    => 'required|is_natural_no_zero',
            'confidence' => 'required|in_list[' . implode(',', array_keys($this->config->item('confidence'))) . ']',
        ]);

        $data = [
            'task_id'    => intval($params['task_id']),
            'confidence' => $params['confidence'],
        ];

        $result = $this->junctionsService->getQuestionTrend($data);

        return $this->response($result);
    }

    /**
    * 诊断-诊断问题排序列表
    * @param task_id       int      Y 任务ID
    * @param city_id       int      Y 城市ID
    * @param time_point    string   Y 时间点
    * @param diagnose_key  array    Y 诊断key
    * @param confidence    int      Y 置信度
    * @param orderby       int      N 诊断问题排序 1：按指标值正序 2：按指标值倒序 默认2
    * @return json
    */
    public function getDiagnoseRankList()
    {
        $params = $this->input->post(NULL, TRUE);

        // 校验参数
        $this->validate([
            'task_id'    => 'required|is_natural_no_zero',
            'time_point' => 'required|exact_length[5]|regex_match[/\d{2}:\d{2}/]',
            'confidence' => 'required|in_list[' . implode(',', array_keys($this->config->item('confidence'))) . ']',
            'city_id'    => 'required|is_natural_no_zero',
        ]);
        // 校验参数
        $validate = Validate::make($params, [
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
            $this->errmsg = '参数diagnose_key必须为数组且不可为空！';
            return;
        }

        $diagnoseKeyConf = $this->config->item('diagnose_key');
        $params['diagnose_key'] = array_filter($params['diagnose_key']);
        foreach ($params['diagnose_key'] as $k=>$v) {
            if (!array_key_exists($v, $diagnoseKeyConf)) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = '参数diagnose_key传递错误！';
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
    * @param search_type     int      Y 查询类型 1：按方案查询 0：按时间点查询
    * @param time_point      string   N 时间点 格式 00:00 PS:当search_type = 0 时 必传
    * @param time_range      string   N 方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传 时间段
    * @param task_time_range string   Y 评估/诊断任务开始结束时间 格式 00:00-24:00
    * @return json
    */
    public function getJunctionMapData()
    {
        $params = $this->input->post(NULL, TRUE);
        // 校验参数
        $validate = Validate::make($params, [
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
                $this->errmsg = 'time_range不能为空！';
                return;
            }
            $time_range = array_filter(explode('-', $params['time_range']));
            if (empty($time_range[0]) || empty($time_range[1])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'time_range传递错误！';
                return;
            }
            $data['time_range'] = strip_tags(trim($params['time_range']));
        } else {
            if (empty($params['time_point'])) {
                $this->errno = ERR_PARAMETERS;
                $this->errmsg = 'time_point不能为空！';
                return;
            }
            $data['time_point'] = strip_tags(trim($params['time_point']));
        }

        $task_time_range = array_filter(explode('-', $params['task_time_range']));
        if (empty($task_time_range[0]) || empty($task_time_range[1])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'task_time_range传递错误！';
            return;
        }

        if (!is_array($params['dates']) || count($params['dates']) < 1) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数dates必须为数组且不可为空！';
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
