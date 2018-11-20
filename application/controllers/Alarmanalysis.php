<?php
/***************************************************************
# 报警分析
# user:ningxiangbing@didichuxing.com
# date:2018-11-19
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\AlarmanalysisService;

class Alarmanalysis extends MY_Controller
{
    protected $alarmanalysisService;

    public function __construct()
    {
        parent::__construct();

        $this->alarmanalysisService = new alarmanalysisService();

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

        $result = $this->alarmanalysisService->alarmAnalysis($params);

        $this->response($result);
    }

    /**
     * 城市/路口报警时段分布接口
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['time_range']        string Y 查询时间段 例：当前时间为：2018-11-13
     *                                                           当天：2018-11-13-2018-11-13
     *                                                           七日：2018-11-06-2018-11-12
     *                                                           30日：同七日
     *                                                           自定义：同七日
     * @return json
     */
    public function alarmTimeDistribution()
    {
        $params = $this->input->post(null, true);
    }

    /**
     * 当日城市/路口报警信息统计
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['alarm_type']        int    Y 报警类型 1: 过饱和 2: 溢流 3:失衡
     */
    public function dailyAlarmInformationStatistics()
    {
        $params = $this->input->post(null, true);
    }

    /**
     * N日城市/路口报警信息统计
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['alarm_type']        int    Y 报警类型 1: 过饱和 2: 溢流 3:失衡
     * @param $params['time_range']        string Y 时间段 yyyy-mm-dd-yyyy-mm-dd
     * @return json
     */
    public function timeAlarmInformationStatistics()
    {
        $params = $this->input->post(null, true);
    }
}
