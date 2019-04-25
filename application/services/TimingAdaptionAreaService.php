<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/30
 * Time: 下午4:41
 */

namespace Services;

use Didi\Cloud\Collection\Collection;
use Overtrue\Pinyin\Pinyin;

/**
 * Class TimingAdaptionAreaService
 * @package Services
 * @property \TimeAlarmRemarks_model $timeAlarmRemarks_model
 * @property \Adapt_model $adapt_model
 */
class TimingAdaptionAreaService extends BaseService
{
    protected $helperService;

    protected $signal_mis_interface;

    public function __construct()
    {
        parent::__construct();

        // load model
        $this->load->model('redis_model');
        $this->load->model('waymap_model');
        $this->load->model('adapt_model');
        $this->load->model('alarmanalysis_model');
        $this->load->model('timeAlarmRemarks_model');
        $this->load->model('traj_model');

        // load config
        $this->load->config('nconf');
        $this->load->config('realtime_conf');
        $this->load->helper('http_helper');

        $this->helperService = new HelperService();

        $this->signal_mis_interface = $this->config->item('signal_mis_interface');
    }

    /**
     * 获取城市区域列表
     *
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function getAreaList($data)
    {
        $url = $this->signal_mis_interface . '/TimingAdaptation/getAreaList';
        return $this->waymap_model->post($url, $data);
    }

    /**
     * @param $params
     *
     * @return mixed
     * @throws \Exception
     */
    public function getAreaListFormat($params)
    {
        $cityId = $params['city_id'];
        $data = $this->getAreaList($params);
        return $this->formatGetAreaListData($cityId, $data);
    }

    /**
     * @param $cityId
     * @param $data
     *
     * @return array
     * @throws \Exception
     */
    private function formatGetAreaListData($cityId, $data)
    {
        // 存放区域平均速度与平均延误数据的redis key 前缀
        $areaRedisKey = 'area_spped_delay_data_' . $cityId;
        /**
         * 获取redis中平均速度平均延误数据
         * 存放的是json格式，json_decode后的格式为：
         * $redisData = [
         *      'xxxx(area_id为键)' => [
         *          'speed'      => xxx,
         *          'stop_delay' => xxx,
         *      ],
         *      ......
         * ]
         */
        $redisData = $this->redis_model->getData($areaRedisKey);
        if (!empty($redisData)) {
            $redisData = json_decode($redisData, true);
        }
        // 获取数组更新最新时间 用于获取每个区域平均延误、平均速度
        $lastHour = $this->helperService->getIndexLastestHour($cityId);

        $esTime = date('Y-m-d H:i:s', strtotime($lastHour));

        foreach ($data as $k => $v) {
            // 平均速度
            $speed = 0;
            // 平均延误
            $stop_delay = 0;

            // 平均速度变化趋势 默认无变化
            $speed_trend = 0;
            // 平均延误变化趋势 默认无变化
            $stop_delay_trend = 0;

            // 获取每个区域的路口ID串
            $jdata = [
                'city_id' => $cityId,
                'area_id' => $v['id'],
            ];

            $junctions = $this->getAreaJunctions($jdata);

            if (!empty($junctions)) {
                // 路口ID串
                $esJunctionIds = implode(',', array_filter(array_column($junctions, 'logic_junction_id')));

                // 获取区域平均速度
                $esSpeed = $this->realtime_model->getEsAreaQuotaValue($cityId, $esJunctionIds, $esTime, 'avgSpeedUp');
                $speed = !empty($esSpeed[$lastHour]['value']) ? $esSpeed[$lastHour]['value'] : 0;

                // 获取区域平均延误
                $esStopDelay = $this->realtime_model->getEsAreaQuotaValue($cityId, $esJunctionIds, $esTime, 'stopDelayUp');
                $stop_delay = !empty($esStopDelay[$lastHour]['value']) ? $esStopDelay[$lastHour]['value'] : 0;

                /**
                 * 获取上一次的平均延误、平均速度数据
                 * 与本次数据进行对比，得出是否有变化
                 * 变化定义：0无变化、1上升、2下降
                 * 所有区域比较完成后最后更新redis
                 */
                if (!empty($redisData)) {

                    $oldSpeed = $redisData[$v['id']]['speed'] ?? 0;
                    $oldStopDelay = $redisData[$v['id']]['stop_delay'] ?? 0;

                    // 对平均速度变化趋势进行判断
                    if ($speed > $oldSpeed) {
                        $speed_trend = 1; // 上升
                    } elseif ($speed < $oldSpeed) {
                        $speed_trend = 2; // 下降
                    } else {
                        $speed_trend = 0; // 无变化
                    }

                    // 对平均延误变化趋势进行判断
                    if ($stop_delay > $oldStopDelay) {
                        $stop_delay_trend = 1; // 上升
                    } elseif ($stop_delay < $oldStopDelay) {
                        $stop_delay_trend = 2; // 下降
                    } else {
                        $stop_delay_trend = 0; // 无变化
                    }
                }
            }
            // 更新redis中平均速度与平均延误
            $redisData[$v['id']]['speed'] = $speed;
            $redisData[$v['id']]['stop_delay'] = $stop_delay;

            // 为平均速度、平均延误、平均速度变化趋势、平均延误变化趋势赋值
            $data[$k]['speed'] = round($speed * 3.6, 2) . 'km/h';
            $data[$k]['stop_delay'] = round($stop_delay, 2) . '/s';
            $data[$k]['speed_trend'] = $speed_trend;
            $data[$k]['stop_delay_trend'] = $stop_delay_trend;
            $data[$k]['last_hour'] = $lastHour;

            // unset掉多余字段
            if (isset($v['status'])) {
                unset($data[$k]['status']);
            }
            if (isset($v['adaptive'])) {
                unset($data[$k]['adaptive']);
            }
        }

        // 更新平均速度、平均延误数据的redis
        $this->redis_model->setEx($areaRedisKey, json_encode($redisData), 24 * 3600);

        return [
            'dataList' => $data,
        ];
    }

