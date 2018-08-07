<?php
/********************************************
# desc:    评估数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Evaluate_model extends CI_Model
{
    private $offlintb = 'offline_';
    private $realtimetb = 'real_time_';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('realtime_conf');
        $this->load->model('waymap_model');
    }

    /**
     * 获取城市路口列表
     *
     * @param $data['city_id'] 城市ID
     * @return array
     */
    public function getCityJunctionList($data)
    {
        $result = $this->waymap_model->getAllCityJunctions($data['city_id']);

        $result = array_map(function ($junction) {
            return [
                'logic_junction_id' => $junction['logic_junction_id'],
                'junction_name' => $junction['name'],
                'lng' => $junction['lng'],
                'lat' => $junction['lat']
            ];
        }, $result);


        $lngs = array_column($result, 'lng');
        $lats = array_column($result, 'lat');

        $center['lng'] = count($lngs) == 0 ? 0 : (array_sum($lngs) / count($lngs));
        $center['lat'] = count($lats) == 0 ? 0 : (array_sum($lats) / count($lats));

        return [
            'dataList' => $result,
            'center' => $center
        ];
    }

    /**
     * 获取指标列表
     *
     * @param $data
     * @return array
     */
    public function getQuotaList($data)
    {
        $realTimeQuota = $this->config->item('real_time_quota');

        $realTimeQuota = array_map(function ($key, $value) {
            return [
                'name' => $value['name'],
                'key' => $key,
                'unit' => $value['unit']
            ];
        }, array_keys($realTimeQuota), array_values($realTimeQuota));

        return ['dataList' => array_values($realTimeQuota)];
    }

    /**
     * 获取某个路口的全部 flow(方向)
     *
     * @param $data['city_id'] 城市ID
     * @param $data['junction_id'] 路口ID
     * @return array
     */
    public function getDirectionList($data)
    {
        $result = $this->waymap_model->getFlowsInfo($data['junction_id']);

        $result = $result[$data['junction_id']] ?? [];

        $result = array_map(function ($key, $value) {
            return [ $key, $value ];
        }, array_keys($result), array_values($result));

        return [ 'dataList' => $result ];
    }

    /**
     * 获取路口指标排序列表
     * @param $data['city_id']    Y 城市ID
     * @param $data['quota_key']  Y 指标KEY
     * @param $data['date']       N 日期 默认当前日期
     * @param $data['time_point'] N 时间 默认当前时间
     * @return array
     */
    public function getJunctionQuotaSortList($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $table = $this->realtimetb . $data['city_id'];
        $where = 'hour = (select hour from ' . $table;
        $where .= ' where day(`updated_at`) = day("' . $data['date'] . '") order by hour desc limit 1)';

        $this->db->select("logic_junction_id, logic_flow_id, {$data['quota_key']}");
        $this->db->from($table);
        $this->db->where($where);
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatJunctionQuotaSortListData($res, $data['quota_key']);

        return $result;
    }

    /**
     * 格式化路口指标排序列表数据
     * @param $data     列表数据
     * @param $quotaKey 查询的指标KEY
     * @return array
     */
    private function formatJunctionQuotaSortListData($data, $quotaKey)
    {
        $result = [];

        // 所需查询路口名称的路口ID串
        $junctionIds = implode(',', array_unique(array_column($data, 'logic_junction_id')));

        // 获取路口信息
        $junctionsInfo = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');

        // 临时数组，用于计算所有相位指标平均用
        $tempData = [];
        foreach ($data as $k=>$v) {
            $tempData[$v['logic_junction_id']][$v['logic_flow_id']] = $v[$quotaKey];
            $result['dataList'][$v['logic_junction_id']] = [
                'logic_junction_id' => $v['logic_junction_id'],
                'junction_name'     => $junctionIdName[$v['logic_junction_id']],
            ];
        }
        if (empty($tempData)) {
            return [];
        }

        $sortKeyArr = [];
        foreach ($tempData as $k=>$v) {
            $quotaValue = $quotaConf[$quotaKey]['round'](array_sum($v) / count($v));
            $sortKeyArr[$k] = $quotaValue;
            $result['dataList'][$k]['quota_value'] = $quotaValue;
        }

        // $result['dataList'] 按指标值进行倒序排序
        array_multisort($sortKeyArr, SORT_DESC, SORT_NUMERIC, $result['dataList']);

        // 去除$result['dataList']的KEY
        $result['dataList'] = array_values($result['dataList']);

        // 返回数据：指标信息
        $result['quota_info'] = [
            'name' => $quotaConf[$quotaKey]['name'],
            'key'  => $quotaKey,
            'unit' => $quotaConf[$quotaKey]['unit'],
        ];

        return $result;
    }
}
