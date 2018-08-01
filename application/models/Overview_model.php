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
        $result = [];

        $table = 'real_time_' . $data['city_id'];

        $result = $this->db->select('*')
            ->from($table)
            ->where('hour', date('H:i', strtotime($data['time_point'])))
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->get()->result_array();

        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        //获取自定义的返回格式
        $junctionsInfo = $this->waymap_model->getJunctionInfo($ids, [
            'key' => 'logic_junction_id',
            'value' => ['name', 'lng', 'lat']
        ]);

        $flowsInfo = $this->waymap_model->getFlowsInfo($ids);

        $result = array_map(function ($item) use ($junctionsInfo, $flowsInfo, $data) {
            $junctionInfo = $junctionsInfo[$item['logic_junction_id']] ?? '';

            $junction_status = $this->getJunctionStatus($item, $data['city_id']);

            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionInfo['name'] ?? '',
                'lng' => $junctionInfo['lng'] ?? '',
                'lat' => $junctionInfo['lat'] ?? '',
                'quota' => $this->createQuotaInfo($item),
                'alarm_info' => $this->getAlarmInfo($item, $data['city_id'], $flowsInfo),
                'junction_status' => $junction_status
            ];
        }, $result);

        $result = $this->mergeJunctionResult($result);

        return $result;
    }

    private function createQuotaInfo($item)
    {
        return [
            'stop_delay' => [$item['stop_delay']],
            'stop_time_cycle' => [$item['stop_time_cycle']]
        ];
    }

    /**
     * 获取当前路口的状态
     *
     * @param $item
     * @return array
     */
    private function getJunctionStatus($item, $city_id)
    {
        $junction_status = $this->config->item('junction_status')[$city_id] ?? null;

        if (is_null($junction_status)) {
            return [
                'name' => '',
                'key' => 0
            ];
        }

        $formula_alarm = $junction_status[4]['formula'];

        if ($junction_status[1]['formula']($item['stop_delay'])) {
            return [
                'name' => $junction_status[1]['name'],
                'key' => $junction_status[1]['key']
            ];
        } elseif ($junction_status[2]['formula']($item['stop_delay'])) {
            return [
                'name' => $junction_status[2]['name'],
                'key' => $junction_status[2]['key']
            ];
        } elseif ($junction_status[3]['formula']($item['stop_delay'])) {
            return [
                'name' => $junction_status[3]['name'],
                'key' => $junction_status[3]['key']
            ];
        } elseif ($formula_alarm($item['spillover_rate'], 'spillover_rate')
            || ($formula_alarm($item['twice_stop_rate'], 'twice_stop_rate')
                && $formula_alarm($item['queue_length'], 'queue_length')
                && $formula_alarm($item['stop_delay'], 'stop_delay'))) {
            return [
                'name' => $junction_status[4]['name'],
                'key' => $junction_status[4]['key']
            ];
        } else {
            return [
                'name' => '',
                'key' => 0
            ];
        }
    }

    /**
     * 合并结果数组，返回数据粒度为 junction
     *
     * @param $result
     * @return array
     */
    private function mergeJunctionResult($result)
    {
        $temp = [];

        foreach ($result as $item) {
            $temp[$item['logic_junction_id']] = isset($temp[$item['logic_junction_id']]) ?
                $this->mergeJunctionResultItem($temp[$item['logic_junction_id']], $item) :
                $item;
        }

        foreach ($temp as &$item) {
            $item['quota']['stop_delay']      = round(array_sum($item['quota']['stop_delay']) / count($item['quota']['stop_delay']), 2);
            $item['quota']['stop_time_cycle'] = max($item['quota']['stop_time_cycle']);
        }

        return [ 'dataList' => array_values($temp) ];
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
        //合并所有指标
        foreach ($target['quota'] as $key => &$quote) {
            $quote = array_merge($quote, $item['quota'][$key]);
        }

        //合并报警信息
        $target['alarm_info'] = array_merge($target['alarm_info'], $item['alarm_info']) ?? [];

        //取所有路口状态 key 值最大的一个
        $target['junction_status'] =
            $target['junction_status']['key'] > $item['junction_status']['key'] ?
                $target['junction_status'] : $item['junction_status'];

        return $target;
    }

    private function getAlarmInfo($item, $city_id, $flowsInfo)
    {
        //获取路口当前 flow 的状态
        $junction_status = $this->config->item('junction_status')[$city_id] ?? null;

        if (is_null($junction_status)) {
            return [];
        }

        $formula_alarm = $junction_status[4]['formula'];

        if ($formula_alarm($item['spillover_rate'], 'spillover_rate')){
            return [$flowsInfo[$item['logic_junction_id']]['logic_flow_id']] . '-溢流';
        } elseif (($formula_alarm($item['twice_stop_rate'], 'twice_stop_rate')
            && $formula_alarm($item['queue_length'], 'queue_length')
            && $formula_alarm($item['stop_delay'], 'stop_delay'))
        ) {
            return [$flowsInfo[$item['logic_junction_id']]['logic_flow_id']] . '-过饱和';
        } else {
            return [];
        }
    }
}