<?php
namespace Services;

/**
 * Class DiagnosisNoTimingService
 * @package Services
 * @property \DiagnosisNoTiming_model $diagnosisNoTiming_model
 */
class DiagnosisNoTimingService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('disgnosisnotiming_conf');
        $this->load->model('diagnosisNoTiming_model');
    }

    /**
     * 获取路口指标详情
     * @param $params ['city_id']     string   城市ID
     * @param $params ['junction_id']     string   逻辑路口ID
     * @param $params ['time_point']      string   时间点      必传
     * @param $params ['dates']           string   日期范围     必传
     * @return array
     */
    public function getFlowQuotas($params)
    {
        $timePoints = splitTimeDurationToPoints($params['time_range']);
        $result = [];

        //定义路口问题阈值规则
        $result["junction_question"] = $this->diagnosisNoTiming_model->getJunctionAlarmList(
            $params['junction_id'], $timePoints, $params['dates']);

        //定义指标名称及注释
        $quotaKey = $this->config->item('flow_quota_key');
        $result["flow_quota_all"] = $quotaKey;

        //movements从路网获取方向信息
        $result["movements"] = $this->diagnosisNoTiming_model->getMovementQuota(
            $params['city_id'], $params['junction_id'], $timePoints, $params['dates']);

        $result["junction_id"] = $params["junction_id"];
        return $result;
    }
}
