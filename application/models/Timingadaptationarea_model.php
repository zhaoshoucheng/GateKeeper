<?php
/********************************************
 * # desc:    自适应区域模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-07-25
 ********************************************/

use Didi\Cloud\Collection\Collection;
use Overtrue\Pinyin\Pinyin;

class Timingadaptationarea_model extends CI_Model
{
    private $signal_mis_interface = '';
    private $tb = 'real_time_alarm';
    private $db = '';

    public function __construct()
    {
        parent::__construct();

        // signal-mis 接口域名
        $this->signal_mis_interface = $this->config->item('signal_mis_interface');

        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('realtime_conf.php');
        $this->load->model('waymap_model');
        $this->load->model('redis_model');
        $this->load->model('common_model');
    }

    /**
     * 人工标注报警信息
     *
     * @param $data ['city_id']           interger Y 城市ID
     * @param $data ['area_id']           interger Y 区域ID
     * @param $data ['logic_junction_id'] string   Y 路口ID
     * @param $data ['logic_flow_id']     string   Y 相位ID
     * @param $data ['is_correct']        interger Y 是否正确 1：正确 2：错误
     * @param $data ['comment']           string   N 备注信息
     *
     * @return array
     */
    public function addAlarmRemark($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        $ret = $this->db->insert('time_alarm_remarks', [
            'city_id' => $data['city_id'],
            'area_id' => $data['area_id'],
            'logic_junction_id' => $data['logic_junction_id'],
            'logic_flow_id' => $data['logic_flow_id'],
            'type' => $data['is_correct'],
            'comment' => $data['comment'],
            'username' => 0,
        ]);
        if (!$ret) {
            $result['errmsg'] = '添加失败！';
            return $result;
        }

        $result['errno'] = 0;
        $result['data']  = 'success.';
        return $result;
    }

    /**
     * 忽略报警 将忽略的flow存入redis时效30分钟
     *
     * @param city_id       interger Y 城市ID
     * @param area_id       interger Y 区域ID
     * @param logic_flow_id string   Y 相位ID
     *
     * @return array
     */
    public function ignoreAlarm($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        // redis KEY
        $areaIgnoreJunctionRedisKey = 'area_ignore_Junction_flow_' . $data['area_id'] . '_' . $data['city_id'];
        $areaIgnoreJunctionFlows    = $this->redis_model->getData($areaIgnoreJunctionRedisKey);
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

        $result['errno'] = 0;
        $result['data']  = 'success.';
        return $result;
    }

    /**
     * 更新自适应路口开关
     *
     * @param $data ['logic_junction_id'] string   Y 路口ID
     * @param $data ['area_id']           interger Y 区域ID
     * @param $data ['is_upload']         interger Y 变更状态 0：关闭；1：打开
     *
     * @return array
     */
    public function junctionSwitch($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        // 调用signal-mis接口
        try {
            $url = $this->signal_mis_interface . '/TimingAdaptation/junctionSwitch';

            $res = httpPOST($url, $data);
            $res = json_decode($res, true);
            if ($res['errorCode'] != 0) {
                $result['errmsg'] = $res['errorMsg'];
                return $result;
            }

            $result['errno'] = 0;
            $result['data']  = 'success.';
            return $result;
        } catch (Exception $e) {
            com_log_warning('_signal-mis_junctionSwitch_failed', 0, $e->getMessage(), compact("url", "data", "res"));
            $result['errmsg'] = '调用signal-mis的junctionSwitch接口出错！';
            return $result;
        }
    }

    /**
     * 更新自适应区域开关
     *
     * @param $data ['city_id']   interger Y 城市ID
     * @param $data ['area_id']   interger Y 区域ID
     * @param $data ['is_upload'] interger Y 变更状态 0：关闭；1：打开
     *
     * @return array
     */
    public function areaSwitch($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        // 调用signal-mis接口
        try {
            $url = $this->signal_mis_interface . '/TimingAdaptation/areaSwitch';

            $res = httpPOST($url, $data);
            $res = json_decode($res, true);
            if ($res['errorCode'] != 0) {
                $result['errmsg'] = $res['errorMsg'];
                return $result;
            }

            $result['errno'] = 0;
            $result['data']  = 'success.';
            return $result;
        } catch (Exception $e) {
            com_log_warning('_signal-mis_areaSwitch_failed', 0, $e->getMessage(), compact("url", "data", "res"));
            $result['errmsg'] = '调用signal-mis的areaSwitch接口出错！';
            return $result;
        }
    }

