<?php
/********************************************
# desc:    报警分析数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-11-19
********************************************/

use \Services\HelperService;

/**
 * Class Alarmanalysis_model
 * @property \Redis_model $redis_model
 */
class Alarmanalysis_model extends CI_Model
{
    protected $helperService;
    public function __construct()
    {
        parent::__construct();

        // load config
        $this->load->config('nconf');

        // load model
        $this->load->model('redis_model');
        $this->helperService = new HelperService();
    }

    /**
     * 报警es查询接口
     * @param $body json 查询DSL
     * @return array
     */
    public function search($body)
    {
        $hosts = $this->config->item('alarm_es_interface');
        $index = $this->config->item('alarm_es_index');
        $queryUrl = sprintf('http://%s/%s/type/_search?%s',$hosts[0],$index['flow'],$index['flow']);
        $response = httpPOST($queryUrl, json_decode($body,true), 0, 'json');
        if (!$response) {
            return [];
        }
        return json_decode($response,true);
    }

    /**
     * 报警相位表ES查询接口
     * @param $body json 查询DSL
     * @return array
     */
    public function searchFlowTable($body)
    {
        return $this->search($body);
    }

    public function getJunctionAlarmByDates($cityID, $dates, $junctionID){
        //date对象补充
        $dateArr = [];
        foreach ($dates as $date) {
            $dateArr[] = '{"match":{"date":{"query":"'.$date.'","type":"phrase"}}}';
        }

        //请求报警服务
        $json = '{"from":0,"size":10000,"query":{"bool":{"must":{"bool":{"must":[{"match":{"city_id":{"query":'.$cityID.',"type":"phrase"}}},{"match":{"logic_junction_id":{"query":"'.$junctionID.'","type":"phrase"}}},{"bool":{"should":['.implode(",",$dateArr).']}}]}}}},"sort":[{"updated_at":{"order":"desc"}}]}';
        $esRes = $this->searchFlowTable($json);

        if (!isset($esRes['hits']['hits'])) {
            com_log_warning('getRealTimeAlarmsInfoFromEs_error', 0, $esRes, compact("json","esRes"));
            throw new \Exception("获取实时报警数据异常");
        }
        if (empty($esRes['hits']['hits'])) {
            return [];
        }

        foreach ($esRes['hits']['hits'] as $k=>$v) {
            $res[$k] = [
                'logic_junction_id' => $v['_source']['logic_junction_id'],
                'logic_flow_id'     => $v['_source']['logic_flow_id'],
                'start_time'        => $v['_source']['start_time'],
                'last_time'         => $v['_source']['last_time'],
                'type'              => $v['_source']['type'],
                'junction_type'              => $v['_source']['junction_type'],
            ];
        }
        return $res;
    }

    /**
     * 获取实时报警数据
     * @param $cityId int 城市ID
     * @param $date   string 日期 yyyy-mm-dd
     * @param $hour   string 时间 HH:ii:ss
     * @return array
     */
    public function getRealTimeAlarmsInfoFromEs($cityId, $date, $hour = '')
    {
        $lastTime  = date('Y-m-d') . ' ' . $hour;

        // 组织ES接口所需DSL
        $json = '{"from":0,"size":1000,"query":{"bool":{"must":{"bool":{"must":[';

        // where city_id
        $json .= '{"match":{"city_id":{"query":' . $cityId . ',"type":"phrase"}}}';

        // where last_time
        $json .= ',{"range":{"last_time":{"from":"'.$lastTime.'","to":null,"include_lower":true,"include_upper":true}}}';

        $json .= ']}}}}}';

        $esRes = $this->searchFlowTable($json);

        if (!isset($esRes['hits']['hits'])) {
            com_log_warning('getRealTimeAlarmsInfoFromEs_error', 0, $esRes, compact("json","esRes"));
            throw new \Exception("获取实时报警数据异常");
        }
        if (empty($esRes['hits']['hits'])) {
            return [];
        }

        foreach ($esRes['hits']['hits'] as $k=>$v) {
            $res[$k] = [
                'logic_junction_id' => $v['_source']['logic_junction_id'],
                'logic_flow_id'     => $v['_source']['logic_flow_id'],
                'start_time'        => $v['_source']['start_time'],
                'last_time'         => $v['_source']['last_time'],
                'type'              => $v['_source']['type'],
                'junction_type'              => $v['_source']['junction_type'],
            ];
        }
        return $res;
    }

    /**
     * 获取实时报警数据
     * @param $cityId int 城市ID
     * @param $date   string 日期 yyyy-mm-dd
     * @param $hour   string 时间 HH:ii:ss
     * @return array
     */
    public function getRealTimeAlarmsInfo($cityId, $date, $hour = '')
    {
        if (empty($hour)) {
            $hour = $this->helperService->getLastestHour($cityId);
        }
        $res = $this->redis_model->getRealtimeAlarmListByDateHour($cityId,$date,$hour);
        return $res;
    }
}
