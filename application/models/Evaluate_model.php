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

        $this->db->select("`logic_junction_id`, SUM({$data['quota_key']}) / count(logic_flow_id) as quota_value");
        $this->db->from($table);
        $this->db->where($where);
        $this->db->group_by('logic_junction_id');
        $this->db->order_by('(SUM(' . $data['quota_key'] . ') / count(logic_flow_id)) DESC');
        $this->db->limit(100);
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

        $result['dataList'] = array_map(function($val) use($junctionIdName) {
            return [
                'logic_junction_id' => $val['logic_junction_id'],
                'junction_name'     => $junctionIdName[$val['logic_junction_id']] ?? '',
                'quota_value'       => $val['quota_value'],
            ];
        }, $data);

        // 返回数据：指标信息
        $result['quota_info'] = [
            'name' => $quotaConf[$quotaKey]['name'],
            'key'  => $quotaKey,
            'unit' => $quotaConf[$quotaKey]['unit'],
        ];

        return $result;
    }

    /**
     * 获取指标趋势
     * @param $data['city_id']     interger Y 城市ID
     * @param $data['junction_id'] string   Y 路口ID
     * @param $data['quota_key']   string   Y 指标KEY
     * @param $data['flow_id']     string   Y 相位ID
     * @param $data['date']        string   Y 日期 格式：Y-m-d 默认当前日期
     * @param $data['time_point']  string   Y 时间 格式：H:i:s 默认当前时间
     * @return array
     */
    public function getQuotaTrend($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $table = $this->realtimetb . $data['city_id'];
        $where = 'logic_junction_id = "' . $data['junction_id'] . '"';
        $where .= ' and logic_flow_id = "' . $data['flow_id'] . '"';
        $where .= ' and day(`updated_at`) = day("' . $data['date'] . '")';
        $this->db->select("hour, {$data['quota_key']}");
        $this->db->from($table);
        $this->db->where($where);
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatQuotaTrendData($res, $data['quota_key']);

        return $result;
    }

    /**
     * 格式化指标趋势数据
     * @param $data     指标趋势数据
     * @param $quotaKey 查询的指标KEY
     * @return array
     */
    public function formatQuotaTrendData($data, $quotaKey)
    {
        $result = [];

        // 指标配置
        $quotaConf = $this->config->item('real_time_quota');

        $result['dataList'] = array_map(function($val) use($quotaKey) {
            return [
                // 指标值 Y轴
                $val[$quotaKey],
                // 时间点 X轴
                $val['hour']
            ];
        }, $data);


        // 返回数据：指标信息
        $result['quota_info'] = [
            'name' => $quotaConf[$quotaKey]['name'],
            'key'  => $quotaKey,
            'unit' => $quotaConf[$quotaKey]['unit'],
        ];

        return $result;
    }
}
