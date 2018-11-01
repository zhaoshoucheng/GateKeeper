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
     * 将查询出来的评估/诊断数据合并到全城路口模板中
     * $allData  全城路口
     * $data     任务结果路口
     * $mergeKey 合并KEY
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

}