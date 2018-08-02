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

        $result = $this->getJunctionListResult($result);

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

        $realTimeQuota = $this->config->item('real_time_quota');

        $result       = array_map(function ($v) use ($realTimeQuota) {
            return [
                $realTimeQuota['stop_delay']['round']($v['avg_stop_delay']),
                $v['hour']
            ];
        }, $result);
        $allStopDelay = array_column($result, 0);
        $info         = [
            'value' => $realTimeQuota['stop_delay']['round'](array_sum($allStopDelay) / count($allStopDelay)),
            'quota_unit' => $realTimeQuota['stop_delay']['unit']
        ];

        return [
            'dataList' => $result,
            'info' => $info
        ];
    }

    public function junctionSurvey($data)
    {
        $data = $this->junctionsList($data);

        $result = [];

        $result['junction_total']   = count($data);
        $result['alarm_total']      = 0;
        $result['congestion_total'] = 0;

        foreach ($data as $datum) {
            $result['alarm_total']      += $datum['alarm_info']['is_alarm'];
            $result['congestion_total'] += (int)($datum['junction_status']['key'] == 2);
        }

        return $result;

    }

    /**
     * 处理从数据库中取出的原始数据并返回
     *
     * @param $result
     * @return array
     */
    private function getJunctionListResult($result)
    {
        //获取全部路口 ID
        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        //获取路口信息的自定义返回格式
        $junctionsInfo = $this->waymap_model->getJunctionInfo($ids, ['key' => 'logic_junction_id', 'value' => ['name', 'lng', 'lat']]);

        //获取全部路口的全部方向的信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($ids);

        //数组初步处理，去除无用数据
        $result = array_map(function ($item) use ($junctionsInfo, $flowsInfo) {
            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'quota' => $this->getRawQuotaInfo($item),
                'alarm_info' => $this->getRawAlarmInfo($item, $flowsInfo),
            ];
        }, $result);

        //数组按照 logic_junction_id 进行合并
        $temp = [];
        foreach($result as $item) {
            $temp[$item['logic_junction_id']] = isset($temp[$item['logic_junction_id']]) ?
                $this->mergeFlowInfo($temp[$item['logic_junction_id']], $item) :
                $item;
        };

        //处理数据内容格式
        $temp = array_map(function ($item) use ($junctionsInfo) {
            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionsInfo[$item['logic_junction_id']]['name'] ?? '',
                'lng' => $junctionsInfo[$item['logic_junction_id']]['lng'] ?? '',
                'lat' => $junctionsInfo[$item['logic_junction_id']]['lat'] ?? '',
                'quota' => ($quota = $this->getFinalQuotaInfo($item)),
                'alarm_info' => $this->getFinalAlarmInfo($item),
                'junction_status' => $this->getJunctionStatus($quota),
            ];
        }, $temp);

        return ['dataList' => array_values($temp)];
    }

    /**
     * 获取原始指标信息
     *
     * @param $item
     * @return array
     */
    private function getRawQuotaInfo($item)
    {
        return [
            'stop_delay_weight' => $item['stop_delay'] * $item['traj_count'],
            'stop_time_cycle' => $item['stop_time_cycle'],
            'traj_count' => $item['traj_count']
        ];
    }

    /**
     * 获取最终指标信息
     *
     * @param $item
     * @return array
     */
    private function getFinalQuotaInfo($item)
    {
        //实时指标配置文件
        $realTimeQuota = $this->config->item('real_time_quota');

        return [
            'stop_delay' => [
                'name' => '平均延误',
                'value' => $realTimeQuota['stop_delay']['round']($item['quota']['stop_delay_weight'] / $item['quota']['traj_count']),
                'unit' => $realTimeQuota['stop_delay']['unit'],
            ],
            'stop_time_cycle' => [
                'name' => '最大停车次数',
                'value' => $realTimeQuota['stop_time_cycle']['round']($item['quota']['stop_time_cycle']),
                'unit' => $realTimeQuota['stop_time_cycle']['unit'],
            ]
        ];
    }

    /**
     * 获取原始报警信息
     *
     * @param $item
     * @param $city_id
     * @param $flowsInfo
     * @return array|string
     */
    private function getRawAlarmInfo($item, $flowsInfo)
    {
        $alarmCategory = $this->config->item('alarm_category');

        if (is_null($alarmCategory)) {
            return [];
        }

        $result = [];

        if ($alarmCategory[1]['formula']($item['spillover_rate'])) {
            $result[] = $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] . '-溢流';
        }
        if ($alarmCategory[2]['formula']($item)) {
            $result[] = $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] . '-过饱和';
        }

        return $result;
    }

    /**
     * 获取最终报警信息
     *
     * @param $item
     * @return array
     */
    private function getFinalAlarmInfo($item)
    {
        return [
            'is_alarm' => (int)!empty($item['alarm_info']),
            'comment' => $item['alarm_info']
        ];
    }

    /**
     * 获取当前路口的状态
     *
     * @param $item
     * @return array
     */
    private function getJunctionStatus($quota)
    {
        $junction_status = $this->config->item('junction_status') ?? null;

        if (is_null($junction_status)) {
            return [];
        }

        if ($junction_status[1]['formula']($quota['stop_delay']['value'])) {
            return [
                'name' => $junction_status[1]['name'],
                'key' => $junction_status[1]['key']
            ];
        } elseif ($junction_status[2]['formula']($quota['stop_delay']['value'])) {
            return [
                'name' => $junction_status[2]['name'],
                'key' => $junction_status[2]['key']
            ];
        } elseif ($junction_status[3]['formula']($quota['stop_delay']['value'])) {
            return [
                'name' => $junction_status[3]['name'],
                'key' => $junction_status[3]['key']
            ];
        } else {
            return [];
        }
    }

    /**
     * 数据处理，多个 flow 记录合并到其对应 junction
     *
     * @param $target
     * @param $item
     * @return mixed
     */
    private function mergeFlowInfo($target, $item)
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