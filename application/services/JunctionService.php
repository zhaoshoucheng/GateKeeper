<?php
/**
 * 路口相关接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-01
 */

namespace Services;

class JunctionService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->config('nconf');

        $this->load->model('waymap_model');
        $this->load->model('junction_model');
        $this->load->model('timing_model');
    }

    /**
     * 获取全城路口信息
     * @param $params['task_id']    interger 任务ID
     * @param $params['type']       interger 计算指数类型 1：统合 0：时间点
     * @param $params['city_id']    interger 城市ID
     * @param $params['time_point'] string   评估时间点 指标计算类型为1时非空
     * @param $params['confidence'] interger 置信度
     * @param $params['quota_key']  string   指标key
     * @return mixed
     * @throws \Exception
     */
    public function getAllCityJunctionInfo($params)
    {
        // 获取全城路口模板 没有模板就没有lng、lat = 画不了地图
        $allCityJunctions = $this->waymap_model->getAllCityJunctions($params['city_id']);
        if (count($allCityJunctions) < 1 || !$allCityJunctions) {
            throw new \Exception('没有获取到全城路口！', ERR_REQUEST_WAYMAP_API);
        }

        // 指标key 指标KEY与数据表字段相同
        $quotaKey = $params['quota_key'];

        // 查询字段定义
        $select = 'id, junction_id';

        // 按查询方式组织select
        if ($params['type'] == 1) { // 综合查询
            $select .= ", max({$quotaKey}) as {$quotaKey}";
        } else {
            $select .= ',' . $quotaKey;
        }

        // 获取数据
        $data = $this->junction_model->getAllCityJunctionInfo($params, $select);
        if (empty($data)) {
            return [];
        }

        // 路口指标配置
        $quotaKeyConf = $this->config->item('junction_quota_key');

        $tempQuotaData = [];
        foreach ($data as &$v) {
            // 指标状态 1：高 2：中 3：低
            $v['quota_status'] = $quotaKeyConf[$quotaKey]['status_formula']($v[$quotaKey]);

            $v[$quotaKey] = $quotaKeyConf[$quotaKey]['round']($v[$quotaKey]);
            $tempQuotaData[$v['junction_id']]['list'][$quotaKey] = $v[$quotaKey];
            $tempQuotaData[$v['junction_id']]['list']['quota_status'] = $v['quota_status'];
        }

        // 与全城路口合并
        $resultData = $this->mergeAllJunctions($allCityJunctions, $tempQuotaData, 'quota_detail');

        return $resultData;
    }

    /**
     * 获取路口指标详情
     * @param $params['task_id']         interger 任务ID
     * @param $params['junction_id']     string   逻辑路口ID
     * @param $params['dates']           array    评估/诊断日期
     * @param $params['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
     * @param $params['time_point']      string   时间点 当search_type = 0 时 必传
     * @param $params['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
     * @param $params['type']            interger 详情类型 1：指标详情页 2：诊断详情页
     * @param $params['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $params['timingType']      interger 配时来源 1：人工 2：反推
     * @return array
     */
    public function getFlowQuotas($params)
    {
        if ((int)$params['type'] == 2) { // 诊断详情页
            $res = $this->getDiagnoseJunctionDetail($params);
        } else { // 指标详情页
            $res = $this->getQuotaJunctionDetail($params);
        }

        if (!$res) {
            return [];
        }

        return $res;
    }

    /**
     * 获取诊断列表页简易路口详情
     * @param $params['task_id']         interger 任务ID
     * @param $params['junction_id']     string   逻辑路口ID
     * @param $params['dates']           array    评估/诊断日期
     * @param $params['time_point']      string   时间点
     * @param $params['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $params['diagnose_key']    array    诊断问题KEY
     * @param $params['timingType']      interger 配时来源 1：人工 2：反推
     * @return array
     */
    public function getDiagnosePageSimpleJunctionDetail($params)
    {
        /*
         * 因为过饱和问题与空放问题共用一个指标，现空放问题的KEY与指标KEY相同
         * 所以可以把过饱和问题的KEY忽略
         */
        $tempDiagnoseKey = [];
        foreach ($params['diagnose_key'] as $k=>$v) {
            $tempDiagnoseKey[$k] = $v;
            if ($v == 'over_saturation') {
                $tempDiagnoseKey[$k] = 'saturation_index';
            }
        }
        array_unique($tempDiagnoseKey);
        $selectStr = $this->selectColumns($tempDiagnoseKey);
        $select = "id, junction_id, {$selectStr}, start_time, end_time, movements, time_point";

        $data = $this->junction_model->getDiagnosePageSimpleJunctionDetail($params, $select);
        if (!$data) {
            return [];
        }

        $dates = $params['dates'];
        $timingType = $params['timingType'];

        // 将json转为数组
        $data['movements'] = json_decode($data['movements'], true);
        if (empty($data['movements'])) {
            return [];
        }

        $resultData = [];
        // 扩展指标字段
        $resultData['extend_flow_quota']['confidence'] = '置信度';

        // 获取flow_id=>name数组
        $timingData = [
            'junction_id' => trim($data['junction_id']),
            'dates'       => $dates,
            'time_range'  => $data['start_time'] . '-' . date("H:i", strtotime($data['end_time']) - 60),
            'timingType'  => $timingType
        ];
        $flowIdName = $this->timing_model->getFlowIdToName($timingData);

        // 置信度配置
        $confidenceConf = $this->config->item('confidence');

        // flow 所有指标配置
        $flowQuotaKeyConf = $this->config->item('flow_quota_key');
        // 指标集合
        foreach ($flowQuotaKeyConf as $k => $v) {
            $resultData['flow_quota'][$k]['name'] = $flowQuotaKeyConf[$k]['name'];
            $resultData['flow_quota'][$k]['unit'] = $flowQuotaKeyConf[$k]['unit'];
        }

        $tempArr = array_merge($flowQuotaKeyConf, ['movement_id'=>'', 'confidence'=>'', 'comment'=>'']);
        foreach ($data['movements'] as $k=>$v) {
            $v['comment'] = $flowIdName[$v['movement_id']] ?? '';
            $v['confidence'] = $confidenceConf[$v['confidence']]['name'];
            foreach ($flowQuotaKeyConf as $kk=>$vv) {
                if (isset($v[$kk])) {
                    $v[$kk] = $vv['round']($v[$kk]);
                }
            }
            $resultData['notmal_movements'][$k] = array_intersect_key($v, $tempArr);
        }

        // 诊断问题配置
        $diagnoseConf = $this->config->item('diagnose_key');

        /*********************************************
            循环诊断问题配置
            判断此路口有哪个问题
            匹配movement中文名称
            匹配置信度中文名称
            匹配此路口有问题的movement并放入此问题集合中
        *********************************************/
        foreach ($diagnoseConf as $k=>$v) {
            /*
             * 因为过饱和问题与空放问题同用一个指标，现定义空放问题的KEY与指标相同
             * 所以当问题是过饱和时，需要进行问题KEY与指标保持一致处理
             */
            $diagnoseKey = $k;
            if ($k == 'over_saturation') {
                $diagnoseKey = 'saturation_index';
            }
            if (!isset($data[$diagnoseKey])) {
                continue;
            }
            if ($v['junction_diagnose_formula']($data[$diagnoseKey])) {
                // 问题名称
                $resultData['diagnose_detail'][$k]['name'] = $v['name'];

                // 组织有此问题的movement集合
                $resultData['diagnose_detail'][$k]['movements'] = [];

                foreach ($data['movements'] as $kk=>$vv) {
                    // 问题对应的指标
                    $diagnoseQuota = $v['flow_diagnose']['quota'];
                    if ($v['flow_diagnose']['formula']($vv[$diagnoseQuota])) {
                        // movement_id
                        $resultData['diagnose_detail'][$k]['movements'][$kk]['movement_id']
                        = $vv['movement_id'];
                        // movement中文名称-相位名称
                        $resultData['diagnose_detail'][$k]['movements'][$kk]['comment']
                        = $flowIdName[$vv['movement_id']];
                        // 此问题对应指标值
                        $resultData['diagnose_detail'][$k]['movements'][$kk][$diagnoseQuota]
                        = $flowQuotaKeyConf[$diagnoseQuota]['round']($vv[$diagnoseQuota]);
                        // 置信度
                        $resultData['diagnose_detail'][$k]['movements'][$kk]['confidence']
                        = $confidenceConf[$vv['confidence']]['name'];
                    }
                }

                if (!empty($resultData['diagnose_detail'][$k]['movements'])) {
                    $resultData['diagnose_detail'][$k]['movements']
                    = array_values($resultData['diagnose_detail'][$k]['movements']);
                }
            }
        }

        return $resultData;
    }

    /**
    * 格式化诊断列表页简易路口详情数据
    * @param $data        路口详情数据
    * @param $dates       评估/诊断日期
    * @param $timingType  配时数据来源 1：人工 2：反推
    */
    private function formatDiagnosePageSimpleJunctionDetailData($data, $dates, $timingType)
    {
        if (empty($data) || empty($dates)) {
            return [];
        }

        
    }

    /**
     * 获取诊断详情页数据
     * @param $data['task_id']         interger 任务ID
     * @param $data['junction_id']     string   逻辑路口ID
     * @param $data['dates']           array    评估/诊断日期
     * @param $data['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
     * @param $data['time_point']      string   时间点 当search_type = 0 时 必传
     * @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
     * @param $data['type']            interger 详情类型 1：指标详情页 2：诊断详情页
     * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $data['timingType']      interger 配时来源 1：人工 2：反推
     * @return array
     */
    private function getDiagnoseJunctionDetail($data)
    {
        // 诊断问题配置
        $diagnoseKeyConf = $this->config->item('diagnose_key');

        // 组织select 所需字段
        $selectQuota = '';
        foreach ($diagnoseKeyConf as $k=>$v) {
            /*
             * 因为过饱和问题与空放都是根据同一指标计算的，现空放问题的KEY与指标相同
             * 所以只不需要再拼接过饱和问题的select column
             */
            if ($k != 'over_saturation') {
                $selectQuota .= empty($selectQuota) ? $k : ',' . $k;
            }
        }
        $select = "id, junction_id, {$selectQuota}, start_time, end_time, result_comment, movements";

        $res = $this->junction_model->getDiagnoseJunctionDetail($data, $select);
        if (!$res) {
            return [];
        }

        $result = $this->formatJunctionDetailData($res, $data['dates'], 2, $data['timingType']);

        return $result;
    }

    /**
     * 获取指标详情页数据
     * @param $data['task_id']         interger 任务ID
     * @param $data['junction_id']     string   逻辑路口ID
     * @param $data['dates']           array    评估/诊断日期
     * @param $data['search_type']     interger 查询类型 1：按方案查询 0：按时间点查询
     * @param $data['time_point']      string   时间点 当search_type = 0 时 必传
     * @param $data['time_range']      string   方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传
     * @param $data['type']            interger 详情类型 1：指标详情页 2：诊断详情页
     * @param $data['task_time_range'] string   评估/诊断任务开始结束时间 格式："06:00-09:00"
     * @param $data['timingType']      interger 配时来源 1：人工 2：反推
     * @return array
     */
    private function getQuotaJunctionDetail($data)
    {
        // 组织select colum
        $select = 'id, junction_id, start_time, end_time, result_comment, movements';

        // 组织where条件
        $where = 'task_id = ' . (int)$data['task_id'] . ' and junction_id = "' . trim($data['junction_id']) . '"';

        if ((int)$data['search_type'] == 0) {
            $select .= ', time_point';
        }

        $res = $this->junction_model->getQuotaJunctionDetail($data, $select);
        if (!$res) {
            return [];
        }

        $result = $this->formatJunctionDetailData($res, $data['dates'], 1, $data['timingType']);

        return $result;

    }

    /**
     * 格式化路口详情数据
     * @param $data        路口详情数据
     * @param $dates       评估/诊断日期
     * @param $resultType  数据返回类型 1：指标详情页 2：诊断详情页
     * @param $timingType  配时数据来源 1：人工 2：反推
     * @return array
     */
    private function formatJunctionDetailData($data, $dates, $resultType, $timingType)
    {
        if (empty($data) || empty($dates) || (int)$resultType < 1) {
            return [];
        }

        // 因为详情页地图下方列表所有相位都有 置信度字段，而置信度不属于指标，固将此放到扩展指标集合中
        $data['extend_flow_quota']['confidence'] = '置信度';

        // movement级指标数据在数据表中以json格式的存储，需要json_decode
        $data['movements'] = json_decode($data['movements'], true);
        if (empty($data['movements'])) {
            return [];
        }

        // 获取flow_id=>name数组
        $timing_data = [
            'junction_id' => trim($data['junction_id']),
            'dates'       => $dates,
            'time_range'  => $data['start_time'] . '-' . date("H:i", strtotime($data['end_time']) - 60),
            'timingType'  => $timingType
        ];
        $flowIdName = $this->timing_model->getFlowIdToName($timing_data);

        // flow级指标配置
        $flowQuotaKeyConf = $this->config->item('flow_quota_key');

        // 需要返回的全部movements所需字段
        $movementsAll = array_merge($flowQuotaKeyConf, ['movement_id'=>'', 'comment'=>'', 'confidence'=>'']);

        // 置信度配置
        $confidenceConf = $this->config->item('confidence');

        // 匹配相位名称 并按 南左、北直、西左、东直、北左、南直、东左、西直 进行排序(NEMA排序)
        $phase = [
            '南左' => 10,
            '北直' => 20,
            '西左' => 30,
            '东直' => 40,
            '北左' => 50,
            '南直' => 60,
            '东左' => 70,
            '西直' => 80
        ];

        $tempMovements = [];
        foreach ($data['movements'] as $k=>&$v) {
            // 相位名称
            $v['comment'] = $flowIdName[$v['movement_id']] ?? '';

            // 加这个判断是旧的任务结果数据中没有此字段
            if (isset($v['confidence'])) {
                $v['confidence'] = $confidenceConf[$v['confidence']]['name'] ?? '';
            } else {
                $v['confidence'] = '';
            }

            // 组织flow级指标对应相位集合及格式化指标数据
            foreach ($flowQuotaKeyConf as $key=>$val) {
                // 指标名称
                $data['flow_quota_all'][$key]['name'] = $val['name'];
                // 指标单位
                $data['flow_quota_all'][$key]['unit'] = $val['unit'];
                $data['flow_quota_all'][$key]['movements'][$k]['id'] = $v['movement_id'];
                if (isset($v[$key])) {
                    $v[$key] = $val['round']($v[$key]);
                    $data['flow_quota_all'][$key]['movements'][$k]['value'] = $val['round']($v[$key]);
                }
            }
            if (array_key_exists(trim($v['comment']), $phase)
                && !array_key_exists($phase[trim($v['comment'])], $tempMovements)
            ) {
                $tempMovements[$phase[trim($v['comment'])]] = array_intersect_key($v, $movementsAll);
            } else {
                $tempMovements[mt_rand(100, 900) + mt_rand(1, 99)] = array_intersect_key($v, $movementsAll);
            }
        }
        // 因为foreach 使用了引用&$v，所以foreach完成后要销毁$v
        unset($v);

        if (!empty($tempMovements)) {
            unset($data['movements']);
            ksort($tempMovements);
            $data['movements'] = array_values($tempMovements);
        }

        if ($resultType == 2) { // 诊断详情页
            // 组织问题集合
            $diagnoseKeyConf = $this->config->item('diagnose_key');
            foreach ($diagnoseKeyConf as $k=>$v) {
                /*
                 * 因为过饱和问题与空放问题同用一个指标进行计算
                 * 所以过饱和问题要单独处理一下
                 */
                $diagnose = $k;
                if ($k == 'over_saturation') {
                    $diagnose = 'saturation_index';
                }

                // 判断路口的问题
                if ($v['junction_diagnose_formula']($data[$diagnose])) {
                    $data['diagnose_detail'][$k]['name'] = $v['name'];
                    $data['diagnose_detail'][$k]['key'] = $k;

                    // 计算性质程度
                    $data['diagnose_detail'][$k]['nature'] = $v['nature_formula']($data[$diagnose]);

                    // 匹配每个问题指标
                    $tempArr = ['movement_id'=>'logic_flow_id', 'comment'=>'name', 'confidence'=>'置信度'];
                    $tempMerge = array_merge($v['flow_quota'], $tempArr);
                    foreach ($data['movements'] as $kk=>$vv) {
                        $data['diagnose_detail'][$k]['movements'][$kk] = array_intersect_key($vv, $tempMerge);
                        foreach ($v['flow_quota'] as $key=>$val) {
                            if (isset($vv[$key])) {
                                $data['diagnose_detail'][$k]['flow_quota'][$key]['name'] = $val['name'];
                                $data['diagnose_detail'][$k]['flow_quota'][$key]['unit'] = $val['unit'];
                                $data['diagnose_detail'][$k]['flow_quota'][$key]['movements'][$kk]['id']
                                = $vv['movement_id'];
                                $data['diagnose_detail'][$k]['flow_quota'][$key]['movements'][$kk]['value']
                                = $flowQuotaKeyConf[$key]['round']($vv[$key]);
                            }
                        }
                    }
                }
            }
        }

        $resultCommentConf = $this->config->item('result_comment');
        $data['result_comment'] = $resultCommentConf[$data['result_comment']] ?? '';
        return $data;
    }

    /**
     * 将查询出来的评估/诊断数据合并到全城路口模板中
     * @param $allData  全城路口
     * @param $data     任务结果路口
     * @param $mergeKey 合并KEY
     * @return array
     */
    private function mergeAllJunctions($allData, $data, $mergeKey = 'detail')
    {
        if (!is_array($allData) || count($allData) < 1 || !is_array($data) || count($data) < 1) {
            return [];
        }

        // 返回数据
        $resultData = [];
        // 经度
        $countLng = 0;
        // 纬度
        $countLat = 0;

        // 循环全城路口
        foreach ($allData as $k=>$v) {
            // 路口存在于任务结果数据中
            if (isset($data[$v['logic_junction_id']])) {
                // 经纬度相加 用于最后计算中心经纬度用
                $countLng += $v['lng'];
                $countLat += $v['lat'];

                // 组织返回结构 路口ID 路口名称 路口经纬度 路口信息
                $resultData['dataList'][$k]['logic_junction_id'] = $v['logic_junction_id'];
                $resultData['dataList'][$k]['name'] = $v['name'];
                $resultData['dataList'][$k]['lng'] = $v['lng'];
                $resultData['dataList'][$k]['lat'] = $v['lat'];
                // 路口问题信息集合
                $resultData['dataList'][$k][$mergeKey] = $data[$v['logic_junction_id']]['list'];

                // 去除quota的key
                if (isset($data[$v['logic_junction_id']]['info'])) {
                    if (isset($data[$v['logic_junction_id']]['info']['quota'])) {
                        $data[$v['logic_junction_id']]['info']['quota']
                            = array_values($data[$v['logic_junction_id']]['info']['quota']);
                    } else {
                        $data[$v['logic_junction_id']]['info']['quota'] = [];
                    }
                    // 去除question的key并设置默认值
                    if (isset($data[$v['logic_junction_id']]['info']['question'])) {
                        $data[$v['logic_junction_id']]['info']['question']
                            = array_values($data[$v['logic_junction_id']]['info']['question']);
                    } else {
                        $data[$v['logic_junction_id']]['info']['question'] = ['无'];
                    }

                    $resultData['dataList'][$k]['info'] = $data[$v['logic_junction_id']]['info'];
                }
            }
        }

        // 任务结果路口总数
        $count = !empty($data['junctionTotal']) ? $data['junctionTotal'] : 0;

        // 全城路口总数
        $qcount = 0;

        if (!empty($resultData['dataList'])) {
            // 统计全城路口总数
            $qcount = count($resultData['dataList']);
            // 去除KEY
            $resultData['dataList'] = array_values($resultData['dataList']);
        }

        if ($count >= 1 || $qcount >= 1) {
            $diagnoseKeyConf = $this->config->item('diagnose_key');
            $junctionQuotaKeyConf = $this->config->item('junction_quota_key');

            // 统计指标（平均延误、平均速度）平均值
            if (isset($data['quotaCount'])) {
                foreach ($data['quotaCount'] as $k=>$v) {
                    $resultData['quotaCount'][$k]['name'] = $junctionQuotaKeyConf[$k]['name'];
                    $resultData['quotaCount'][$k]['value'] = round(($v / $count), 2);
                    $resultData['quotaCount'][$k]['unit'] = $junctionQuotaKeyConf[$k]['unit'];
                }
            }

            // 计算地图中心坐标
            $centerLng = round($countLng / $qcount, 6);
            $centerLat = round($countLat / $qcount, 6);

            // 柱状图
            if (!empty($data['count']) && $count >= 1) {
                foreach ($data['count'] as $k=>$v) {
                    // 此问题的路口个数
                    $resultData['count'][$k]['num'] = $v;
                    // 问题中文名称
                    $resultData['count'][$k]['name'] = $diagnoseKeyConf[$k]['name'];
                    // 此问题占所有问题的百分比
                    $percent = round(($v / $count) * 100, 2);
                    $resultData['count'][$k]['percent'] = $percent . '%';
                    // 对应不占百分比
                    $resultData['count'][$k]['other'] = (100 - $percent) . '%';
                }
            }
        }

        // 去除quotaCount的key
        if (isset($resultData['quotaCount'])) {
            $resultData['quotaCount'] = array_values($resultData['quotaCount']);
        }

        $resultData['junctionTotal'] = intval($count);

        // 中心坐标
        $resultData['center']['lng'] = $centerLng;
        $resultData['center']['lat'] = $centerLat;

        return $resultData;
    }

    /**
     * 组织select 字段
     */
    private function selectColumns($key)
    {
        $select = '';
        if (is_string($key)) { // 评估，单选
            if (array_key_exists($key, $this->config->item('junction_quota_key'))) {
                $select = $key;
            }
        }
        if (is_array($key)) { // 诊断问题， 多选
            $diagnoseKeyConf = $this->config->item('diagnose_key');
            foreach ($key as $v) {
                if (array_key_exists($v, $diagnoseKeyConf)) {
                    $select .= empty($select) ? $v : ',' . $v;
                }
            }
        }

        return $select;
    }

}