    /**
     * 获取区域路口信息(公用)
     *
     * @param $data
     *
     * @return array
     * @throws \Exception
     */
    private function getAreaJunctions($data)
    {
        $url = $this->signal_mis_interface . '/TimingAdaptation/getAreaJunctionList';

        return $this->waymap_model->post($url, $data);
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getAreaJunctionList($params)
    {
        $cityId = $params['city_id'];

        $areaJunctions = $this->getAreaJunctions($params);

        return $this->formatGetAreaJunctionListData($cityId, $areaJunctions);
    }

    /**
     * 格式化区域路口集合数据
     * 添加路口报警状态
     *
     * @param $cityId int Y 城市ID
     * @param $data   array    Y 数据
     *                $data = [
     *                [
     *                'logic_junction_id'=>xxxx, // 路口ID
     *                'status'           =>xxxx, // 路口类型：0:无配时；1:有配时；2:自适应；9:配时异常
     *                'source'           =>xxxx, // 数据来源
     *                'junction_name'    =>xxxx, // 路口名称
     *                'is_upload'        =>xxxx, // 自适应下发状态 0：否 1：是
     *                ],
     *                ]
     *
     * @return array
     * @throws \Exception
     */
    private function formatGetAreaJunctionListData($cityId, $data)
    {
        // 获取实时报警路口信息
        $alarmJunctions = $this->getRealTimeAlarmJunctions($cityId);

        // 路口ID串
        $junctionIds = implode(',', array_unique(array_column($alarmJunctions, 'logic_junction_id')));

        // 获取路口信息
        $allJunctionIds = implode(',', array_unique(array_column($data, 'logic_junction_id')));

        $junctionInfo = $this->waymap_model->getJunctionInfo($allJunctionIds);

        // 组织路口ID=>路口经纬度的数据  ['路口ID' =>['lng'=>xx, 'lat'=>xx], ...]
        $junctionIdByLatAndLog = [];

        foreach ($junctionInfo as $v) {
            $junctionIdByLatAndLog[$v['logic_junction_id']] = [
                'lng' => $v['lng'],
                'lat' => $v['lat'],
            ];
        }

        // 获取路口相位信息
        try {
            $flowsInfo = $this->waymap_model->getFlowsInfo($junctionIds);
        } catch (\Exception $e) {
            $flowsInfo = [];
        }

        // 报警类别
        $alarmCate = $this->config->item('alarm_category');

        /**
         * 格式alarmJunctions使其为：
         * $alarmJunctions = [
         * '路口ID' => [
         * '报警内容：方向-报警原因 例：东直-溢流',
         * '报警内容：方向-报警原因 例：东直-溢流',
         * ],
         * ]
         */
        $alarmData = [];
        foreach ($alarmJunctions as $v) {
            $flowName = $flowsInfo[$v['logic_junction_id']][$v['logic_flow_id']] ?? '未知方向';
            $alarmComment = $alarmCate[$v['type']]['name'] ?? '未知报警';
            $alarmContent = $flowName . '-' . $alarmComment;
            if (empty($alarmData[$v['logic_junction_id']])) {
                $alarmData[$v['logic_junction_id']] = [];
            }
            array_push($alarmData[$v['logic_junction_id']], $alarmContent);
        }
        array_filter($alarmData);

        foreach ($data as $k => $v) {
            // 路口经纬度
            $data[$k]['lng'] = $junctionIdByLatAndLog[$v['logic_junction_id']]['lng'] ?? 0;
            $data[$k]['lat'] = $junctionIdByLatAndLog[$v['logic_junction_id']]['lat'] ?? 0;

            // 组织路口报警信息
            $data[$k]['alarm'] = [
                'is' => 0,
                'comment' => '',
            ];
            if (array_key_exists($v['logic_junction_id'], $alarmData)) {
                $data[$k]['alarm'] = [
                    'is' => 1,
                    'comment' => $alarmData[$v['logic_junction_id']] ?? '',
                ];
            }
        }

        // 按照拼音排序
        $pinyin = new Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        foreach ($data as $key => $junction) {
            $name = $junction['junction_name'];
            $firstName = mb_substr($name, 0, 1);
            $pinyins = $pinyin->convert($firstName, PINYIN_ASCII_TONE);
            if (!empty($pinyins)) {
                $data[$key]['pinyin'] = $pinyins[0];
            } else {
                $data[$key]['pinyin'] = '';
            }
        }

        usort($data, function ($a, $b) {
            return $a['pinyin'] < $b['pinyin'] ? -1 : 1;
        });

        return [
            'dataList' => $data,
        ];
    }

    /**
     * 获取实时报警路口信息
     *
     * @param $cityId
     *
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    private function getRealTimeAlarmJunctions($cityId)
    {
        // 先去redis查数据，如果没有则查表
        $alarmRedisKey = 'new_its_realtime_alarm_' . $cityId;

        $result = $this->redis_model->getData($alarmRedisKey);

        $result = json_decode($result, true);

        if (empty($result)) {

            // 获取最近时间
            $lastHour = $this->helperService->getLastestHour($cityId);
            $date = date('Y-m-d');

            $result = $this->alarmanalysis_model->getRealTimeAlarmsInfoFromEs($cityId, $date, $lastHour);
        }

        return $result;
    }

    /**
     * 获取区域实时报警信息
     * @param $params ['city_id']     int 城市ID
     * @param $params ['area_id']     int 区域ID
     * @param $params ['alarm_type']  int 报警类型 0，全部，1，过饱和，2，溢流。默认0
     * @param $params ['ignore_type'] int 类型：0，全部，1，已忽略，2，未忽略。默认0
     * @return array
     * @throws \Exception
     */
    public function realTimeAlarmList($params)
    {
        $this->load->model('timeAlarmRemarks_model');

        $cityId = $params['city_id'];
        $areaId = $params['area_id'];
        $alarmType = $params['alarm_type'];
        $ignoreType = $params['ignore_type'];

        // 获取实时报警路口信息（全城）
        $alarmJunctions = $this->getRealTimeAlarmJunctions($cityId);
        if (empty($alarmJunctions)) {
            return [];
        }

        // 相位报警类型
        $flowAlarmCate = $this->config->item('flow_alarm_category');

        // 根据alarm_type过滤路口
        if ($alarmType != 0 && array_key_exists($alarmType, $flowAlarmCate)) {
            $alarmJunctions = array_filter($alarmJunctions, function ($item) use ($alarmType) {
                return $item['type'] == $alarmType;
            });
        } else {
            $alarmJunctions = array_filter($alarmJunctions);
        }

        if (empty($alarmJunctions)) {
            return [];
        }

        // 获取redis中忽略的路口相位
        $areaIgnoreJunctionRedisKey = 'area_ignore_Junction_flow_' . $areaId . '_' . $cityId;
        $areaIgnoreJunctionFlows = $this->redis_model->getData($areaIgnoreJunctionRedisKey);
        if (!empty($areaIgnoreJunctionFlows)) {
            /**
             * redids中存在的是json格式的,json_decode后格式：
             * $areaIgnoreJunctionFlows = [
             * 'xxxxxxxxxxxx', // logic_flow_id
             * 'xxxxxxxxxxxx',
             * ];
             */
            $areaIgnoreJunctionFlows = json_decode($areaIgnoreJunctionFlows, true);
        }

        // 根据ignore_type过滤所需路口 1:已忽略 2:未忽略
        if ($ignoreType != 0 && in_array($ignoreType, [1, 2])) {
            if ($ignoreType == 1) {
                if (empty($areaIgnoreJunctionFlows)) {
                    $alarmJunctions = [];
                } else {
                    $alarmJunctions = array_map(function ($item) use ($areaIgnoreJunctionFlows) {
                        if (in_array($item['logic_flow_id'], $areaIgnoreJunctionFlows, true)) {
                            return $item;
                        }
                        return null;
                    }, $alarmJunctions);
                }
            } else {
                if (!empty($areaIgnoreJunctionFlows)) {
                    $alarmJunctions = array_map(function ($item) use ($areaIgnoreJunctionFlows) {
                        if (!in_array($item['logic_flow_id'], $areaIgnoreJunctionFlows, true)) {
                            return $item;
                        }
                        return null;
                    }, $alarmJunctions);
                }
            }
            $alarmJunctions = array_filter($alarmJunctions);
        }
        if (empty($alarmJunctions)) {
            return [];
        }

        // 获取区域路口
        $areaJunctions = $this->getAreaJunctions($params);

        $areaJuncitonIds = array_column($areaJunctions, 'logic_junction_id');

        // 从全城报警路口中取出区域报警路口
        $areaAlarmJunctions = [];
        foreach ($alarmJunctions as $k => $v) {
            if (in_array($v['logic_junction_id'], $areaJuncitonIds, true)) {
                $areaAlarmJunctions[$k] = $v;
            }
        }
        if (empty($areaAlarmJunctions)) {
            return [];
        }

        // 需要获取路口name的路口ID串
        $junctionIds = implode(',', array_unique(array_column($areaAlarmJunctions, 'logic_junction_id')));
        // 获取路口信息
        $junctionsInfo = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');
        // 获取路口相位信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($junctionIds);

        /* 获取报警信息人工校验信息 */
        // 路口ID
        $junctionId = array_column($areaAlarmJunctions, 'logic_junction_id');
        // flowID
        $flowId = array_column($areaAlarmJunctions, 'logic_flow_id');
        $alarmRemarks = $this->timeAlarmRemarks_model->getAlarmRemarks($cityId, $areaId, $junctionId, $flowId);
        // 人工校验信息 [flowid => type]
        $alarmRemarksFlowKeyTypeValue = [];
        if (!empty($alarmRemarks)) {
            $alarmRemarksFlowKeyTypeValue = array_column($alarmRemarks, 'type', 'logic_flow_id');
        }

        foreach ($areaAlarmJunctions as $k => $val) {
            // 持续时间
            $durationTime = (strtotime($val['last_time']) - strtotime($val['start_time'])) / 60;
            if ($durationTime == 0) {
                $durationTime = 2;
            }

            $is_ignore = 2; // 是否忽略 默认未忽略
            if (!empty($areaIgnoreJunctionFlows)) {
                if (in_array($val['logic_flow_id'], $areaIgnoreJunctionFlows, true)) {
                    $is_ignore = 1;
                }
            }

            // 人工标注
            $check = 0;
            if (!empty($alarmRemarksFlowKeyTypeValue)) {
                $check = $alarmRemarksFlowKeyTypeValue[$val['logic_flow_id']] ?? 0;
            }

            if (!empty($junctionIdName[$val['logic_junction_id']])
                && !empty($flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']])
            ) {
                $result['data'][$k] = [
                    'start_time' => date('H:i', strtotime($val['start_time'])),
                    'duration_time' => round($durationTime),
                    'logic_junction_id' => $val['logic_junction_id'],
                    'junction_name' => $junctionIdName[$val['logic_junction_id']] ?? '',
                    'logic_flow_id' => $val['logic_flow_id'],
                    'flow_name' => $flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']] ?? '',
                    'alarm_comment' => $flowAlarmCate[$val['type']]['name'] ?? '',
                    'order' => $flowAlarmCate[$val['type']]['order'] ?? 0,
                    'alarm_key' => $val['type'],
                    'type' => $val['type'],
                    'junction_type' => $val['junction_type'],
                    'is_ignore' => $is_ignore,
                    'check' => $check,
                ];
            }
        }
        usort($result['data'], function($a, $b) {
            if ($a['is_ignore'] != $b['is_ignore']) {
                return ($a['is_ignore'] < $b['is_ignore']) ? 1 : -1;
            }
            if ($a['order'] != $b['order']) {
                return ($a['order'] < $b['order']) ? 1 : -1;
            }
            return ($a['duration_time'] < $b['duration_time']) ? 1 : -1;
        });

        return [
            'dataList' => array_values($result['data'] ?? []),
        ];
    }

    /**
     * 人工标注报警信息
     *
     * @param $data
     *
     * @return string
     * @throws \Exception
     */
    public function addAlarmRemark($data)
    {
        $data = [
            'city_id' => $data['city_id'],
            'area_id' => $data['area_id'],
            'logic_junction_id' => $data['logic_junction_id'],
            'logic_flow_id' => $data['logic_flow_id'],
            'flow_alarm_type' => $data['flow_alarm_type']??0,
            'junction_alarm_type' => $data['junction_alarm_type']??0,
            'type' => $data['is_correct'],
            'comment' => $data['comment'],
            'username' => $_COOKIE['username'] ?? "",
        ];

        $res = $this->timeAlarmRemarks_model->insertAlarmRemark($data);

        if (!$res) {
            throw new \Exception('添加失败！', ERR_DEFAULT);
        }

        return 'success';
    }

    /**
     * 忽略报警 将忽略的flow存入redis时效30分钟s
     *
     * @param $data
     *
     * @return string
     */
    public function ignoreAlarm($data)
    {
        // redis KEY
        $areaIgnoreJunctionRedisKey = 'area_ignore_Junction_flow_' . $data['area_id'] . '_' . $data['city_id'];
        $areaIgnoreJunctionFlows = $this->redis_model->getData($areaIgnoreJunctionRedisKey);
        if (!empty($areaIgnoreJunctionFlows)) {
            /**
             * redids中存在的是json格式的,json_decode后格式：
             * $areaIgnoreJunctionFlows = [
             * 'xxxxxxxxxxxx', // logic_junction_id
             * 'xxxxxxxxxxxx',
             * ];
             */
            $areaIgnoreJunctionFlows = json_decode($areaIgnoreJunctionFlows, true);
        } else {
            $areaIgnoreJunctionFlows = [];
        }

        // 向数组中添加新的数据
        array_push($areaIgnoreJunctionFlows, $data['logic_flow_id']);

        $this->redis_model->setEx($areaIgnoreJunctionRedisKey, json_encode($areaIgnoreJunctionFlows), 30 * 60);

        return 'success';
    }

    /**
     * 更新自适应路口开关
     *
     * @param $data
     *
     * @return string
     * @throws \Exception
     */
    public function junctionSwitch($data)
    {
        // 调用signal-mis接口
        $url = $this->signal_mis_interface . '/TimingAdaptation/junctionSwitch';

        $this->waymap_model->post($url, $data);

        return 'success';
    }

    /**
     * 更新自适应区域开关
     *
     * @param $data
     *
     * @return string
     * @throws \Exception
     */
    public function areaSwitch($data)
    {
        // 调用signal-mis接口
        $url = $this->signal_mis_interface . '/TimingAdaptation/areaSwitch';

        $this->waymap_model->post($url, $data);

        return 'success';
    }

    /**
     * 获取区域指标折线图
     * @param $data ['city_id']   int    城市ID
     * @param $data ['area_id']   int    区域ID
     * @param $data ['quota_key'] string 指标KEY
     * @return mixed
     * @throws \Exception
     */
    public function getAreaQuotaInfo($data)
    {
        // 获取路口ID串
        $junctions = $this->getAreaJunctions($data);

        if (empty($junctions)) {
            throw new \Exception('此区域没有路口', ERR_DEFAULT);
        }
        $quotaInfoTmp = $this->realtime_model->getRedisAreaQuotaValueCurve($data['area_id'], $data['quota_key']);
        if (empty($quotaInfoTmp)) {
            return [];
        }
        $quotaInfo = [];
        foreach ($quotaInfoTmp as $key=>$item){
            if(!empty($item['value']) && $item['value']>0){
                $quotaInfo[] = $item;
            }
        }



        $ret = [];
        $lastHour = $this->helperService->getLastestHour($data['city_id']);
        foreach ($quotaInfo as $k => $item) {
            $value = $item['value'];
            if ($data['quota_key'] == 'avgSpeed') {
                // 速度m/s转换为km/h
                $value = $item['value'] * 3.6;
            }
            $dayTime = $item['hour'];
            $ret[$k] = [
                $dayTime, // 时间 X轴
                round($value, 2),                                       // 值   Y轴
            ];
        }

        $tmpRet = [];
        $preDayTime = "";
        $preValue = 0;
        for ($i = 0; $i < count($ret); $i++) {
            $currentDayTime = $ret[$i][0];
            $currentValue = $ret[$i][1];

            if ($i == 0) {
                $tmpRet[] = $ret[$i];
            } elseif (getTodayTimeStamp($currentDayTime) - getTodayTimeStamp($preDayTime) > 15 * 60) {
                //求间隔了多少个5分钟
                $diffLen = (getTodayTimeStamp($currentDayTime) - getTodayTimeStamp($preDayTime)) / 60 / 5;
                $avgValue = ($currentValue - $preValue) / $diffLen;

                for ($x = 0; $x < $diffLen; $x++) {
                    $tmpDayTime = date("H:i:s", strtotime(date("Y-m-d")) + getTodayTimeStamp($preDayTime) + $x * 5 * 60);
                    $tmpValue = round($preValue + $x * $avgValue, 2);
                    $tmpRet[] = [$tmpDayTime, $tmpValue,];
                }
                $tmpRet[] = $ret[$i];
            } else {
                $tmpRet[] = $ret[$i];
            }
            $preDayTime = $currentDayTime;
            $preValue = $currentValue;
        }
        $dataList = [
            'dataList' => !empty($tmpRet) ? $tmpRet : [],
        ];
        return $dataList;
    }

    /**
     * 获取纠偏信息
     *
     * @param $data['city_id'] Y 请求城市
     * @param $data['logic_junction_id'] Y 路口id
     * @return int
     * @throws \Exception
     */
    public function getClockShift($data){
        //获取最近的缓存数据
        $clockKey = sprintf("itstool_junction_clockshift_%s", $data["logic_junction_id"]);
        $clockShift = $this->redis_model->getData($clockKey);
        if(!empty($clockShift)){
            return $clockShift;
        }

        //获取配时信息
        $allTimingInfo = $this->getAllFlowTimingInfo($data);
        $newTimingInfo = $allTimingInfo;
        if(empty($allTimingInfo["movement"])){
            return 0;
        }
        foreach ($allTimingInfo["movement"] as $key=>$item){
            $endTime = time();
            $startTime = $endTime - 30 * 60;
            $esData = [
                "cityId" => $data['city_id'],
                "endTime" => $endTime,
                "movementId" => $item['movement_id'],
                "source" => "signal_control",
                "startTime" => $startTime,
            ];

            $esUrl = $this->config->item('new_es_interface') . '/estimate/space/query';

            $cycleLength = $allTimingInfo['cycle'];
            if (empty($cycleLength)) {
                throw new \Exception('路口该方向相位差为空', ERR_DEFAULT);
            }

            $detail = httpPOST($esUrl, $esData, 0, 'json');
            if (!$detail) {
                throw new \Exception('调用es接口 获取时空图 失败！', ERR_DEFAULT);
            }
            $detail = json_decode($detail, true);
            if ($detail['code'] != '000000') {
                throw new \Exception($detail['message'], ERR_DEFAULT);
            }
            $trajList = [];
            //格式化原始轨迹数据
            foreach ($detail['result'] as $k => $v) {
                if(count($v)<5){
                    continue;
                }
                $timestampArr = array_column($v, 'timestamp');
                array_multisort($timestampArr, SORT_NUMERIC, SORT_ASC, $v);  //类似db里面的order by
                foreach ($v as $kk => $vv) {
                    $second = $vv['timestamp'];
                    $trajList[$k][] = [
                        "timestamp" => $second,
                        "distance" => $vv['distanceToStopBar'] // 值      Y轴  //
                    ];
                }
            }
            $newTimingInfo["movement"][$key]["traj"] = array_values($trajList);
            if(empty($trajList)){
                unset($newTimingInfo["movement"][$key]);
            }
        }
        $newTimingInfo["movement"] = array_values($newTimingInfo["movement"]);
        $requestInfo = [
            "junction_list"=>[$newTimingInfo],
        ];

        $correctResult = $this->traj_model->getRealtimeClockShiftCorrect(json_encode($requestInfo));
        $clockShift = $correctResult[0]["clock_shift"] ?? 0;    //纠偏差
        $this->redis_model->setEx($clockKey,$clockShift,300);
        return $clockShift;
    }

    /**
     * 获取时空图
     *
     * @param $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function getSpaceTimeMtraj($data)
    {
        //获取配时信息
        $clockShift = $this->getClockShift($data);

        // 获取相位配时信息 周期、相位差等
        // 获取相位配时信息
        $timingInfo = $this->getFlowTimingInfo($data);
        if (empty($timingInfo) || empty($timingInfo["green"])) {
            return [];
        }

        //正常逻辑不变
        $endTime = time();
        $startTime = $endTime - 30 * 60;
        $esData = [
            "cityId" => $data['city_id'],
            "endTime" => $endTime,
            "movementId" => $data['logic_flow_id'],
            "source" => "signal_control",
            "startTime" => $startTime,
        ];

        $esUrl = $this->config->item('new_es_interface') . '/estimate/space/query';

        $cycleLength = $timingInfo['cycle'];
        $offset = $timingInfo['offset'];
        if (empty($cycleLength)) {
            throw new \Exception('路口该方向相位差为空', ERR_DEFAULT);
        }

        $detail = httpPOST($esUrl, $esData, 0, 'json');
        if (!$detail) {
            throw new \Exception('调用es接口 获取时空图 失败！', ERR_DEFAULT);
        }
        $detail = json_decode($detail, true);
        if ($detail['code'] != '000000') {
            throw new \Exception($detail['message'], ERR_DEFAULT);
        }

        if (empty($detail['result'])) {
            return [];
        }

        $ret['dataList'] = [];
        //$v代表一条轨迹
        foreach ($detail['result'] as $k => $v) {
            // 按时间正序排序
            $timestampArr = array_column($v, 'timestamp');
            //按照时间倒序排序:越近越靠前 尾-->头
            array_multisort($timestampArr, SORT_NUMERIC, SORT_DESC, $v);  //类似db里面的order by
            //$vv代表一个轨迹点
            foreach ($v as $kk => $vv) {
                // 提取天级秒数
                $time = date_parse(date("H:i:s", $vv['timestamp']));
                $second = $time['hour'] * 3600 + $time['minute'] * 60 + $time['second'];
                $ret['dataList'][$k][$kk] = [
                    $second,                      // 时间秒数 X轴
                    $vv['distanceToStopBar'] * -1 // 值      Y轴  //
                ];
            }
            //获取把时空图轨迹压缩在一个周期内
            $ret['dataList'][$k] = $this->getTrajsInOneCycle($ret['dataList'][$k]
                , $cycleLength
                , ($offset + $clockShift) % $cycleLength);   //纠偏
        }
        $ret['dataList'] = array_filter($ret['dataList']);

        $trajs = Collection::make($ret['dataList']);

        // X轴 Y轴 信息集合
        $ret['info'] = [
            "x" => [
                "max" => $trajs->collapse()->column(0)->max(),
                "min" => $trajs->collapse()->column(0)->min(),
            ],
            "y" => [
                "max" => $trajs->collapse()->column(1)->max(),
                "min" => $trajs->collapse()->column(1)->min(),
            ],
        ];

        $ret['signal_info'] = $timingInfo;
        $ret['clock_shift'] = $clockShift;

        return $ret;
    }


    /**
     * 获取所有相位的自适应配置信息
     *
     * @param $data ['logic_junction_id'] string Y 路口ID
     *
     * @return array
     */
    private function getAllFlowTimingInfo($data,$flowId="")
    {
        $resData = $this->adapt_model->getAdaptByJunctionId($data['logic_junction_id']);

        if (empty($resData['timing_info'])) {
            return [];
        }

        $Info = json_decode($resData['timing_info'], true);
        if (empty($Info['data'])) {
            return [];
        }

        list($timingInfo) = $Info['data']['tod'] ?? [""];
        if (empty($timingInfo)) {
            return [];
        }

        // 周期 相位差
        $res = [
            "junction_id" => $data['logic_junction_id'],
            "cycle" => $timingInfo["extra_time"]["cycle"],
            "offset" => $timingInfo["extra_time"]["offset"],
        ];
        // 信息灯信息
        foreach ($timingInfo['movement_timing'] as $k => $v) {
            if (empty($v['flow']['logic_flow_id'])) {
                continue;
            }
            $tmp = [];
            $tmp['movement_id'] = $v['flow']['logic_flow_id'];
            if($flowId!="" && $tmp['movement_id']!=$flowId){
                continue;
            }
            foreach ($v['timing'] as $kk => $vv) {
                $tmp['green'][] = [
                    'green_start' => $vv['start_time'],
                    'green_duration' => $vv['duration'],
                    'yellow' => $v['yellow'],
                    'red_clean' => $v['all_red'],
                ];
            }
            $res["movement"][] = $tmp;
        }
        return $res;
    }

    /**
     * 获取某一相位的自适应配置信息
     *
     * @param $data ['logic_junction_id'] string Y 路口ID
     *
     * @return array
     */
    public function getFlowTimingInfo($data)
    {
        $resData = $this->adapt_model->getAdaptByJunctionId($data['logic_junction_id']);
        if (empty($resData['timing_info'])) {
            //从实时接口读取配置信息

            return [];
        }

        $Info = json_decode($resData['timing_info'], true);
        if (empty($Info['data'])) {
            return [];
        }

        list($timingInfo) = $Info['data']['tod'] ?? [""];
        if (empty($timingInfo)) {
            return [];
        }

        // 周期 相位差
        $res = [
            'cycle' => $timingInfo['extra_time']['cycle'],
            'offset' => $timingInfo['extra_time']['offset'],
            'tod_start_time' => $timingInfo['extra_time']['tod_start_time'],
            'tod_end_time' => $timingInfo['extra_time']['tod_end_time'],
        ];
        // 信息灯信息
        foreach ($timingInfo['movement_timing'] as $k => $v) {
            if ($v['flow']['logic_flow_id'] == $data['logic_flow_id']) {
                $res['yellow'] = $v['yellow'];
                foreach ($v['timing'] as $kk => $vv) {
                    $res['green'][$kk] = [
                        'start_time' => $vv['start_time'],
                        'duration' => $vv['duration'],
                    ];
                }
            }
        }

        return $res;
    }

    /**
     * 获取把时空图轨迹压缩在一个周期内
     *
     * @param $trajs       array    轨迹数据,二维数组
     * @param $cycleLength int      周期时长
     * @param $offset      int      相位差
     *
     * @return array 调整后的轨迹
     */
    private function getTrajsInOneCycle(array $trajs, int $cycleLength, int $offset)
    {
        $trajsCol = Collection::make($trajs);

        //求距离停车线最近距离
        $min = $trajsCol->reduce(function ($a, $b) {
            if ($a == null) {
                return $b;
            }
            if (abs($a[1]) < abs($b[1])) {
                return $a;
            }
            return $b;
        });
        $crossTime = $min[0]; // 该轨迹最小点过路口时间
        $shiftTime = $crossTime - (($crossTime - $offset) % $cycleLength);  //该轨迹最小点过路口时间,时间偏移量
        //$crossTime - $shiftTime 相对时间
        $minTime = $crossTime - $shiftTime - 2 * $cycleLength;    //最小周期设置为2倍周期范围内
        $maxTime = $crossTime - $shiftTime + 1.5 * $cycleLength;  //最大周期设置为1.5倍周期范围内

        $ret = [];
        foreach ($trajs as $traj) {
            $time = $traj[0] - $shiftTime;
            if ($time < $minTime || $time > $maxTime) {
                continue;
            }

            $ret[] = [$time, $traj[1]];
        }
        return $ret;
    }

    /**
     * 获取最新配时数据
     * @param $data [logic_junction_id] Y 逻辑路口
     * @param $data [logic_flow_id] Y 逻辑相位
     * @return array
     */
    public function getCurrentTimingCurv($data)
    {
        //获取最近的缓存数据
        $currentTimingListRedisKey = sprintf("schedule_junction_current_timing_list_%s", $data["logic_junction_id"]);
        $currentTimingList = $this->redis_model->lrange($currentTimingListRedisKey);
        $flowTimingCurve = [];
        if (!empty($currentTimingList)) {
            foreach ($currentTimingList as $val) {
                list($ctime, $cdata) = explode("||", $val);
                //当天数据
                if (strtotime($ctime) > strtotime(date("Y-m-d"))) {
                    $cjson = json_decode($cdata, true);
                    $movementTiming = $cjson["data"]["tod"]["0"]["movement_timing"]??[];
                    $cycle = $cjson["data"]["tod"]["0"]["extra_time"]["cycle"]??0;
                    $offset = $cjson["data"]["tod"]["0"]["extra_time"]["offset"]??0;
                    if (!empty($movementTiming)) {
                        foreach ($movementTiming as $flowTiming) {
                            if ($flowTiming["flow"]["logic_flow_id"] == $data["logic_flow_id"]) {
                                $green = $flowTiming["timing"][0]["duration"]??0;
                                $yellow = $flowTiming["yellow"]??0;
                                if (isset($flowTimingCurve[date("H:i", strtotime($ctime))])) {
                                    $flowTimingCurve[date("H:i", strtotime($ctime))]["green"] += $green;
                                } else {
                                    $flowTimingCurve[date("H:i", strtotime($ctime))] = [
                                        "yellow" => $yellow,
                                        "green" => $green,
                                        "cycle" => $cycle,
                                        "offset" => $offset,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $flowTimingCurve;
    }

    /**
     * 获取散点图
     *
     * @param $data
     *
     * @return array
     */
    public function getScatterMtraj($data)
    {

        $flowTimingCurve = $this->getCurrentTimingCurv($data);
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        $endTime = time();
        $startTime = $endTime - 30 * 60;

        $esData = [
            "cityId" => $data['city_id'],
            "endTime" => $endTime,
            "movementId" => $data['logic_flow_id'],
            "source" => "signal_control",
            "startTime" => $startTime,
        ];

        $esUrl = $this->config->item('new_es_interface') . '/estimate/scatter/query';

        $detail = httpPOST($esUrl, $esData, 0, 'json');
        if (!$detail) {
            $result['errmsg'] = '调用es接口 获取散点图 失败！';
            return $result;
        }
        $detail = json_decode($detail, true);
        if ($detail['code'] != '000000') {
            $result['errmsg'] = $detail['message'];
            return $result;
        }

        if (empty($detail['result'])) {
            $result['errmsg'] = '该方向没有轨迹数据';
            return $result;
        }

        $ret['dataList'] = [];
        // 用于存储所有时间
        $timestamp = [];
        // 用于存储所有值
        $value = [];

        foreach ($detail['result'] as $k => $v) {
            $dataValueTime = date("H:i",$v['timestamp']);
            foreach ($flowTimingCurve as $flowtime=>$flowItem){
                $flowFullTimestamp = strtotime(date("Y-m-d")." ".$flowtime.":00");
                if($v['timestamp']>($flowFullTimestamp-120) && $v['timestamp']<$flowFullTimestamp){
                    $dataValueTime = date("H:i",$flowFullTimestamp);
                }
            }

            // 将时间转为秒数
            $time = date_parse(date("H:i:s", $v['timestamp']));
            $second = $time['hour'] * 3600 + $time['minute'] * 60 + $time['second'];
            $ret['dataList'][$k] = [
                $second,                 // 时间秒数 X轴
                $v['stopDelayBefore'],  // 值      Y轴
                $dataValueTime
            ];

            // 记录所有时间 用于获取最大最小值
            $timestamp[$k] = $second;
            // 记录所有值 用于获取最大最小值
            $value[$k] = $v['stopDelayBefore'];
        }

        // X轴 Y轴 信息集合
        $ret['info'] = [
            "x" => [
                "max" => 0,
                "min" => 0,
            ],
            "y" => [
                "max" => 0,
                "min" => 0,
            ],
        ];
        if (!empty($timestamp)) {
            $ret['info']['x'] = [
                'max' => max($timestamp),
                'min' => min($timestamp),
            ];
        }
        if (!empty($value)) {
            $ret['info']['y'] = [
                'max' => max($value),
                'min' => min($value),
            ];
        }

        // 获取相位配时信息
        $ret['signal_info'] = $flowTimingCurve;
        return $ret;
    }

    /**
     * 获取城市排队阈值
     * @param $cityId Y
     * @return float
     */
    public function getCityControlParams($cityId)
    {
        $this->load->config('control_params');
        $controlParams = $this->config->item('control_params');
        $cityParams = $controlParams["city"][$cityId];
        $cityParams["queue_ratio_thresh"] = $cityParams["queue_ratio_thresh"]*100;
        return $cityParams;
    }

    /**
     * 获取排队长度图
     *
     * @param $data
     *
     * @return array
     * @throws \Exception
     */
    public function getQueueLengthMtraj($data)
    {
        $endTime = time();
        $startTime = $endTime - 30 * 60;

        $esData = [
            "cityId" => $data['city_id'],
            "endTime" => $endTime,
            "movementId" => $data['logic_flow_id'],
            "source" => "signal_control",
            "startTime" => $startTime,
        ];

        $esUrl = $this->config->item('new_es_interface') . '/estimate/queue/query';

        $detail = httpPOST($esUrl, $esData, 0, 'json');
        if (!$detail) {
            throw new \Exception('调用es接口 排队长度图 失败！', ERR_DEFAULT);
        }
        $detail = json_decode($detail, true);
        if ($detail['code'] != '000000') {
            throw new \Exception($detail['message'], ERR_DEFAULT);
        }

        if (empty($detail['result'])) {
            $result['errmsg'] = '该方向没有轨迹数据';
            return $result;
        }

        // 获取某个方向的flow长度
        $flowMovement = $this->waymap_model->getFlowMovement($data['city_id'], $data['logic_junction_id'], $data['logic_flow_id']);
        if (empty($flowMovement) || empty($flowMovement['in_link_length'])) {
            throw new \Exception('flow长度获取错误', ERR_REQUEST_WAYMAP_API);
        }
        $inLinkLength = $flowMovement['in_link_length'];

        $ret['dataList'] = [];

        // 用于存储所有时间
        $timestamp = [];
        // 用于存储所有值

        foreach ($detail['result'] as $k => $v) {
            foreach ($v as $kk => $vv) {
                // 将时间转为秒数
                $time = date_parse(date("H:i:s", $vv['timestamp']));
                $second = $time['hour'] * 3600 + $time['minute'] * 60 + $time['second'];
                $ret['dataList'][$vv['timestamp']] = [
                    $second,             // 时间秒数 X轴
                    round($vv['stopDistance'] / $inLinkLength * 100, 2), // 值      Y轴
                ];

                // 记录所有时间 用于获取最大最小值
                $timestamp[$vv['timestamp']] = $second;
            }
        }

        // X轴 Y轴 信息集合
        $ret['info'] = [
            "x" => [
                "max" => 0,
                "min" => 0,
            ],
            "y" => [
                "max" => 100,
                "min" => 0,
            ],
        ];
        if (!empty($timestamp)) {
            $ret['info']['x'] = [
                'max' => max($timestamp),
                'min' => min($timestamp),
            ];
        }

        if (!empty($ret['dataList'])) {
            ksort($ret['dataList']);
            $ret['dataList'] = array_values($ret['dataList']);
        }

        // 获取相位配时信息
        $timingInfo = $this->getFlowTimingInfo($data);
        //$ret['signal_info'] = $timingInfo;
        $ret['control_params'] = $this->getCityControlParams($data['city_id']);
        return $ret;
    }
}