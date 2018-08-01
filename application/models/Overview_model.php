<?php
/********************************************
 * # desc:    概览数据模型
 * # author:  ningxiangbing@didichuxing.com
 * # date:    2018-07-25
 ********************************************/

class Overview_model extends CI_Model
{
    private $tb = '';

    public function __construct()
    {
        parent::__construct();

        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $this->config->load('realtime_conf');
        $this->load->model('waymap_model');
    }

    public function junctionsList($data)
    {
        $table = 'real_time_' . $data['city_id'];

        $result = $this->db->select('*')
            ->from($table)
            ->where('hour', $data['time_point'])
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->get()->result_array();

        //获取全部路口 ID
        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        //获取路口信息的自定义返回格式
        $junctionsInfo = $this->waymap_model->getJunctionInfo($ids, [
            'key' => 'logic_junction_id',
            'value' => ['name', 'lng', 'lat']
        ]);

        //获取全部路口的全部方向的信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($ids);

        $result = array_map(function ($item) use ($junctionsInfo, $flowsInfo) {
            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'quota' => $this->createQuotaInfo($item),
                'alarm_info' => $this->getAlarmInfo($item, $flowsInfo),
            ];
        }, $result);

        $result = $this->mergeJunctionResult($result, $junctionsInfo);

        return $result;
    }

    public function operationCondition($data)
    {

        $table = 'real_time_' . $data['city_id'];

        $result = $this->db->select('left(hour, 5) as hour, avg(stop_delay) as avg_stop_delay')
            ->from($table)
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->group_by('left(hour, 5)')
            ->get()->result_array();

        $result = array_map(function ($v) {
            return [
                round($v['avg_stop_delay'], 2),
                $v['hour']
            ];
        }, $result);

        return ['dataList' => $result];
    }

    public function junctionSurvey($data)
    {
        $data = $this->junctionsList($data);

        $result = [];

        $result['junction_total'] = count($data);
        $result['alarm_total'] = 0;
        $result['congestion_total'] = 0;

        foreach ($data as $datum) {
            $result['alarm_total'] += $datum['alarm_info']['is_alarm'];
            $result['congestion_total'] += (int)($datum['junction_status']['key'] == 2);
        }

        return $result;

    }

    /**
     * 构建指标信息
     *
     * @param $item
     * @return array
     */
    private function createQuotaInfo($item)
    {
        return [
            'stop_delay_weight' => $item['stop_delay'] * $item['traj_count'],
            'stop_time_cycle' => $item['stop_time_cycle'],
            'traj_count' => $item['traj_count']
        ];
    }

    /**
     * 生成报警信息
     *
     * @param $item
     * @param $city_id
     * @param $flowsInfo
     * @return array|string
     */
    private function getAlarmInfo($item, $flowsInfo)
    {
        $alarmCategory = $this->config->item('alarm_category');

        if (is_null($alarmCategory)) {
            return [];
        }

        if ($alarmCategory[1]['formula']($item['spillover_rate'])) {
            return [$flowsInfo[$item['logic_junction_id']]['logic_flow_id']] . '-溢流';
        } elseif ($alarmCategory[2]['formula']($item)) {
            return [$flowsInfo[$item['logic_junction_id']]['logic_flow_id']] . '-过饱和';
        } else {
            return [];
        }
    }

    /**
     * 合并结果数组，返回数据粒度为 junction
     *
     * @param $result
     * @return array
     */
    private function mergeJunctionResult($result, $junctionsInfo)
    {
        $temp = [];

        foreach ($result as $item) {
            $temp[$item['logic_junction_id']] = isset($temp[$item['logic_junction_id']]) ?
                $this->mergeJunctionResultItem($temp[$item['logic_junction_id']], $item) :
                $item;
        }

        foreach ($temp as &$item) {
            $junctionInfo = $junctionsInfo[$item['logic_junction_id']];

            $item['quota'] = [
                'stop_delay' => [
                    'name' => '平均延误',
                    'value' => round($item['quota']['stop_delay_weight'] / $item['quota']['traj_count'], 2),
                    'unit' => '秒',
                ],
                'stop_time_cycle' => [
                    'name' => '最大停车时间',
                    'value' => round($item['quota']['stop_time_cycle'], 2),
                    'unit' => '秒',
                ]
            ];

            $item['alarm_info'] = [
                'is_alarm' => (int)!empty($item['alarm_info']),
                'commment' => $item['alarm_info']
            ];

            $item['junction_name'] = $junctionInfo['name'] ?? '';
            $item['lng']           = $junctionInfo['lng'] ?? '';
            $item['lat']           = $junctionInfo['lat'] ?? '';

            $item['junction_status'] = $this->getJunctionStatus($item);
        }

        return ['dataList' => array_values($temp)];
    }

    /**
     * 获取当前路口的状态
     *
     * @param $item
     * @return array
     */
    private function getJunctionStatus($item)
    {
        $junction_status = $this->config->item('junction_status') ?? null;

        if (is_null($junction_status)) {
            return [];
        }

        if ($junction_status[1]['formula']($item['stop_delay']['value'])) {
            return [
                'name' => $junction_status[1]['name'],
                'key' => $junction_status[1]['key']
            ];
        } elseif ($junction_status[2]['formula']($item['stop_delay']['value'])) {
            return [
                'name' => $junction_status[2]['name'],
                'key' => $junction_status[2]['key']
            ];
        } elseif ($junction_status[3]['formula']($item['stop_delay']['value'])) {
            return [
                'name' => $junction_status[3]['name'],
                'key' => $junction_status[3]['key']
            ];
        } else {
            return [];
        }
    }

    /**
     * 合并数组成员
     *
     * @param $target
     * @param $item
     * @return mixed
     */
    private function mergeJunctionResultItem($target, $item)
    {
        //合并属性 停车延误加权求和，停车时间求最大，权值求和
        $target['quota']['stop_delay_weight'] += $item['quota']['stop_delay_weight'];
        $target['quota']['stop_time_cycle']   = max($target['quota']['stop_time_cycle'], $item['quota']['stop_time_cycle']);
        $target['quota']['traj_count']        += $item['quota']['traj_count'];

        //合并报警信息
        $target['alarm_info'] = array_merge($target['alarm_info'], $item['alarm_info']) ?? [];

        return $target;
    }
}