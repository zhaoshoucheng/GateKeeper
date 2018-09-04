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

        $dataByFlow = Collection::make($result)->groupBy(['logic_flow_id', 'hour'], function ($arr) use ($key) {
            return reset($arr)[$key] ?? '';
        });

        $dataByHour = Collection::make($result)->groupBy(['hour', 'logic_flow_id'], function ($arr) use ($key) {
            return reset($arr)[$key] ?? '';
        });

        //求出每个方向的全天均值中最大的方向 ID //如果有多个最大值，则取平均求最大
        $maxFlowIds = $dataByHour->reduce(function ($carry, $item){
            return Collection::make($item)->keysOfMaxValue()->reduce(function (Collection $ca, $it) {
                $ca->increment($it); return $ca;
            }, $carry);
        }, Collection::make([]))->keysOfMaxValue()->reduce(function (Collection $carry, $item) use ($dataByHour) {
            return $carry->set($item, $dataByHour->avg($item));
        }, Collection::make([]))->keysOfMaxValue();

        //找出均值最大的方向的最大值最长持续时间区域
        $base_time_box = $maxFlowIds->reduce(function (Collection $carry, $id) use ($dataByFlow, $dataByHour) {
            $maxFlow = Collection::make($dataByFlow->get($id));
            $maxFlowFirstKey = $maxFlow->keys()->first(null, '');
            $maxArray = $nowArray = [
                'start_time' => $maxFlowFirstKey,
                'end_time' => $maxFlowFirstKey,
                'length' => 0,
            ];
            $maxFlow->each(function ($quota, $hour) use ($dataByHour, &$nowArray, &$maxArray) {
                $max = max($dataByHour->get($hour));
                if($quota >= $max && $quota > 0) {
                    $nowArray['end_time'] = $hour;
                    if($nowArray['start_time'] == '') $nowArray['start_time'] = $hour;
                    $nowArray['length']++;
                } else {
                    if($nowArray['length'] > $maxArray['length']) $maxArray = $nowArray;
                    $nowArray = [ 'start_time' => '', 'end_time' => '', 'length' => 0, ];
                }
            });
            if($nowArray['length'] < $maxArray['length']) $nowArray = $maxArray;
            if($carry->isEmpty() || $carry->get('0.length', 0) == $nowArray['length']) {
                return $carry->set($id, $nowArray);
            } elseif($carry->get('0.length', 0) < $nowArray['length']) {
                return Collection::make([$id => $nowArray]);
            } else {
                return $carry;
            }
        }, Collection::make([]));

        //如果某个时间点某个方向没有数据，则设为 null
        $hours = Collection::make($hours);
        $dataByFlow = $dataByFlow->map(function ($flow) use ($hours) {
            return $hours->reduce(function ($carry, $item) {
                $carry[$item] = $carry[$item] ?? null; return $carry;
            }, $flow);
        });

        $dataByFlow->each(function ($value, $ke) use (&$base, &$flow_info, &$maxFlowIds, $flowsName, $key) {
            $base[$ke] = [];
            foreach ($value as $k => $v) { $base[$ke][] = [$v === null ? null : $this->quotas[$key]['round']($v), $k]; }
            $flow_info[$ke] = [ 'name' => $flowsName[$ke] ?? '', 'highlight' => (int)($maxFlowIds->inArray($ke))];
        });

        $base_time_box->each(function ($v, $k) use (&$describes, &$summarys, $key, $junctionInfo) {
            $describes[] = $this->quotas[$key]['describe']([
                $junctionInfo['junction']['name'] ?? '',
                $junctionInfo['flows'][$k] ?? '',
                $v['start_time'],
                $v['end_time']]);
            $summarys[] = $this->quotas[$key]['summary']([
                $v['start_time'],
                $v['end_time'],
                $junctionInfo['flows'][$k] ?? '']);
        });

        $base_time_box = $base_time_box->all();
        $describe_info = implode("\n", $describes);
        $summary_info = implode("\n", $summarys);

        return compact('base', 'flow_info', 'base_time_box', 'describe_info', 'summary_info');
    }
}
