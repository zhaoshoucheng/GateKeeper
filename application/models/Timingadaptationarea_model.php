<?php
/********************************************
 * # desc:    自适应区域模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-07-25
 ********************************************/

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
    }

    /**
     * 获取区域列表
     * @param $data['city_id']    interger Y 城市ID
     * @return array
     */
    public function getAreaList($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = ['errno' => -1, 'errmsg' => '未知错误', 'data' =>[]];

        // 调用signal-mis接口
        try {
            $url = $this->signal_mis_interface . '/TimingAdaptation/getAreaList';

            $res = httpPOST($url, $data);
            if (empty($res)) {
                return [];
            }
            $res = json_decode($res, true);
            if ($res['errorCode'] != 0) {
                $result['errmsg'] = $res['errorMsg'];
                return $result;
            }

            // 处理数据
            $result['errno'] = 0;
            $result['data'] = $this->formatGetAreaListData($data['city_id'], $res['data']);

            return $result;
        } catch (Exception $e) {
            com_log_warning('_signal-mis_getAreaList_failed', 0, $e->getMessage(), compact("url","data","res"));
            $result['errmsg'] = '调用signal-mis的getAreaList接口出错！';
            return $result;
        }
    }

    /**
     * 格式化区域列表数据
     *    需要组织每个区域的平均延误数据、平均速度数据、
     *    平均延误与上一次对比情况、平均速度与上一次数据对比情况（0无变化、1上升、2下降）
     * @param $cityId interger Y 城市ID
     * @param $data   array    Y 数据源
     * data = array
     * (
     *      [0] => Array
     *          (
     *              [id] => 10
     *              [name] => 大明湖区域
     *              [city_id] => 12
     *              [status] => 0
     *              [llng] => 116.936191
     *              [llat] => 36.703935
     *              [rlng] => 117.076954
     *              [rlat] => 36.634540
     *              [adaptive] => 1
     *              [is_upload] => 0
     *              [junction_num] => 147
     *              [adaptive_num] => 0
     *          )
     *  )
     * @return array
     */
    private function formatGetAreaListData($cityId, $data)
    {
        if (empty($data) || (int)$cityId < 1) {
            return [];
        }

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

        foreach ($data as $k=>$v) {
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
            $junctions = $this->getAreaJunctionList($jdata);
            if (!empty($junctions['data'])) {
                // 调用数据组接口 参数 路口id串 暂时没有接口，先随机
                $speed = rand(1000, 10000000);
                $stop_delay = rand(1000, 10000000);

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
                    } else if ($speed < $oldSpeed) {
                        $speed_trend = 2; // 下降
                    } else {
                        $speed_trend = 0; // 无变化
                    }

                    // 对平均延误变化趋势进行判断
                    if ($stop_delay > $oldStopDelay) {
                        $stop_delay_trend = 1; // 上升
                    } else if ($stop_delay < $oldStopDelay) {
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
            $data[$k]['speed'] = $speed;
            $data[$k]['stop_delay'] = $stop_delay;
            $data[$k]['speed_trend'] = $speed_trend;
            $data[$k]['stop_delay_trend'] = $stop_delay_trend;

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

        return $data;
    }

    /**
     * 获取区域路口信息
     * @param $data['city_id'] interger Y 城市ID
     * @param $data['area_id'] interger Y 区域ID
     * @return array
     */
    public function getAreaJunctionList($data)
    {
        $result = ['errno'=>-1, 'errmsg'=>'', 'data'=>[]];

        // 获取区域路口
        $areaJunctions = $this->getAreaJunctions($data);
        if ($areaJunctions['errno'] != 0) {
            $result['errmsg'] = $areaJunctions['errmsg'];
            return $result;
        }
        $result['errno'] = 0;
        $result['data'] = $this->formatGetAreaJunctionListData($data['city_id'], $areaJunctions['data']);
        return $result;
    }

    /**
     * 格式化区域路口集合数据
     * 添加路口报警状态
     * @param $cityId interger Y 城市ID
     * @param $data   array    Y 数据
     * $data = [
     *     [
     *         'logic_junction_id'=>xxxx, // 路口ID
     *         'lat'              =>xxxx, // 纬度
     *         'lon'              =>xxxx, // 经度
     *         'status'           =>xxxx, // 路口类型：0:无配时；1:有配时；2:自适应；9:配时异常
     *         'source'           =>xxxx, // 数据来源
     *         'junction_name'    =>xxxx, // 路口名称
     *         'is_upload'        =>xxxx, // 自适应下发状态 0：否 1：是
     *     ],
     * ]
     * @return array
     */
    private function formatGetAreaJunctionListData($cityId, $data)
    {
        if (empty($data) || intval($cityId) < 1) {
            return [];
        }

        // 获取实时报警路口信息
        $alarmJunctions = $this->getRealTimeAlarmJunctions($cityId);
        if (empty($alarmJunctions)) {
            return [];
        }

        // 路口ID串
        $junctionIds = implode(',', array_unique(array_column($alarmJunctions, 'logic_junction_id')));
        // 获取路口相位信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($junctionIds);
        // 报警类别
        $alarmCate = $this->config->item('alarm_category');

        /**
         格式alarmJunctions使其为：
         $alarmJunctions = [
            '路口ID' => [
                '报警内容：方向-报警原因 例：东直-溢流',
                '报警内容：方向-报警原因 例：东直-溢流',
            ],
         ]
         */
        $alarmData = [];
        foreach ($alarmJunctions as $v) {
            $flowName = $flowsInfo[$v['logic_junction_id']][$v['logic_flow_id']] ?? '未知方向';
            $alarmComment = $alarmCate[$v['type']]['name'] ?? '未知报警';
            $alarmContent =  $flowName . '-' . $alarmComment;
            if (empty($alarmData[$v['logic_junction_id']])) {
                $alarmData[$v['logic_junction_id']] = [];
            }
            array_push($alarmData[$v['logic_junction_id']], $alarmContent);
        }
        array_filter($alarmData);

        foreach ($data as $k=>$v) {
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

        return $data;
    }

    /**
     * 获取区域实时报警信息
     * @param $data['city_id']     interger Y 城市ID
     * @param $data['area_id']     interger Y 区域ID
     * @param $data['alarm_type']  interger N 报警类型：0：全部；1：过饱和；2：溢流。默认0
     * @param $data['ignore_type'] interger N 忽略类型：0：全部；1：已忽略；2：未忽略。默认0
     * @return array
     */
    public function realTimeAlarmList($data)
    {
        $result = ['errno'=>-1, 'errmsg'=>'', 'data'=>[]];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        // 获取实时报警路口信息（全城）
        $alarmJunctions = $this->getRealTimeAlarmJunctions($data['city_id']);
        if (empty($alarmJunctions)) {
            $result['errno'] = 0;
            return $result;
        }

        // 根据alarm_type过滤路口
        if ($data['alarm_type'] != 0 && in_array($data['alarm_type'], [1, 2])) {
            $alarmJunctions = array_map(function($item) use ($data) {
                if ($item['type'] == $data['alarm_type']) {
                    return $item;
                }
            }, $alarmJunctions);
        }
        $alarmJunctions = array_filter($alarmJunctions);
        if (empty($alarmJunctions)) {
            $result['errno'] = 0;
            return $result;
        }

        // 获取redis中忽略的路口相位
        $areaIgnoreJunctionRedisKey = 'area_ignore_Junction_flow_' . $data['area_id'] . '_' . $data['city_id'];
        $areaIgnoreJunctionFlows = $this->redis_model->getData($areaIgnoreJunctionRedisKey);
        if (!empty($areaIgnoreJunctionFlows)) {
            /**
             redids中存在的是json格式的,json_decode后格式：
             $areaIgnoreJunctionFlows = [
                'xxxxxxxxxxxx', // logic_junction_id
                'xxxxxxxxxxxx',
             ];
             */
            $areaIgnoreJunctionFlows = json_decode($areaIgnoreJunctionFlows, true);
        }

        // 根据ignore_type过滤所需路口 1:已忽略 2:未忽略
        if ($data['ignore_type'] != 0 && in_array($data['ignore_type'], [1, 2])) {
            if ($data['ignore_type'] == 1) {
                if (empty($areaIgnoreJunctionFlows)) {
                    $alarmJunctions = [];
                } else {
                    $alarmJunctions = array_map(function($item) use ($areaIgnoreJunctionFlows){
                        if (in_array($item['logic_flow_id'], $areaIgnoreJunctionFlows, true)) {
                            return $item;
                        }
                    }, $alarmJunctions);
                }
            } else {
                if (!empty($areaIgnoreJunctionFlows)) {
                    $alarmJunctions = array_map(function($item) use ($areaIgnoreJunctionFlows){
                        if (!in_array($item['logic_flow_id'], $areaIgnoreJunctionFlows, true)) {
                            return $item;
                        }
                    }, $alarmJunctions);
                }
            }
            $alarmJunctions = array_filter($alarmJunctions);
        }
        if (empty($alarmJunctions)) {
            $result['errno'] = 0;
            return $result;
        }

        // 获取区域路口
        $areaJunctions = $this->getAreaJunctions($data);
        if ($areaJunctions['errno'] != 0) {
            $result['errmsg'] = $areaJunctions['errmsg'];
            return $result;
        }
        $areaJuncitonIds = array_column($areaJunctions['data'], 'logic_junction_id');

        // 从全城报警路口中取出区域报警路口
        $areaAlarmJunctions = [];
        foreach ($alarmJunctions as $k=>$v) {
            if (in_array($v['logic_junction_id'], $areaJuncitonIds, true)) {
                $areaAlarmJunctions[$k] = $v;
            }
        }
        if (empty($areaAlarmJunctions)) {
            $result['errno'] = 0;
            return $result;
        }

        // 需要获取路口name的路口ID串
        $junctionIds = implode(',', array_unique(array_column($areaAlarmJunctions, 'logic_junction_id')));
        // 获取路口信息
        $junctionsInfo = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');
        // 获取路口相位信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($junctionIds);
        // 报警类别
        $alarmCate = $this->config->item('alarm_category');

        /* 获取报警信息人工校验信息 */
        // 路口ID
        $junctionId = array_column($areaAlarmJunctions, 'logic_junction_id');
        // flowID
        $flowId = array_column($areaAlarmJunctions, 'logic_flow_id');
        $alarmRemarks = $this->getAlarmRemarks($data['city_id'], $data['area_id'], $junctionId, $flowId);
        // 人工校验信息 [flowid => type]
        $alarmRemarksFlowKeyTypeValue = [];
        if (!empty($alarmRemarks)) {
            $alarmRemarksFlowKeyTypeValue = array_column($alarmRemarks, 'type', 'logic_flow_id');
        }

        foreach ($areaAlarmJunctions as $k=>$val) {
            // 持续时间
            $durationTime = (strtotime($val['last_time']) - strtotime($val['start_time'])) / 60;
            if ($durationTime == 0) {
                // 当前时间
                $nowTime = time();
                $tempDurationTime = ($nowTime - strtotime($val['start_time'])) / 60;
                // 默认持续时间为1分钟 有的只出现一次，表里记录last_time与start_time相等
                if ($tempDurationTime < 1) {
                    $durationTime = 1;
                } else {
                    $durationTime = $tempDurationTime;
                }
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
                && !empty($flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']])) {
                $result['data'][$k] = [
                    'start_time'        => date('H:i', strtotime($val['start_time'])),
                    'duration_time'     => round($durationTime),
                    'logic_junction_id' => $val['logic_junction_id'],
                    'junction_name'     => $junctionIdName[$val['logic_junction_id']] ?? '',
                    'logic_flow_id'     => $val['logic_flow_id'],
                    'flow_name'         => $flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']] ?? '',
                    'alarm_comment'     => $alarmCate[$val['type']]['name'] ?? '',
                    'alarm_key'         => $val['type'],
                    'is_ignore'         => $is_ignore,
                    'check'             => $check,
                ];
            }
        }
        if (!empty($result['data'])) {
            $result['data'] = array_values($result['data']);
        }

        $result['errno'] = 0;
        return $result;
    }

    /**
     * 人工标注报警信息
     * @param $data['city_id']           interger Y 城市ID
     * @param $data['area_id']           interger Y 区域ID
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @param $data['logic_flow_id']     string   Y 相位ID
     * @param $data['is_correct']        interger Y 是否正确 1：正确 2：错误
     * @param $data['comment']           string   N 备注信息
     * @return array
     */
    public function addAlarmRemark($data)
    {
        $result = ['errno'=>-1, 'errmsg'=>'', 'data'=>''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        $ret = $this->db->insert('time_alarm_remarks', [
            'city_id'           => $data['city_id'],
            'area_id'           => $data['area_id'],
            'logic_junction_id' => $data['logic_junction_id'],
            'logic_flow_id'     => $data['logic_flow_id'],
            'type'              => $data['is_correct'],
            'comment'           => $data['comment'],
            'username'          => 0
        ]);
        if (!$ret) {
            $result['errmsg'] = '添加失败！';
            return $result;
        }

        $result['errno'] = 0;
        $result['data'] = 'success.';
        return $result;
    }

    /**
     * 忽略报警 将忽略的flow存入redis时效30分钟
     * @param city_id       interger Y 城市ID
     * @param area_id       interger Y 区域ID
     * @param logic_flow_id string   Y 相位ID
     * @return array
     */
    public function ignoreAlarm($data)
    {
        $result = ['errno'=>-1, 'errmsg'=>'', 'data'=>''];

        if (empty($data)) {
            $result['errmsg'] = 'data 不能为空！';
            return $result;
        }

        // redis KEY
        $areaIgnoreJunctionRedisKey = 'area_ignore_Junction_flow_' . $data['area_id'] . '_' . $data['city_id'];
        $areaIgnoreJunctionFlows = $this->redis_model->getData($areaIgnoreJunctionRedisKey);
        if (!empty($areaIgnoreJunctionFlows)) {
            /**
             redids中存在的是json格式的,json_decode后格式：
             $areaIgnoreJunctionFlows = [
                'xxxxxxxxxxxx', // logic_junction_id
                'xxxxxxxxxxxx',
             ];
             */
            $areaIgnoreJunctionFlows = json_decode($areaIgnoreJunctionFlows, true);
        } else {
            $areaIgnoreJunctionFlows = [];
        }

        // 向数组中添加新的数据
        array_push($areaIgnoreJunctionFlows, $data['logic_flow_id']);

        $this->redis_model->setEx($areaIgnoreJunctionRedisKey, json_encode($areaIgnoreJunctionFlows), 30 * 60);

        $result['errno'] = 0;
        $result['data'] = 'success.';
        return $result;
    }

    /**
     * 更新自适应路口开关
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @param $data['area_id']           interger Y 区域ID
     * @param $data['is_upload']         interger Y 变更状态 0：关闭；1：打开
     * @return array
     */
    public function junctionSwitch($data)
    {
        $result = ['errno'=>-1, 'errmsg'=>'', 'data'=>''];

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
            $result['data'] = $res['data'];

            return $result;
        } catch (Exception $e) {
            com_log_warning('_signal-mis_junctionSwitch_failed', 0, $e->getMessage(), compact("url","data","res"));
            $result['errmsg'] = '调用signal-mis的junctionSwitch接口出错！';
            return $result;
        }
    }

    /**
     * 获取区域路口信息(公用)
     * @param $data['city_id'] interger Y 城市ID
     * @param $data['area_id'] interger Y 区域ID
     * @return array
     */
    private function getAreaJunctions($data)
    {
        $url = $this->signal_mis_interface . '/TimingAdaptation/getAreaJunctionList';
        $data = [
            'city_id' => $data['city_id'],
            'area_id' => $data['area_id'],
        ];

        $result = ['errno'=>-1, 'errmsg'=>'', 'data'=>[]];

        try {
            $junctions = httpPOST($url, $data);
            if (empty($junctions)) {
                $result['errno'] = 0;
                return $result;
            }
            $junctions = json_decode($junctions, true);
            if ($junctions['errorCode'] != 0) {
                $result['errmsg'] = $junctions['errorMsg'];
                return $result;
            }

            $result['errno'] = 0;
            $result['data'] = $junctions['data'] ?? [];
            return $result;
        } catch (Exception $e) {
            com_log_warning('_signal-mis_getAreaJunctionList_failed', 0, $e->getMessage(), compact("url","data","junctions"));
            $result['errmsg'] = '调用signal-mis的getAreaJunctionList接口出错！';
            return $result;
        }
    }

    /**
     * 获取实时报警路口信息
     * @param $cityId interger Y 城市ID
     * @return array
     */
    private function getRealTimeAlarmJunctions($cityId)
    {
        $result = [];

        // 先去redis查数据，如果没有则查表
        $alarmRedisKey = 'its_realtime_alarm_' . $cityId;

        $result = $this->redis_model->getData($alarmRedisKey);
        $result = json_decode($result, true);
        if (empty($result)) {
            if (!$this->isTableExisted($this->tb)) {
                return [];
            }

            // 获取最近时间
            $lastHour = $this->getLastestHour($cityId);

            $lastTime = date('Y-m-d') . ' ' . $lastHour;
            $cycleTime = date('Y-m-d H:i:s', strtotime($lastTime) + 300);

            $sql = '/*{"router":"m"}*/';
            $sql .= 'select type, logic_junction_id, logic_flow_id, start_time, last_time';
            $sql .= ' from ' . $this->tb;
            $sql .= ' where city_id = ?  and date = ?';
            $sql .= ' and last_time >= ? and last_time <= ?';
            $sql .= ' order by type asc, (last_time - start_time) desc';
            $result = $this->db->query($sql, [
                $data['city_id'],
                $data['date'],
                $lastTime,
                $cycleTime
            ])->result_array();

            if (empty($result)) {
                return [];
            }
        }

        return $result;
    }

    /**
     * 获取报警人工校验信息
     * @param $cityId     interger Y 城市ID
     * @param $areaId     interger Y 区域ID
     * @param $junctionId array    Y 路口ID ['xxxxx', 'xxxxx']
     * @param $flowId     array    Y 相位ID ['xxxx', 'xxxxxx']
     * @return array
     */
    private function getAlarmRemarks($cityId, $areaId, $junctionId, $flowId)
    {
        $table = 'time_alarm_remarks';
        if (!$this->isTableExisted($table)) {
            return [];
        }

        $sql = 'select logic_flow_id, type from ' . $table;
        $sql .= ' where city_id = ?';
        $sql .= ' and area_id = ?';
        $sql .= ' and logic_junction_id in (';

        $junctionIn = '';
        foreach ($junctionId as $v) {
            $junctionIn .= empty($junctionIn) ? "'{$v}'" : ", '{$v}'";
        }
        $sql .= $junctionIn . ')';
        $sql .= ' and logic_flow_id in (';

        $flowIn = '';
        foreach ($flowId as $v) {
            $flowIn .= empty($flowIn) ? "'{$v}'" : ", '{$v}'";
        }
        $sql .= $flowIn . ')';

        $result = $this->db->query($sql, [$cityId, $areaId])->result_array();
        return $result;
    }

    /**
     * 获取指定日期最新的数据时间
     * @param $table
     * @param null $date
     * @return false|string
     */
    private function getLastestHour($cityId, $date = null)
    {
        $hour = $this->redis_model->getData("its_realtime_lasthour_$cityId");
        if(!empty($hour)) {
            return $hour;
        }
        if (!$this->isTableExisted('real_time_' . $cityId)) {
            return date('H:i:s');
        }

        $date = $date ?? date('Y-m-d');

        $result = $this->db->select('hour')
            ->from('real_time_' . $cityId)
            ->where('updated_at >=', $date . ' 00:00:00')
            ->where('updated_at <=', $date . ' 23:59:59')
            ->order_by('hour', 'desc')
            ->limit(1)
            ->get()->first_row();

        if(!$result) {
            return date('H:i:s');
        }

        return $result->hour;
    }

    /**
     * 校验数据表是否存在
     */
    private function isTableExisted($table)
    {
        $isExisted = $this->db->table_exists($table);
        return $isExisted;
    }
}
