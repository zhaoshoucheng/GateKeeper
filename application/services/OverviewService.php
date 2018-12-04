<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/24
 * Time: 上午11:35
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class OverviewService
 * @package Services
 * @property \RealtimeAlarm_model $realtimeAlarm_model
 * @property \Realtime_model      $realtime_model
 */
class OverviewService extends BaseService
{
    protected $helperService;

    /**
     * OverviewService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->helperService = new HelperService();

        $this->load->model('redis_model');
        $this->load->model('waymap_model');
        $this->load->model('realtime_model');
        $this->load->model('realtimeAlarm_model');
        $this->load->model('alarmanalysis_model');

        $this->config->load('realtime_conf');
    }

    /**
     * 路口概况
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function junctionSurvey($params)
    {
        $data = $this->junctionsList($params);

        $data = $data['dataList'] ?? [];

        $result = [];

        $result['junction_total']   = count($data);
        $result['alarm_total']      = 0;
        $result['congestion_total'] = 0;

        foreach ($data as $datum) {
            $result['alarm_total']      += $datum['alarm']['is'] ?? 0;
            $result['congestion_total'] += (int)(($datum['status']['key'] ?? 0) == 3);
        }

        return $result;
    }

    /**
     * 路口列表
     *
     * @param $params
     *
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public function junctionsList($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $hour = $this->helperService->getLastestHour($cityId);

        $data = $this->redis_model->getRealtimePretreatJunctionList($cityId, $date, $hour);

        return $data ? $data : [];
    }

    /**
     * 运行情况 概览页 平均延误
     *
     * @param $params['city_id'] int    Y 城市ID
     * @param $params['date']    string N 日期 yyyy-mm-dd
     * @return array
     * @throws \Exception
     */
    public function operationCondition($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $res = $this->redis_model->getRealtimeAvgStopDelay($cityId, $date);

        $result = $res ? $res : $this->realtime_model->avgStopdelay($cityId, $date);

        $realTimeQuota = $this->config->item('real_time_quota');

        $formatQuotaRoundHour = function ($v) use ($realTimeQuota) {
            return [
                $realTimeQuota['stop_delay']['round']($v['avg_stop_delay']),
                substr($v['hour'], 0, 5),
            ];
        };

        $ext = [];

        $findSkip = function ($carry, $item) use (&$ext) {
            $now = strtotime($item[1] ?? '00:00');
            if ($now - $carry >= 30 * 60) {
                $ext = array_merge($ext, range($carry + 5 * 60, $now - 5 * 60, 5 * 60));
            }
            return $now;
        };

        $resultCollection = Collection::make($result)->map($formatQuotaRoundHour);

        $info = [
            'value' => $resultCollection->avg(0, $realTimeQuota['stop_delay']['round']),
            'unit' => $realTimeQuota['stop_delay']['unit'],
        ];

        $resultCollection->reduce($findSkip, strtotime('00:00'));

        $result = $resultCollection->merge(array_map(function ($v) {
            return [null, date('H:i', $v)];
        }, $ext))->sortBy(1);

        return [
            'dataList' => $result,
            'info' => $info,
        ];
    }

    /**
     * 拥堵概览
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 当前时间点 格式：H:i:s 例：09:10:00
     * @return array
     * @throws \Exception
     */
    public function getCongestionInfo($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $hour = $this->helperService->getLastestHour($cityId);
        $res = $this->realtime_model->getAvgQuotaByJunction($cityId, $date, $hour);
        if (!$res) {
            return [];
        }

        $result = [];

        // 拥堵数量
        $congestionNum = [];

        // 路口总数
        $junctionTotal = count($res);

        // 路口状态配置
        $junctionStatusConf = $this->config->item('junction_status');
        // 路口状态计算规则
        $junctinStatusFormula = $this->config->item('junction_status_formula');

        foreach ($res as $k => $v) {
            $congestionNum[$junctinStatusFormula($v['quotaMap']['weight_avg'])][$k] = 1;
        }

        $result['count'] = [];
        $result['ratio'] = [];
        foreach ($junctionStatusConf as $k => $v) {
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num' => isset($congestionNum[$k]) ? count($congestionNum[$k]) : 0,
            ];

            $result['ratio'][$k] = [
                'cate' => $v['name'],
                'ratio' => isset($congestionNum[$k])
                    ? round((count($congestionNum[$k]) / $junctionTotal) * 100) . '%'
                    : '0%',
            ];
        }

        $result['count'] = array_values($result['count']);
        $result['ratio'] = array_values($result['ratio']);

