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
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';

        // where date
        $json .= ',{"match":{"date":{"query":"' . trim($params['start_time']) . '","type":"phrase"}}}';

        if (!empty($params['logic_junction_id'])) { // 单路口报警分析查询
            // where logic_junction_id
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":' . (int)$params['frequency_type'] . ',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":["hour","type","frequency_type"],"aggregations":{"hour":{"terms":{"field":"hour","size":200},"aggregations":{"type":{"terms":{"field":"type","size":0},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];
        if (empty($result['aggregations']['hour']['buckets'])) {
            return (object)[];
        }

        // 路口报警类型配置
        $junctionAlarmType = $this->config->item('junction_alarm_type');

        $tempRes = array_map(function($item) use ($junctionAlarmType) {
            if (!empty($item['type']['buckets'])) {
                $tempData[$item['key'] . ':00']['list'] = array_map(function($typeData) use ($junctionAlarmType) {
                    return [
                        'name'  => $junctionAlarmType[$typeData['key']] ?? "",
                        'value' => $typeData['num']['value'],
                        'key'   => $typeData['key'],
                    ];
                }, $item['type']['buckets']);
                return $tempData;
            } else {
                return [];
            }
        }, $result['aggregations']['hour']['buckets']);

        /* 0-23整点小时保持连续 原因：数据表中可以会有某个整点没有报警，这样会导致前端画表时出现异常 */
        // 当前整点
        $nowHour = date('H');
        for ($i = 0; $i < $nowHour; $i++) {
            $continuousHour[$i . ':00'] = [];
        }

        // 平铺数组
        $temp = Collection::make($tempRes)->collapse()->get();
        foreach ($temp as $k=>$v) {
            // 各种报警条数总数
            $temp[$k]['count'] = array_sum(array_column($v['list'], 'value'));
        }

        // 合并数组
        $resultData = array_merge($continuousHour, $temp);

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
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';

        // where date >= start_time
        $json .= ',{"range":{"date":{"from":"' . trim($params['start_time']) . '","to":null,"include_lower":true,"include_upper":true}}}';

        // where date <= end_time
        $json .= ',{"range":{"date":{"from":null,"to":"' . trim($params['end_time']) . '","include_lower":true,"include_upper":true}}}';

        // 当按路口报警分析查询时
        if (!empty($params['logic_junction_id'])) {
            // where logic_junction_id
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. (int)$params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","date"],"excludes":[]},"fields":"date","aggregations":{"date":{"terms":{"field":"date","size":200},"aggregations":{"type":{"terms":{"field":"type","size":0},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];

        if (empty($result['aggregations']['date']['buckets'])) {
            return (object)[];
        }
        // 路口报警类型配置
        $junctionAlarmType = $this->config->item('junction_alarm_type');

        $tempRes = array_map(function($item) use ($junctionAlarmType) {
            if (!empty($item['type']['buckets'])) {
                // $item['key'] / 1000 es返回的是毫秒级的时间戳，固除以1000
                $key = date('Y-m-d', $item['key'] / 1000);
                $tempData[$key]['list'] = array_map(function($typeData) use ($junctionAlarmType) {
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

        /* 使日期连续 因为表中可能某个日期是没有的，就会出现断裂*/
        $startTime = strtotime($params['start_time']);
        $endTime = strtotime($params['end_time']);
        for ($i = $startTime; $i < $endTime; $i += 24 * 3600) {
            $continuousTime[date('Y-m-d', $i)] = [];
        }


        // 平铺数组
        $temp = Collection::make($tempRes)->collapse()->get();
        foreach ($temp as $k=>$v) {
            // 各种报警条数总数
            $temp[$k]['count'] = array_sum(array_column($v['list'], 'value'));
        }

        // 合并数组
        $resultData = array_merge($continuousTime, $temp);

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
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';

        if ($params['start_time'] == $params['end_time']) { // 当天
            // where date
            $json .= ',{"match":{"date":{"query":"' . trim($params['start_time']) . '","type":"phrase"}}}';
        } else { // 多天
            // where date
            $json .= ',{"range":{"date":{"from":"' . trim($params['start_time']) . '","to":null,"include_lower":true,"include_upper":true}}}';
            $json .= ',{"range":{"date":{"from":null,"to":"' . trim($params['end_time']) . '","include_lower":true,"include_upper":true}}}';
        }

        // 按路口查询
        if (!empty($params['logic_junction_id'])) {
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. (int)$params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":"hour","sort":[{"hour":{"order":"asc"}}],"aggregations":{"hour":{"terms":{"field":"hour","size":200,"order":{"_term":"asc"}},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];

        if (empty($result['aggregations']['hour']['buckets'])) {
            return (object)[];
        }
        // 路口报警类型配置
        $junctionAlarmType = $this->config->item('junction_alarm_type');

        $tempRes = array_map(function($item) use ($junctionAlarmType) {
            return [
                'hour'  => $item['key'],
                'value' => $item['num']['value'],
            ];
        }, $result['aggregations']['hour']['buckets']);

        // 当前整点
        $nowHour = date('H');
        // 将tempRes数据重新置为 ['hour'=>value] 数组
        $tempResData = array_column($tempRes, 'value', 'hour');
        /* 0-23整点小时保持连续 原因：数据表中可以会有某个整点没有报警，这样会导致前端画表时出现异常 */
        for ($i = 0; $i < $nowHour; $i++) {
            if (!array_key_exists($i, $tempResData)) {
                $tempResData[$i] = 0;
                $tempRes[] = [
                    'hour'  => $i,
                    'value' => 0,
                ];
            }
        }

        /* 找出连续三小时报警最的大的TOP2 */
        for ($i = 0; $i < $nowHour; $i++) {
            $value0 = $tempResData[$i] ?? 0;
            $value1 = $tempResData[($i+1)] ?? 0;
            $value2 = $tempResData[($i+2)] ?? 0;
            $countData[$i . '-' . ($i+1) . '-' . ($i+2)] = ($value0 + $value1 + $value2);
        }

        // 排序
        arsort($countData);
        // 去重
        array_unique($countData);
        // 取top2
        $topData = array_slice($countData, 0, 2);
        /* 判断两个连续3小时的开始时间差是否满足4小时及以上 */
        list($top1key, $top2key) = array_keys($topData);
        // top1、top2开始时间
        list($top1start) = explode('-', $top1key);
        list($top2start) = explode('-', $top2key);
        if (abs($top1start - $top2start) < 4) {
            // 小于4小时只取最大的一个
            unset($topData[$top2key]);
        }

        array_multisort($tempRes, $tempResData);
        $resultData['dataList'] = $tempRes;
        // 组织top信息
        foreach($topData as $hour=>$value) {
            $resultData['topInfo'][] = explode('-', $hour);
        }

        return $resultData;
    }

    /**
     * 7日报警均值
     * @param $params['city_id']           int    Y 城市ID
     * @param $params['logic_junction_id'] string N 逻辑路口ID 当：不为空时，按路口查询;为空时，按城市查询
     * @param $params['frequency_type']    int    Y 频率类型 0：全部 1：常发 2：偶发
     * @return json
     */
    public function sevenDayAlarmMeanValue($params)
    {
        if (empty($params)) {
            return (object)[];
        }

        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . (int)$params['city_id'] . ',"type":"phrase"}}}';

        // 前七日开始时间
        $startTime = date('Y-m-d', strtotime('-7 days'));
        // 前七日结束时间
        $endTime = date('Y-m-d', strtotime('-1 days'));

        // where date
        $json .= ',{"range":{"date":{"from":"' . $startTime . '","to":null,"include_lower":true,"include_upper":true}}}';
        $json .= ',{"range":{"date":{"from":null,"to":"' . $endTime . '","include_lower":true,"include_upper":true}}}';

        // 按路口查询
        if (!empty($params['logic_junction_id'])) {
            $json .= ',{"match":{"logic_junction_id":{"query":"' . trim($params['logic_junction_id']) . '","type":"phrase"}}}';
        }

        // 当选择了报警频率时
        if ($params['frequency_type'] != 0
            && array_key_exists($params['frequency_type'], $this->config->item('frequency_type'))) {
            // where frequency_type
            $json .= ',{"match":{"frequency_type":{"query":'. (int)$params['frequency_type'] .',"type":"phrase"}}}';
        }

        $json .= ']}}}},"_source":{"includes":["COUNT","hour"],"excludes":[]},"fields":"hour","sort":[{"hour":{"order":"asc"}}],"aggregations":{"hour":{"terms":{"field":"hour","size":200,"order":{"_term":"asc"}},"aggregations":{"num":{"value_count":{"field":"id"}}}}}}';

        $result = $this->alarmanalysis_model->search($json);
        if (!$result) {
            return (object)[];
        }

        /* 处理数据 */
        $tempRes = [];

        if (empty($result['aggregations']['hour']['buckets'])) {
            return (object)[];
        }

        $tempRes = array_map(function($item) {
            return [
                'hour'  => $item['key'] . ':00', // 10:00
                'value' => round($item['num']['value'] / 7 , 2),
            ];
        }, $result['aggregations']['hour']['buckets']);

        /* 0-23整点小时保持连续 原因：数据表中可以会有某个整点没有报警，这样会导致前端画表时出现异常 */
        for ($i = 0; $i < 24; $i++) {
            $continuousHour[$i . ':00'] = 0;
        }
        foreach(array_merge($continuousHour, array_column($tempRes, 'value', 'hour')) as $k=>$v) {
            $resultData['dataList'][] = [
                'hour'  => $k,
                'value' => $v,
            ];
        }

        return $resultData;
    }
}
