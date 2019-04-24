<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Services\DiagnosisNoTimingService;
class DiagnosisNoTiming extends MY_Controller
{
    protected $dianosisService;

    public function __construct()
    {
        parent::__construct();

        $this->dianosisService = new DiagnosisNoTimingService();
        $this->load->model('junction_model');
        $this->load->model('timing_model');
        $this->load->config('nconf');
        $this->setTimingType();
    }

    /**
     * 获取路口指标详情
     * @param $params ['dates']           array    Y 评估/诊断日期 [20180102,20180103,....]
     * @param $params ['junction_id']     string   Y 逻辑路口ID
     * @param $params ['time_range']      string   N 方案的开始结束时间 (07:00-09:15) 时间点时传半小时间隔
     * @return json
     */
    public function getJunctionQuotaDetail()
    {
        //json格式转换为post格式
        $params = file_get_contents("php://input");
        if(!empty(json_decode($params,true))){
            $_POST = json_decode($params,true);
        }

        // 校验参数
        $this->validate([
            'time_range' => 'required|trim|regex_match[/\d{2}:\d{2}-\d{2}:\d{2}/]',
            'city_id' => 'required|min_length[1]',
            'junction_id' => 'required|min_length[4]',
            'dates' => 'is_array',
        ],[
            'dates' => array(
                'is_array' => '%s 必须是一个数组',
            ),
        ]);
        $params = [];
        $params["time_range"] = $this->input->post("time_range",true);
        $params["city_id"] = $this->input->post("city_id",true);
        $params["junction_id"] = $this->input->post("junction_id",true);
        $params["dates"] = $this->input->post("dates",true);

        // 获取路口指标详情
        $res = $this->dianosisService->getFlowQuotas($params);
        $this->response($res);
    }

    /**
     * 获取诊断路口问题趋势
     * @param $params ['city_id']       string   Y 城市ID
     * @param $params ['dates']         array    Y 评估/诊断日期 [20180102,20180103,....]
     * @return json
     */
    public function getJunctionQuestionTrend()
    {
        //json格式转换为post格式
        $params = file_get_contents("php://input");
        if(!empty(json_decode($params,true))){
            $_POST = json_decode($params,true);
        }

        // 校验参数
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'dates' => 'required',
        ]);
        $params = [];
        $params["city_id"] = $this->input->post("city_id",true);
        $params["dates"] = $this->input->post("dates",true);

        $res = $this->dianosisService->getFlowQuotas($params);
        $this->response($res);
    }

    /**
     * 诊断路口问题top20
     * @param $params ['city_id']       string   Y 城市ID
     * @param $params ['dates']         array    Y 评估/诊断日期 [20180102,20180103,....]
     * @param $params ['hour']          array    Y 评估/诊断日期 [09:30]
     * @return json
     */
    public function getDiagnoseRankList()
    {
        //json格式转换为post格式
        $params = file_get_contents("php://input");
        if(!empty(json_decode($params,true))){
            $_POST = json_decode($params,true);
        }

        // 校验参数
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'dates' => 'required|trim',
            'hour' => 'required|trim|regex_match[/\d{2}:\d{2}/]',
        ]);
        $params = [];
        $params["city_id"] = $this->input->post("city_id",true);
        $params["dates"] = $this->input->post("dates",true);
        $params["hour"] = $this->input->post("hour",true);

        $res = $this->dianosisService->getFlowQuotas($params);
        $this->response($res);
    }

    /**
     * 诊断路口问题详情
     * @param $params ['city_id']       string   Y 城市ID
     * @param $params ['dates']         array    Y 评估/诊断日期 [20180102,20180103,....]
     * @param $params ['hour']          array    Y 评估/诊断日期 [09:30]
     * @return json
     */
    public function getAllCityJunctionsDiagnoseList()
    {
        //json格式转换为post格式
        $params = file_get_contents("php://input");
        if(!empty(json_decode($params,true))){
            $_POST = json_decode($params,true);
        }

        // 校验参数
        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'dates' => 'required|trim',
            'hour' => 'required|trim|regex_match[/\d{2}:\d{2}/]',
        ]);
        $params = [];
        $params["city_id"] = $this->input->post("city_id",true);
        $params["dates"] = $this->input->post("dates",true);
        $params["hour"] = $this->input->post("hour",true);

        $res = $this->dianosisService->getFlowQuotas($params);
        $this->response($res);
    }
}