    /**
     * 获取区域指标折线图
     *
     * @param $data ['city_id']   interger Y 城市ID
     * @param $data ['area_id']   interger Y 区域ID
     * @param $data ['quota_key'] string   Y 指标KEY avgSpeed / stopDelay
     *
     * @return array
     */
    public function getAreaQuotaInfo($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        // 获取路口ID串
        $junctions = $this->getAreaJunctions($data);
        if (empty($junctions['data'])) {
            $result['errmsg'] = '此区域没有路口！';
            return $result;
        }

        $esJunctionIds = implode(',', array_filter(array_column($junctions['data'], 'logic_junction_id')));

        // 当前时间 毫秒级时间戳
        $endTime = (int)(time() * 1000);
        // 开始时间 当天开始时间 毫秒级时间戳
        $startTime = strtotime('00:00:00') * 1000;

        $esData = [
            "source" => "signal_control",
            "cityId" => $data['city_id'],
            'requestId' => get_traceid(),
            "junctionId" => $esJunctionIds,
            "timestamp" => "[{$startTime}, {$endTime}]",
            "source" => 'signal_control',
            "andOperations" => [
                "junctionId" => "in",
                "cityId" => "eq",
                "timestamp" => "range",
            ],
            "quotaRequest" => [
                "quotaType" => "weight_avg",
                "quotas" => "sum_{$data['quota_key']}*trailNum, sum_trailNum",
                "groupField" => "dayTime",
                "orderField" => "dayTime",
                "asc" => "true",
            ],
        ];

        $esUrl = $this->config->item('es_interface') . '/estimate/diagnosis/queryQuota';

        try {
            $quotaInfo = httpPOST($esUrl, $esData, 0, 'json');
            if (!$quotaInfo) {
                $result['errmsg'] = '调用es接口 获取区域指标折线图 失败！';
                return $result;
            }
            $quotaInfo = json_decode($quotaInfo, true);
            if ($quotaInfo['code'] != '000000') {
                $result['errmsg'] = $quotaInfo['message'];
                return $result;
            }
            $quotaValueInfo = [];
            if (!empty($quotaInfo['result']['quotaResults'])) {
                $quotaValueInfo = $quotaInfo['result']['quotaResults'];
            }

            $ret = [];
            foreach ($quotaValueInfo as $k => $item) {
                $value = $item['quotaMap']['weight_avg'];
                if ($data['quota_key'] == 'avgSpeed') {
                    // 速度m/s转换为km/h
                    $value = $item['quotaMap']['weight_avg'] * 3.6;
                }
                $dayTime = date('H:i:s', strtotime($item['quotaMap']['dayTime']));
                $ret[$k] = [
                    $dayTime, // 时间 X轴
                    round($value, 2),                                       // 值   Y轴
                ];
            }

            /*
            unset($ret[0]);
            unset($ret[1]);
            unset($ret[2]);
            unset($ret[3]);
            $ret = array_values($ret);
            */

            $tmpRet      = [];
            $lastDayTime = "00:00:00";
            for ($i = 0; $i < count($ret);) {
                $nowTime  = strtotime($ret[$i][0]);
                $lastTime = strtotime($lastDayTime);
                // 如果两个距离不是顺序的
                if ($lastTime > $nowTime) {
                    $i++;
                    continue;
                }

                // 如果两个距离小于15分钟
                if ($nowTime - $lastTime < 15 * 60) {
                    $tmpRet[]    = $ret[$i];
                    $lastDayTime = $ret[$i][0];
                    $i++;
                    continue;
                }

                if ($lastDayTime == "00:00:00") {
                    $tmpRet[] = [
                        $lastDayTime,
                        null,
                    ];
                }

                // 两个距离大于15分钟
                $lastDayTime = $tmpDayTime = date("H:i:s", $lastTime + 15 * 60);
                $tmpRet[]    = [
                    $tmpDayTime,
                    null,
                ];
                continue;
            }

            $result['errno'] = 0;
            $result['data']  = !empty($tmpRet) ? $tmpRet : [];
            return $result;
        } catch (Exception $e) {
            com_log_warning('_es_queryQuota_failed', 0, $e->getMessage(), compact("esUrl", "esData", "quotaInfo"));
            $result['errmsg'] = '调用es的获取区域指标折线图接口出错！';
            return $result;
        }
    }

