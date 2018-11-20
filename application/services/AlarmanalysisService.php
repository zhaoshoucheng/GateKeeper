<?php
/**
 * 报警分析接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-19
 */

namespace Services;

class AlarmanalysisService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        // load model
        $this->load->model('alarmanalysis_model');
    }

    /**
     * 城市/路口报警分析接口
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 开始日期与结束日期一致认为查询当天从0点到当前整点的数据
     * @return array
     */
    public function alarmAnalysis($params)
    {
        if (empty($params)) {
            return [];
        }

        if ($params['start_time'] == $params['end_time']) {
            // 获取当天报警分析
            $this->getDailyAlarmAnalysis($params);
        } else {
            // 按时间段获取报警分析
            $this->getTimeAlarmAnalysis($params);
        }
    }

    /**
     * 获取当天报警分析
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd
     * @return array
     */
    private function getDailyAlarmAnalysis($params)
    {
        // 组织DSL所需json
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[{"match":{"city_id":{"query":'.$params['city_id'].',"type":"phrase"}}},{"match":{"date":{"query":"'.$params['start_time'].'","type":"phrase"}}}';

        if (!empty($params['logic_junction_id'])) { // 单路口报警分析查询
            $json .= ',{"match":{"logic_junction_id":{"query":"'.$params['logic_junction_id'].'","type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":"hour","aggregations":{"hour":{"terms":{"field":"hour","size":200},"aggregations":{"COUNT(id)":{"value_count":{"field":"id"}}}}}}';

        $result = $this->alarmanalysis_model->search($json);


    }

    /**
     * 按时间段获取报警分析
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口报警分析查询;为空时，按城市报警分析查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd
     * @return array
     */
    private function getTimeAlarmAnalysis($params)
    {
        
    }
}
