<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/22
 * Time: 下午6:21
 */

class FlowDurationV6_model extends CI_Model
{
    protected $tb = 'flow_duration_v6_';
    protected $engine = '';

    /**
     * @var CI_DB_query_builder
     */
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->load->config("nconf");
        $this->engine = $this->config->item('data_engine');
        $this->db = $this->load->database('default', true);
    }

    /**
     * @param        $cityId
     * @param        $logicJunctionId
     * @param        $logicFlowId
     * @param        $dates
     * @param        $groupBy
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getQuotaEvaluateCompare($cityId, $logicJunctionId, $logicFlowId, $dates, $groupBy, $quotaKey = '', $select = '*', $type = 'quota')
    {
        $url = $this->config->item('data_service_interface');
        $dates = array_values($dates);
        $res = [];
        if ($type == 'detail') {
            $data = [
                'city_id'           => (int)$cityId,
                'select_column'     => $select,
                'logic_junction_id' => $logicJunctionId,
                'logic_flow_id'     => $logicFlowId,
                'date'              => $dates,
                'engine'            => $this->engine,
            ];
            $res = httpPOST($url . '/getQuotaEvaluateDetail', $data, 0, 'json');
        } else {
            $data = [
                'city_id'           => (int)$cityId,
                'select_column'     => $select,
                'quota'             => $quotaKey,
                'logic_junction_id' => $logicJunctionId,
                'group_by'          => 'logic_junction_id, hour, date',
                'date'              => $dates,
                'engine'            => $this->engine,
            ];
            $res =  httpPOST($url . '/getQuotaEvalute', $data, 0, 'json');
        }
        if (!$res) {
            return [];
        }

        $res = json_decode($res, true);
        if ($res['errno'] != 0) {
            return [];
        }
        return $res['data'];
    }

    /**
     *
     * @param        $cityId
     * @param        $logicJunctionId
     * @param        $dates
     * @param        $hours
     * @param string $select
     *
     * @return array
     * @throws Exception
     */
    public function getQuotaByJunction($cityId, $logicJunctionId, $dates, $hours, $quotaKey = '', $select = '*')
    {
        $data = [
            'city_id'           => (int)$cityId,
            'select_column'     => $select,
            'quota'             => $quotaKey,
            'logic_junction_id' => $logicJunctionId,
            'group_by'          => 'logic_flow_id, hour',
            'order_by'          => 'logic_flow_id, hour',
            'traj_count_value'  => 0,
            'date'              => $dates,
            'hour'              => $hours,
            'engine'            => $this->engine,
        ];
        $url = $this->config->item('data_service_interface');

        $res = httpPOST($url . '/getFlowQuota', $data, 0, 'json');
        if (!$res) {
            return [];
        }

        $res = json_decode($res, true);
        if ($res['errno'] != 0) {
            return [];
        }
        return $res['data'];

    }


    /**
     * 获取单点路口优化对比
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @param $data['city_id']           int      Y 城市ID
     * @param $data['date']              array    Y 所需要查询的日期
     * @param $data['hour']              array    Y 所需要查询的时间
     * @param $data['quota_key']         string   Y 指标key
     * @return array
     */
    public function getQuotaInfoByDate($data)
    {
        $data = [
            'city_id'           => (int)$data['city_id'],
            'quota'             => $data['quota_key'],
            'logic_junction_id' => $data['logic_junction_id'],
            'traj_count_value'  => 10,
            'date'              => $data['date'],
            'hour'              => $data['hour'],
            'engine'            => $this->engine,
        ];

        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/getJunctionOptCompare', $data, 0, 'json');
        if (!$res) {
            return [];
        }

        $res = json_decode($res, true);
        if ($res['errno'] != 0) {
            return [];
        }
        return $res['data'];

    }

    /**
     * 多路口指标计算
     * @param        $dates
     * @param        $hours
     * @param        $junctionIds
     * @param        $flowIds
     * @param        $cityId
     * @param string $select
     *
     * @return array
     */
    public function getJunctionByCityId($dates, $flowIds, $cityId, $quotaKey, $flowLength = [], $select = '*')
    {
        $data = [
            'city_id'        => intval($cityId),
            'quota'          => $quotaKey,
            'date'           => $dates,
            'logic_flow_id'  => array_values(array_filter($flowIds)),
            'time_case_when' => $flowLength,
            'engine'         => $this->engine
        ];

        $url = $this->config->item('data_service_interface');
        $res = httpPOST($url . '/getJunctionsQuotaEvaluate', $data, 0, 'json');
        if (!$res) {
            return [];
        }

        $res = json_decode($res, true);
        if ($res['errno'] != 0) {
            return [];
        }
        return $res['data'];

    }

}