    /**
     * 获取时空图
     *
     * @param $data ['city_id']           interger Y 城市ID
     * @param $data ['logic_junction_id'] string   Y 路口ID
     * @param $data ['logic_flow_id']     string   Y 相位ID
     *
     * @return array
     */
    public function getSpaceTimeMtraj($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        $endTime   = time();
        $startTime = $endTime - 30 * 60;

        $esData = [
            "cityId" => $data['city_id'],
            "endTime" => $endTime,
            "movementId" => $data['logic_flow_id'],
            "source" => "signal_control",
            "startTime" => $startTime,
        ];

        $esUrl = $this->config->item('es_interface') . '/estimate/space/query';

        try {

            // 获取相位配时信息
            $timingInfo = $this->getFlowTimingInfo($data);
            if ($timingInfo['errno'] != 0) {
                $result['errmsg'] = $timingInfo['errmsg'];
                return $result;
            }

            if (empty($timingInfo['data'])) {
                $result['errmsg'] = '路口该方向无配时信息';
                return $result;
            }

            $cycleLength = $timingInfo['data']['cycle'];
            $offset      = $timingInfo['data']['offset'];
            if (empty($cycleLength)) {
                $result['errmsg'] = '路口该方向相位差为空';
                return $result;
            }

            $detail = httpPOST($esUrl, $esData, 0, 'json');
            if (!$detail) {
                $result['errmsg'] = '调用es接口 获取时空图 失败！';
                return $result;
            }
            $detail = json_decode($detail, true);
            if ($detail['code'] != '000000') {
                $result['errmsg'] = $detail['message'];
                return $result;
            }

            if (empty($detail['result'])) {
                $result['errmsg'] = '该方向无轨迹';
                return $result;
            }

            $ret['dataList'] = [];

            foreach ($detail['result'] as $k => $v) {
                // 按时间正序排序
                $timestampArr = array_column($v, 'timestamp');
                sort($timestampArr);
                array_multisort($timestampArr, SORT_DESC, $v);

                foreach ($v as $kk => $vv) {
                    // 将时间转为秒数
                    $time                     = date_parse(date("H:i:s", $vv['timestamp']));
                    $second                   = $time['hour'] * 3600 + $time['minute'] * 60 + $time['second'];
                    $ret['dataList'][$k][$kk] = [
                        $second,                      // 时间秒数 X轴
                        $vv['distanceToStopBar'] * -1 // 值      Y轴
                    ];
                }

                $ret['dataList'][$k] = $this->getTrajsInOneCycle($ret['dataList'][$k], $cycleLength, $offset);
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

            $ret['signal_info'] = $timingInfo['data'];

            $result['errno'] = 0;
            $result['data']  = $ret;

            return $result;
        } catch (Exception $e) {
            com_log_warning('_es_space_query_failed', 0, $e->getMessage(), compact("esUrl", "esData", "detail"));
            $result['errmsg'] = '调用es的获取时空图接口出错！';
            return $result;
        }
    }

    /**
     * 获取某一相位的自适应配置信息
     *
     * @param $data ['logic_junction_id'] string Y 路口ID
     *
     * @return array
     */
    private function getFlowTimingInfo($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        $table = 'adapt_timing_mirror';
        if (!$this->isTableExisted($table)) {
            $result['errmsg'] = '数据adapt_timing_mirror表不存在！';
            return $result;
        }

        $sql = 'select timing_info from ' . $table;
        $sql .= ' where logic_junction_id = ?';

        $resData = $this->db->query($sql, [$data['logic_junction_id']])->row_array();
        if (empty($resData['timing_info'])) {
            $result['errno'] = 0;
            return $result;
        }

        $Info = json_decode($resData['timing_info'], true);

        list($timingInfo) = $Info['data']['tod'];
        if (empty($timingInfo)) {
            $result['errno'] = 0;
            return $result;
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

        $result['errno'] = 0;
        $result['data']  = $res;
        return $result;
    }

    /**
     * 获取把时空图轨迹压缩在一个周期内
     *
     * @param $trajs       轨迹数据,二维数组
     * @param $cycleLength 周期时长
     * @param $offset      相位差
     *
     * @return array 调整后的轨迹
     */
    private function getTrajsInOneCycle(array $trajs, int $cycleLength, int $offset)
    {
        $trajsCol = Collection::make($trajs);

        $min = $trajsCol->reduce(function ($a, $b) {
            if ($a == null) {
                return $b;
            }
            if (abs($a[1]) < abs($b[1])) {
                return $a;
            }
            return $b;
        });

        $crossTime = $min[0]; // 过路口时间
        $shiftTime = $crossTime - (($crossTime - $offset) % $cycleLength);
        $minTime   = $crossTime - $shiftTime - 2 * $cycleLength;
        $maxTime   = $crossTime - $shiftTime + 1.5 * $cycleLength;

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
     * 获取散点图
     *
     * @param $data ['city_id']           interger Y 城市ID
     * @param $data ['logic_junction_id'] string   Y 路口ID
     * @param $data ['logic_flow_id']     string   Y 相位ID
     *
     * @return array
     */
    public function getScatterMtraj($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        $endTime   = time();
        $startTime = $endTime - 30 * 60;

        $esData = [
            "cityId" => $data['city_id'],
            "endTime" => $endTime,
            "movementId" => $data['logic_flow_id'],
            "source" => "signal_control",
            "startTime" => $startTime,
        ];

        $esUrl = $this->config->item('es_interface') . '/estimate/scatter/query';

        try {
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
                // 将时间转为秒数
                $time                = date_parse(date("H:i:s", $v['timestamp']));
                $second              = $time['hour'] * 3600 + $time['minute'] * 60 + $time['second'];
                $ret['dataList'][$k] = [
                    $second,                 // 时间秒数 X轴
                    $v['stopDelayBefore'],  // 值      Y轴
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
            $timingInfo = $this->getFlowTimingInfo($data);
            if ($timingInfo['errno'] != 0) {
                $result['errmsg'] = $timingInfo['errmsg'];
                return $result;
            }

            $ret['signal_info'] = $timingInfo['data'];

            $result['errno'] = 0;
            $result['data']  = $ret;

            return $result;
        } catch (Exception $e) {
            com_log_warning('_es_scatter_query_failed', 0, $e->getMessage(), compact("esUrl", "esData", "detail"));
            $result['errmsg'] = '调用es的获取散点图接口出错！';
            return $result;
        }
    }

    /**
     * 获取排队长度图
     *
     * @param $data ['city_id']           interger Y 城市ID
     * @param $data ['logic_junction_id'] string   Y 路口ID
     * @param $data ['logic_flow_id']     string   Y 相位ID
     *
     * @return array
     */
    public function getQueueLengthMtraj($data)
    {
        $result = ['errno' => -1, 'errmsg' => '', 'data' => ''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        $endTime   = time();
        $startTime = $endTime - 30 * 60;

        $esData = [
            "cityId" => $data['city_id'],
            "endTime" => $endTime,
            "movementId" => $data['logic_flow_id'],
            "source" => "signal_control",
            "startTime" => $startTime,
        ];

        $esUrl = $this->config->item('es_interface') . '/estimate/queue/query';

        try {
            $detail = httpPOST($esUrl, $esData, 0, 'json');
            if (!$detail) {
                $result['errmsg'] = '调用es接口 排队长度图 失败！';
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

            // 获取某个方向的flow长度
            $flowMovement = $this->waymap_model->getFlowMovement($data['city_id'], $data['logic_junction_id'], $data['logic_flow_id']);
            if (empty($flowMovement) || empty($flowMovement['in_link_length'])) {
                $result['errmsg'] = 'flow长度获取错误';
                return $result;
            }
            $inLinkLength = $flowMovement['in_link_length'];

            $ret['dataList'] = [];

            // 用于存储所有时间
            $timestamp = [];
            // 用于存储所有值
            $value = [];
            foreach ($detail['result'] as $k => $v) {
                foreach ($v as $kk => $vv) {
                    // 将时间转为秒数
                    $time                              = date_parse(date("H:i:s", $vv['timestamp']));
                    $second                            = $time['hour'] * 3600 + $time['minute'] * 60 + $time['second'];
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
            if ($timingInfo['errno'] != 0) {
                $result['errmsg'] = $timingInfo['errmsg'];
                return $result;
            }

            $ret['signal_info'] = $timingInfo['data'];

            $result['errno'] = 0;
            $result['data']  = $ret;

            return $result;
        } catch (Exception $e) {
            com_log_warning('_es_queue_query_failed', 0, $e->getMessage(), compact("esUrl", "esData", "detail"));
            $result['errmsg'] = '调用es的获取排队长度图接口出错！';
            return $result;
        }
    }
}
