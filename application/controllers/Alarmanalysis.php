<?php
/***************************************************************
# 报警分析
# user:ningxiangbing@didichuxing.com
# date:2018-11-19
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AlarmanalysisService;
use Services\RealtimeQuotaService;

class Alarmanalysis extends MY_Controller
{
    protected $alarmanalysisService;
    protected $realtimeQuotaService;

    public function __construct()
    {
        parent::__construct();

        $this->alarmanalysisService = new alarmanalysisService();
        $this->realtimeQuotaService = new realtimeQuotaService();
        $this->load->config('alarmanalysis_conf');
    }

    /**
     * 城市/路口报警分析接口
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 开始日期与结束日期一致认为查询当天从0点到当前整点的数据
     * @return json
     */
    public function alarmAnalysis()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $this->validate([
            'frequency_type' => 'required|in_list[' . implode(',', array_keys($this->config->item('frequency_type'))) . ']',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'city_id'        => 'required|is_natural_no_zero',
        ]);

        $params['logic_junction_id'] = !empty($params['logic_junction_id'])
                                        ? strip_tags(trim($params['logic_junction_id']))
                                        : '';

        if (strtotime($params['end_time']) - strtotime($params['start_time']) < 0) {
            throw new \Exception('结束日期需大于等于开始日期！', ERR_PARAMETERS);
        }
        $result = $this->alarmanalysisService->alarmAnalysis($params);
        $this->response($result);
    }

    //济南项目使用
    public function newAlarmAnalysis()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $this->validate([
            'frequency_type' => 'required|in_list[' . implode(',', array_keys($this->config->item('frequency_type'))) . ']',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'city_id'        => 'required|is_natural_no_zero',
        ]);

        $params['logic_junction_id'] = !empty($params['logic_junction_id'])
            ? strip_tags(trim($params['logic_junction_id']))
            : '';

        if (strtotime($params['end_time']) - strtotime($params['start_time']) < 0) {
            throw new \Exception('结束日期需大于等于开始日期！', ERR_PARAMETERS);
        }
        $result = $this->alarmanalysisService->alarmAnalysis($params);
        $this->response($result);
    }

    /**
     * 7日报警均值  济南项目使用
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @return json
     */
    public function newSevenDayAlarmMeanValue()
    {
        $params = $this->input->post(null, true);
        // 校验参数
        $this->validate([
            'frequency_type' => 'required|in_list[' . implode(',', array_keys($this->config->item('frequency_type'))) . ']',
            'city_id'        => 'required|is_natural_no_zero',
        ]);

        $params['logic_junction_id'] = !empty($params['logic_junction_id'])
            ? strip_tags(trim($params['logic_junction_id']))
            : '';

        $result = $this->alarmanalysisService->sevenDayAlarmMeanValue($params);

        $this->response($result);
    }

    /**
     * 城市/路口报警时段分布接口
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 开始日期与结束日期一致认为查询当天从0点到当前整点的数据
     * @return json
     */
    public function alarmTimeDistribution()
    {
        $params = $this->input->post(null, true);

        // 校验参数
        $this->validate([
            'frequency_type' => 'required|in_list[' . implode(',', array_keys($this->config->item('frequency_type'))) . ']',
            'start_time'     => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'end_time'       => 'required|trim|regex_match[/\d{4}-\d{2}-\d{2}/]',
            'city_id'        => 'required|is_natural_no_zero',
        ]);

        $params['logic_junction_id'] = !empty($params['logic_junction_id'])
                                        ? strip_tags(trim($params['logic_junction_id']))
                                        : '';

        if (strtotime($params['end_time']) - strtotime($params['start_time']) < 0) {
            throw new \Exception('结束日期需大于等于开始日期！', ERR_PARAMETERS);
        }

        $result = $this->alarmanalysisService->alarmTimeDistribution($params);

        $this->response($result);
    }

    /**
     * 7日报警均值
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @return json
     */
    public function sevenDayAlarmMeanValue()
    {
        $params = $this->input->post(null, true);
        // 校验参数
        $this->validate([
            'frequency_type' => 'required|in_list[' . implode(',', array_keys($this->config->item('frequency_type'))) . ']',
            'city_id'        => 'required|is_natural_no_zero',
        ]);

        $params['logic_junction_id'] = !empty($params['logic_junction_id'])
                                        ? strip_tags(trim($params['logic_junction_id']))
                                        : '';
        $result = $this->alarmanalysisService->sevenDayAlarmMeanValue($params);
        $this->response($result);
    }

    public function realtimeSingleQuotaCurve(){
        $this->convertJsonToPost();
        $params = $this->input->post(null, true);
        // 校验参数
        $this->validate([
            'city_id'        => 'required|is_natural_no_zero',
            'dates'        => 'is_array',
            'junction_id'  => 'required|trim',
            // 'movement_id'  => 'required|trim',
            'start_time'        => 'required|trim|regex_match[/\d{2}:\d{2}/]',
            'end_time'        => 'required|trim|regex_match[/\d{2}:\d{2}/]',
            'quota_key'        => 'required|trim',
        ], [
            'dates' => array(
                'is_array' => '%s 必须是一个数组',
            ),
        ]);
        if(empty($params["dates"])){
            $params["dates"] = [date("Y-m-d")];
        }
        $resultList = $this->realtimeQuotaService->realtimeSingleQuotaCurve($params);
        $currentTime = "";
        if(isset($resultList["today"])){
            $lastResult = end($resultList["today"]);
            $currentTime = $lastResult["hour"];
        }
        $this->response(["quota_list"=>$resultList,"dates"=>$params["dates"],"current_time"=>$currentTime]);
    }

    public function junctionRealtimeFlowQuotaList(){
        $this->convertJsonToPost();
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id'        => 'required|is_natural_no_zero',
            'junction_id'  => 'required|trim',
            'dates'        => 'is_array',
            'time'        => 'trim|regex_match[/\d{2}:\d{2}:\d{2}/]',
            'with_alarm'  => 'is_natural'
        ]);
        $result = $this->realtimeQuotaService->junctionRealtimeFlowQuotaList($params);
        $this->response($result);
    }

    public function junctionAlarmDealList(){
        $this->convertJsonToPost();
        $params = $this->input->post(null, true);
        $this->validate([
            'city_id'        => 'required|is_natural_no_zero',
            'dates'        => 'is_array',
            'junction_id'  => 'required|trim',
        ], [
            'dates' => array(
                'is_array' => '%s 必须是一个数组',
            ),
        ]);
        $result = $this->alarmanalysisService->junctionAlarmDealList($params);
        $this->response($result);
    }
}
