<?php
/**
 * 报警分析接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-19
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

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
            return (object)[];
        }

        if ($params['start_time'] == $params['end_time']) {
            // 获取当天报警分析
            return $this->getDailyAlarmAnalysis($params);
        } else {
            // 按时间段获取报警分析
            return $this->getTimeAlarmAnalysis($params);
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
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":'.$params['city_id'].',"type":"phrase"}}}';

        // where date
        $json .= ',{"match":{"date":{"query":"'.$params['start_time'].'","type":"phrase"}}}';

        if (!empty($params['logic_junction_id'])) { // 单路口报警分析查询
            // where logic_junction_id
            $json .= ',{"match":{"logic_junction_id":{"query":"'.$params['logic_junction_id'].'","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. $params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":["hour","type","frequency_type"],"aggregations":{"hour":{"terms":{"field":"hour","size":200},"aggregations":{"type":{"terms":{"field":"type","size":0},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];
        if (!empty($result['aggregations']['hour']['buckets'])) {
            // 路口报警类型配置
            $junctionAlarmType = $this->config->item('junction_alarm_type');

            $tempRes = array_map(function($item) use ($junctionAlarmType) {
                if (!empty($item['type']['buckets'])) {
                    $tempData[$item['key'] . ':00'] = array_map(function($typeData) use ($junctionAlarmType) {
                        return [
                            'name'  => $junctionAlarmType[$typeData['key']],
                            'value' => $typeData['num']['value'],
                            'key'   => $typeData['key'],
                        ];
                    }, $item['type']['buckets']);
                    return $tempData;
                } else {
                    return [];
                }
            }, $result['aggregations']['hour']['buckets']);
        }

        /* 0-23整点小时保持连续 原因：数据表中可以会有某个整点没有报警，这样会导致前端画表时出现异常 */
        // 当前整点
        $nowHour = date('H');
        for ($i = 0; $i < $nowHour; $i++) {
            $continuousHour[$i . ':00'] = [];
        }

        // 平铺数组
        $temp = Collection::make($tempRes)->collapse()->get();
        // 合并数组
        $resultData['dataList'] = array_merge($continuousHour, $temp);

        return $resultData;
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
        // 组织DSL所需json
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . $params['city_id'] . ',"type":"phrase"}}}';

        // where date >= start_time
        $json .= ',{"range":{"date":{"from":"' . $params['start_time'] . '","to":null,"include_lower":true,"include_upper":true}}}';

        // where date <= end_time
        $json .= ',{"range":{"date":{"from":null,"to":"' . $params['end_time'] . '","include_lower":true,"include_upper":true}}}';

        // 当按路口报警分析查询时
        if (!empty($params['logic_junction_id'])) {
            // where logic_junction_id
            $json .= ',{"match":{"logic_junction_id":{"query":"'.$params['logic_junction_id'].'","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. $params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","date"],"excludes":[]},"fields":"date","aggregations":{"date":{"terms":{"field":"date","size":200},"aggregations":{"type":{"terms":{"field":"type","size":0},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];

        if (!empty($result['aggregations']['date']['buckets'])) {
            // 路口报警类型配置
            $junctionAlarmType = $this->config->item('junction_alarm_type');

            $tempRes = array_map(function($item) use ($junctionAlarmType) {
                if (!empty($item['type']['buckets'])) {
                    // $item['key'] / 1000 es返回的是毫秒级的时间戳，固除以1000
                    $key = date('Y-m-d', $item['key'] / 1000);
                    $tempData[$key] = array_map(function($typeData) use ($junctionAlarmType) {
                        return [
                            'name'  => $junctionAlarmType[$typeData['key']],
                            'value' => $typeData['num']['value'],
                            'key'   => $typeData['key'],
                        ];
                    }, $item['type']['buckets']);
                    return $tempData;
                } else {
                    return [];
                }
            }, $result['aggregations']['date']['buckets']);
        }

        /* 使日期连续 因为表中可能某个日期是没有的，就会出现断裂*/
        $startTime = strtotime($params['start_time']);
        $endTime = strtotime($params['end_time']);
        for ($i = $startTime; $i < $endTime; $i += 24 * 3600) {
            $continuousTime[date('Y-m-d', $i)] = [];
        }


        // 平铺数组
        $temp = Collection::make($tempRes)->collapse()->get();
        // 合并数组
        $resultData['dataList'] = array_merge($continuousTime, $temp);

        return $resultData;
    }

    /**
     * 城市/路口报警时段分布接口
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @param $params['start_time']        string Y 查询开始日期 yyyy-mm-dd
     * @param $params['end_time']          string Y 查询结束日期 yyyy-mm-dd 开始日期与结束日期一致认为查询当天从0点到当前整点的数据
     * @return array
     */
    public function alarmTimeDistribution($params)
    {
        if (empty($params)) {
            return (object)[];
        }

        // 组织DSL所需json
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . $params['city_id'] . ',"type":"phrase"}}}';

        if ($params['start_time'] == $params['end_time']) { // 当天
            // where date
            $json .= ',{"match":{"date":{"query":"' . $params['start_time'] . '","type":"phrase"}}}';
        } else { // 多天
            // where date
            $json .= ',{"range":{"date":{"from":"' . $params['start_time'] . '","to":null,"include_lower":true,"include_upper":true}}}';
            $json .= ',{"range":{"date":{"from":null,"to":"' . $params['end_time'] . '","include_lower":true,"include_upper":true}}}';
        }

        // 按路口查询
        if (!empty($params['logic_junction_id'])) {
            $json .= ',{"match":{"logic_junction_id":{"query":"' . $params['logic_junction_id'] . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. $params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":"hour","aggregations":{"hour":{"terms":{"field":"hour","size":200},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];

        if (!empty($result['aggregations']['hour']['buckets'])) {
            // 路口报警类型配置
            $junctionAlarmType = $this->config->item('junction_alarm_type');

            $tempRes = array_map(function($item) use ($junctionAlarmType) {
                return [
                    'intHour' => $item['key'],
                    'hour'    => $item['key'] . ':00',
                    'value'   => $item['num']['value'],
                ];
            }, $result['aggregations']['date']['buckets']);
        }

        print_r($tempRes);

        /* 0-23整点小时保持连续 原因：数据表中可以会有某个整点没有报警，这样会导致前端画表时出现异常 */
        // 当前整点
        $nowHour = date('H');
        for ($i = 0; $i < $nowHour; $i++) {
            $continuousHour[$i . ':00'] = [];
        }

        // 平铺数组
        $temp = Collection::make($tempRes)->collapse()->get();
        // 合并数组
        $resultData['dataList'] = array_merge($continuousHour, $temp);

        return $resultData;


    }
}