        return $result;
    }

    /**
     * 获取 token
     *
     * @return array
     */
    public function getToken()
    {
        $token = md5(time() . rand(1, 10000) * rand(1, 10000));

        $this->redis_model->setData('Token_' . $token, $token);
        $this->redis_model->setExpire('Token_' . $token, 60 * 30);

        return [
            $token,
        ];
    }

    /**
     * 验证 token
     *
     * @param $params
     *
     * @return array
     */
    public function verifyToken($params)
    {
        $token = 'Token_' . $params['tokenval'];

        $res = $this->redis_model->verifyToken($token);

        return [
            'verify' => $res,
        ];
    }

    /**
     * 获取当前服务器时间
     *
     * @return array
     */
    public function getNowDate()
    {
        $weekArray = [
            '日', '一', '二', '三', '四', '五', '六',
        ];

        $time = time();

        return [
            'date' => date('Y-m-d', $time),
            'time' => date('H:i:s', $time),
            'week' => '星期' . $weekArray[date('w', $time)],
        ];
    }

    /**
     * 获取停车延误TOP20
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function stopDelayTopList($params)
    {
        $cityId   = $params['city_id'];
        $date     = $params['date'];
        $pagesize = $params['pagesize'];

        $hour = $this->helperService->getLastestHour($cityId);

        $select = 'logic_junction_id, hour, sum(stop_delay * traj_count) / sum(traj_count) as stop_delay';

        $result = $this->realtime_model->getTopStopDelay($cityId, $date, $hour, $pagesize, $select);

        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        $junctionIdNames = $this->waymap_model->getJunctionInfo($ids);
        $junctionIdNames = array_column($junctionIdNames, 'name', 'logic_junction_id');

        $realTimeQuota = $this->config->item('real_time_quota');

        $result = array_map(function ($item) use ($junctionIdNames, $realTimeQuota) {
            return [
                'time' => $item['hour'],
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionIdNames[$item['logic_junction_id']] ?? '未知路口',
                'stop_delay' => $realTimeQuota['stop_delay']['round']($item['stop_delay']),
                'quota_unit' => $realTimeQuota['stop_delay']['unit'],
            ];
        }, $result);

        return $result;
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function stopTimeCycleTopList($params)
    {
        $cityId   = $params['city_id'];
        $date     = $params['date'];
        $pagesize = $params['pagesize'];

        $hour = $this->helperService->getLastestHour($cityId);

        $select = 'logic_junction_id, hour, stop_time_cycle, logic_flow_id';

        $result = $this->realtime_model->getTopCycleTime($cityId, $date, $hour, $pagesize, $select);

        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        $junctionIdNames = $this->waymap_model->getJunctionInfo($ids);
        $junctionIdNames = array_column($junctionIdNames, 'name', 'logic_junction_id');

        $flowsInfo = $this->waymap_model->getFlowsInfo($ids);

        $realTimeQuota = $this->config->item('real_time_quota');

        $result = array_map(function ($item) use ($junctionIdNames, $realTimeQuota, $flowsInfo) {
            return [
                'time' => $item['hour'],
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionIdNames[$item['logic_junction_id']] ?? '未知路口',
                'logic_flow_id' => $item['logic_flow_id'],
                'flow_name' => $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] ?? '未知方向',
                'stop_time_cycle' => $realTimeQuota['stop_time_cycle']['round']($item['stop_time_cycle']),
                'quota_unit' => $realTimeQuota['stop_time_cycle']['unit'],
            ];
        }, $result);

        return $result;
    }

    /**
     * 获取今日报警预览
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @return array
     * @throws \Exception
     */
    public function todayAlarmInfo($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        // 组织ES所需JSON
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . $cityId . ',"type":"phrase"}}}';

        // where date
        $json .= ',{"match":{"date":{"query":"' . trim($date) . '","type":"phrase"}}}';

        $json .= ']}}}},"_source":{"includes":["COUNT"],"excludes":[]},"aggregations":{"type":{"terms":{"field":"type","size":200},"aggregations":{"num":{"cardinality":{"field":"logic_junction_id","precision_threshold":40000}}}}}}';

        $esRes = $this->alarmanalysis_model->search($json);
        if (empty($esRes['aggregations']['type']['buckets']) || !$esRes) {
            return [];
        }

        $res = [];
        $total = 0;
        // 格式
        foreach ($esRes['aggregations']['type']['buckets'] as $k=>$v) {
            $res[$v['key']] = $v['num']['value'];
            $total += $v['num']['value'];
        }

        // 报警类别配置
        $alarmCate = $this->config->item('alarm_category');

        $result = [];
        foreach ($alarmCate as $k=>$v) {
            $num = $res[$v['key']] ?? 0;
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num'  => $num,
            ];

            $result['ratio'][$k] = [
                'cate' => $v['name'],
                'ratio' => ($total >= 1) ? round(($num / $total) * 100) . '%' : '0%',
            ];
        }

        $result['count'] = array_values($result['count']);
        $result['ratio'] = array_values($result['ratio']);

        return $result;
    }

    /**
     * 获取七日报警变化
     * 规则：取当前日期前六天的报警路口数+当天到现在时刻的报警路口数
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 时间 HH:ii:ss
     * @throws Exception
     * @return array
     */
    public function sevenDaysAlarmChange($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        // 七日日期
        $sevenDates = [];

        // 前6天时间戳作为开始时间
        $startDate = strtotime($date . '-6 day');
        // 当前日期时间戳作为结束时间
        $endDate = strtotime($date);

        // 组织DSL所需json
        $json = '{"from":0,"size":0,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . $cityId . ',"type":"phrase"}}}';

        /* where date in*/
        $json .= ',{"bool":{"should":[';
        for ($i = $startDate; $i <= $endDate; $i += 24 * 3600) {
            $json .= '{"match":{"date":{"query":"' . date('Y-m-d', $i) . '","type":"phrase"}}}';
            if ($i < $endDate) {
                $json .= ',';
            }
        }
        $json .= ']}}]}}}},"_source":{"includes":["COUNT"],"excludes":[]},"sort":[{"date":{"order":"asc"}}],"aggregations":{"date":{"terms":{"field":"date","size":200,"order":{"_term":"asc"}},"aggregations":{"num":{"cardinality":{"field":"logic_junction_id","precision_threshold":40000}}}}}}';

        $data = $this->alarmanalysis_model->search($json);
        if (!$data || empty($data['aggregations']['date']['buckets'])) {
            return [];
        }

        $result['dataList'] = [];
        foreach ($data['aggregations']['date']['buckets'] as $k=>$v) {
            $result['dataList'][$k] = [
                'date'  => date('Y-m-d', $v['key'] / 1000),
                'value' => $v['num']['value'],
            ];
        }

        return $result;
    }

    /**
     * 获取实时报警信息
     * @param $params['city_id']    int    Y 城市ID
     * @param $params['date']       string N 日期 yyyy-mm-dd
     * @param $params['time_point'] string N 当前时间点 格式：H:i:s 例：09:10:00
     * @return array
     * @throws \Exception
     */
    public function realTimeAlarmList($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $res = $this->alarmanalysis_model->getRealTimeAlarmsInfo($cityId, $date);
        if (!$res || empty($res)) {
            return [];
        }
        $result = [];

        // 需要获取路口name的路口ID串
        $junctionIds = implode(',', array_unique(array_column($res, 'logic_junction_id')));

        // 获取路口信息
        $junctionsInfo  = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');

        // 获取路口相位信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($junctionIds);

        // 报警类别
        $alarmCate = $this->config->item('flow_alarm_category');

        foreach ($res as $k => $val) {
            // 持续时间
            $durationTime = round((strtotime($val['last_time']) - strtotime($val['start_time'])) / 60, 2);
            if ($durationTime < 1) {
                $durationTime = 2;
            }

            if (!empty($junctionIdName[$val['logic_junction_id']])
                && !empty($flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']])) {
                $result['dataList'][$k] = [
                    'start_time' => date('H:i', strtotime($val['start_time'])),
                    'duration_time' => $durationTime,
                    'logic_junction_id' => $val['logic_junction_id'],
                    'junction_name' => $junctionIdName[$val['logic_junction_id']] ?? '',
                    'logic_flow_id' => $val['logic_flow_id'],
                    'flow_name' => $flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']] ?? '',
                    'alarm_comment' => $alarmCate[$val['type']]['name'] ?? '',
                    'alarm_key' => $val['type'],
                ];
            }
        }

        if (empty($result['dataList'])) {
            return [];
        }
        $result['dataList'] = array_values($result['dataList']);

        return $result;
    }

    /**
     * 处理从数据库中取出的原始数据并返回
     *
     * @param $cityId
     * @param $result
     * @param $realTimeAlarmsInfo
     *
     * @return array
     * @throws \Exception
     */
    public function getJunctionListResult($cityId, $result, $realTimeAlarmsInfo)
    {
        //获取路口信息的自定义返回格式
        $junctionsInfo = $this->waymap_model->getAllCityJunctions($cityId, 0);
        $junctionsInfo = array_column($junctionsInfo, null, 'logic_junction_id');

        //获取需要报警的全部路口ID
        $ids = implode(',', array_column($realTimeAlarmsInfo, 'logic_junction_id'));

        //获取需要报警的全部路口的全部方向的信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($ids);

        //数组初步处理，去除无用数据
        $result = array_map(function ($item) use ($flowsInfo, $realTimeAlarmsInfo) {
            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'quota' => $this->getRawQuotaInfo($item),
                'alarm_info' => $this->getRawAlarmInfo($item, $flowsInfo, $realTimeAlarmsInfo),
            ];
        }, $result);

        //数组按照 logic_junction_id 进行合并
        $temp = [];
        foreach ($result as $item) {
            $temp[$item['logic_junction_id']] = isset($temp[$item['logic_junction_id']]) ?
                $this->mergeFlowInfo($temp[$item['logic_junction_id']], $item) :
                $item;
        };

        //处理数据内容格式
        $temp = array_map(function ($item) use ($junctionsInfo) {
            return [
                'jid' => $item['logic_junction_id'],
                'name' => $junctionsInfo[$item['logic_junction_id']]['name'] ?? '',
                'lng' => $junctionsInfo[$item['logic_junction_id']]['lng'] ?? '',
                'lat' => $junctionsInfo[$item['logic_junction_id']]['lat'] ?? '',
                'quota' => ($quota = $this->getFinalQuotaInfo($item)),
                'alarm' => $this->getFinalAlarmInfo($item),
                'status' => $this->getJunctionStatus($quota),
            ];
        }, $temp);

        $lngs = array_filter(array_column($temp, 'lng'));
        $lats = array_filter(array_column($temp, 'lat'));

        $center['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
        $center['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));

        return [
            'dataList' => array_values($temp),
            'center' => $center,
        ];
    }

    /**
     * 获取原始指标信息
     *
     * @param $item
     *
     * @return array
     */
    private function getRawQuotaInfo($item)
    {
        return [
            'stop_delay_weight' => $item['stop_delay'] * $item['traj_count'],
            'stop_time_cycle' => $item['stop_time_cycle'],
            'traj_count' => $item['traj_count'],
        ];
    }

    /**
     * 获取原始报警信息
     *
     * @param $item
     * @param $flowsInfo
     * @param $realTimeAlarmsInfo
     *
     * @return array
     */
    private function getRawAlarmInfo($item, $flowsInfo, $realTimeAlarmsInfo)
    {
        $alarmCategory = $this->config->item('alarm_category');

        $result = [];

        if (isset($flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']])) {
            foreach ($alarmCategory as $key => $value) {
                if (array_key_exists($item['logic_flow_id'] . $key, $realTimeAlarmsInfo)) {
                    $result[] = $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] .
                        '-' . $value['name'];
                }
            }
        }

        return $result;
    }

    /**
     * 数据处理，多个 flow 记录合并到其对应 junction
     *
     * @param $target
     * @param $item
     *
     * @return mixed
     */
    private function mergeFlowInfo($target, $item)
    {
        //合并属性 停车延误加权求和，停车时间求最大，权值求和
        $target['quota']['stop_delay_weight'] += $item['quota']['stop_delay_weight'];
        $target['quota']['stop_time_cycle']   = max($target['quota']['stop_time_cycle'], $item['quota']['stop_time_cycle']);
        $target['quota']['traj_count']        += $item['quota']['traj_count'];

        if (isset($target['alarm_info'])) {
            //合并报警信息
            $target['alarm_info'] = array_merge($target['alarm_info'], $item['alarm_info']) ?? [];
        }

        return $target;
    }

    /**
     * 获取最终指标信息
     *
     * @param $item
     *
     * @return array
     */
    private function getFinalQuotaInfo($item)
    {
        //实时指标配置文件
        $realTimeQuota = $this->config->item('real_time_quota');

        return [
            'stop_delay' => [
                'name' => '平均延误',
                'value' => $realTimeQuota['stop_delay']['round']($item['quota']['stop_delay_weight'] / $item['quota']['traj_count']),
                'unit' => $realTimeQuota['stop_delay']['unit'],
            ],
            'stop_time_cycle' => [
                'name' => '最大停车次数',
                'value' => $realTimeQuota['stop_time_cycle']['round']($item['quota']['stop_time_cycle']),
                'unit' => $realTimeQuota['stop_time_cycle']['unit'],
            ],
        ];
    }

    /**
     * 获取最终报警信息
     *
     * @param $item
     *
     * @return array
     */
    private function getFinalAlarmInfo($item)
    {
        return [
            'is' => (int)!empty($item['alarm_info']),
            'comment' => $item['alarm_info'],
        ];
    }

    /**
     * 获取当前路口的状态
     *
     * @param $quota
     *
     * @return mixed
     */
    private function getJunctionStatus($quota)
    {
        $junctionStatus = $this->config->item('junction_status');

        $junctionStatusFormula = $this->config->item('junction_status_formula');

        return $junctionStatus[$junctionStatusFormula($quota['stop_delay']['value'])];
    }
}
