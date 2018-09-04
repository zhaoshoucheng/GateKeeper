<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/21
 * Time: 上午10:06
 */


class Junctionreport_model extends CI_Model
{
    protected $tb = 'flow_duration_v6_';

    protected $quotas;

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $this->load->model('waymap_model');
        $this->load->config('report_conf');

        $this->quotas = $this->config->item('quotas');
    }

    /**
     * 单点路口分析–数据查询
     *
     * @param $data
     * @param $dates
     * @param $hours
     * @return array
     */
    public function queryQuotaInfo($data, $dates, $hours)
    {

        $result = $this->db->select('sum(' . $data['quota_key'] . ' * traj_count) / sum(traj_count) as ' . $data['quota_key'] . ', hour, logic_flow_id')
            ->from($this->tb . $data['city_id'])
            ->where('logic_junction_id', $data['logic_junction_id'])
            ->where_in('date', $dates)
            ->where_in('hour', $hours)
            ->where('traj_count >=', 10)
            ->group_by(['logic_flow_id', 'hour'])
            ->order_by('logic_flow_id, hour')
            ->get()->result_array();

        return $this->formatQueryQuotaInfoData($data, $result);

    }

    /**
     * 对数据通过一系列的逻辑处理进行格式化
     * @param $data
     * @param $result
     * @return array
     */
    private function formatQueryQuotaInfoData($data, $result)
    {
        if(empty($result)) return [];

        $junctionInfo = $this->getJunctionInfo($data);

        $hours = $this->getHours($data);

        $pretreatResultData = $this->getPretreatResultData($data, $result, $junctionInfo, $hours);

        return [
            'info' => [
                'junction_name' => $junctionInfo['junction']['name'] ?? '',
                'junction_lng' => $junctionInfo['junction']['lng'] ?? '',
                'junction_lat' => $junctionInfo['junction']['lat'] ?? '',
                'quota_name' => $this->quotas[$data['quota_key']]['name'],
                'quota_unit' => $this->quotas[$data['quota_key']]['unit'],
                'quota_desc' => $this->quotas[$data['quota_key']]['desc'][$data['type']],
                'summary_info' => $pretreatResultData['summary_info'],
                'describe_info' => $pretreatResultData['describe_info'],
                'flow_info' => $pretreatResultData['flow_info'],
                'base_time_box' => $pretreatResultData['base_time_box']
            ],
            'base' => $pretreatResultData['base']
        ];
    }

    /**
     * 获取指定时间段内的半小时划分集合
     * @param $data
     * @return array
     */
    private function getHours($data)
    {
        $start = strtotime($data['schedule_start']);
        $end = strtotime($data['schedule_end']);

        $results = [];

        $time = $start;
        while($time <= $end) {
            $results[] = date('H:i', $time);
            $time += (30 * 60);
        }

        return $results;
    }

    /**
     * 获取指定时间段内指定星期的日期集合
     * @param $data
     * @return array
     */
    private function getDates($data)
    {
        $start = strtotime($data['evaluate_start_date']);
        $end = strtotime($data['evaluate_end_date']);
        $weeks = $data['week'];

        $results = [];

        $time = $start;
        while($time <= $end) {
            if(in_array(date('w', $time), $weeks)) {
                $results[] = date('Y-m-d', $time);
            }
            $time += (60 * 60 * 24);
        }

        return $results;
    }

    /**
     * 获取指定路口信息
     * @param array $data
     * @return mixed
     */
    private function getJunctionInfo($data)
    {
        $junctionId = $data['logic_junction_id'];

        $junctionInfo = $this->waymap_model->getJunctionInfo($junctionId, [ 'key'=>'logic_junction_id', 'value' => ['name', 'lat', 'lng'] ]);

        $flowsInfo = $this->waymap_model->getFlowsInfo($junctionId);

        return [
            'junction' => $junctionInfo[$junctionId] ?? [],
            'flows' => $flowsInfo[$junctionId] ?? []
        ];
    }

    /**
     * 对数据进行预处理，同时获取可以初步获取的信息
     * @param $data
     * @param $result
     * @param $junctionInfo
     * @return array
     */
    private function getPretreatResultData($data, &$result, $junctionInfo, $hours)
    {
        $key = $data['quota_key'];
        $flowsName = $junctionInfo['flows'];

        //构建二维数据表以映射折线图，同时创建以时间为依据分组的数据
        $dataByFlow = [];
        $dataByHour = [];

        $dataByFlow = Collection::make($data)
            ->groupBy(['logic_flow_id', 'hour'], function ($arr) use ($key) {
                return reset($arr)[$key] ?? '';
            })->all();

        $dataByHour = Collection::make($data)
            ->groupBy(['hour', 'logic_flow_id'], function ($arr) use ($key) {
                return reset($arr)[$key] ?? '';
            })->all();


        foreach ($result as $item) {
            //Flow
            $dataByFlow[$item['logic_flow_id']] = $dataByFlow[$item['logic_flow_id']] ?? [];
            $dataByFlow[$item['logic_flow_id']][$item['hour']] = $item[$key];
            //Hour
            $dataByHour[$item['hour']] = $dataByHour[$item['hour']] ?? [];
            $dataByHour[$item['hour']][$item['logic_flow_id']] = $item[$key];
        }

        //求出每个方向的全天均值中最大的方向 ID
        $flowsIdArray = [];
        foreach ($dataByHour as $hour => $quotas) {
            $flowsId = array_keys($quotas, max($quotas));
            foreach ($flowsId as $id) {
                $flowsIdArray[$id] = ($flowsIdArray[$id] ?? 0) + 1;
            }
        }
        $maxFlowIds = array_keys($flowsIdArray, max($flowsIdArray));

        //如果有多个最大值，则取平均求最大
        $avg = [];
        foreach ($maxFlowIds as $id) {
            $avg[$id] = array_sum($dataByFlow[$id]) / count($dataByFlow[$id]);
        }
        $maxFlowIds = array_keys($avg, max($avg));

        //找出均值最大的方向的最大值最长持续时间区域
        $base_time_box = [];
        foreach ($maxFlowIds as $maxFlowId) {
            $firstHour = array_keys($dataByFlow[$maxFlowId])[0] ?? '';
            $start_time = $end_time = $firstHour;
            $lastLength = 0;
            $lastStartTime = $lastEndTime = $firstHour;
            $length = 0;

            foreach ($dataByFlow[$maxFlowId] as $hour => $quota) {
                $max = max($dataByHour[$hour]);
                if($quota >= $max && $quota > 0) {
                    $end_time = $hour;
                    if($start_time == '') $start_time = $hour;
                    $length++;
                } else {
                    if($length > $lastLength) {
                        $lastStartTime = $start_time;
                        $lastEndTime = $end_time;
                        $lastLength = $length;
                    }
                    $start_time = $end_time = '';
                    $length = 0;
                }
            }

            if($length < $lastLength) {
                $start_time = $lastStartTime;
                $end_time = $lastEndTime;
                $length = $lastLength;
            }

            if(empty($base_time_box)) {
                $base_time_box[$maxFlowId] = compact('start_time', 'end_time', 'length');
            } elseif(reset($base_time_box)['length'] == $length) {
                $base_time_box[$maxFlowId] = compact('start_time', 'end_time', 'length');
            } elseif(reset($base_time_box)['length'] < $length) {
                $base_time_box = [$maxFlowId => compact('start_time', 'end_time', 'length')];
            }
        }

        //如果某个时间点某个方向没有数据，则设为 null
        foreach ($dataByFlow as $flowId => $flow) {
            foreach ($hours as $hour) {
                $dataByFlow[$flowId][$hour] = $dataByFlow[$flowId][$hour] ?? null;
            }
            ksort($dataByFlow[$flowId]);
        }

        //格式化二维数据表 - 生成 两类数据 （flow_info | base）
        $base = $flow_info = [];
        foreach ($dataByFlow as $flowId => $flow) {
            //base
            $base[$flowId] = [];
            foreach ($flow as $k => $v) { $base[$flowId][] = [$v === null ? null : $this->quotas[$key]['round']($v), $k]; }
            //flow_info
            $flow_info[$flowId] = [ 'name' => $flowsName[$flowId] ?? '', 'highlight' => (int)(in_array($flowId, $maxFlowIds) )];
        }

        $describe_info = $this->quotas[$key]['describe']([
            $junctionInfo['junction']['name'] ?? '',
            $junctionInfo['flows'][$maxFlowId] ?? '',
            $start_time,
            $end_time]);

        $summary_info = $this->quotas[$key]['summary']([
            $start_time,
            $end_time,
            $junctionInfo['flows'][$maxFlowId] ?? '']);

        return compact('base', 'flow_info', 'base_time_box', 'describe_info', 'summary_info');
    }
}
