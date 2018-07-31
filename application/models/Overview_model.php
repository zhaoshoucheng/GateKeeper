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

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            // 添加日志
            return [];
        }

        $this->load->config('realtime_conf');
        $this->load->model('waymap_model');
    }

    public function junctionsList($data)
    {
        $result = [];

        $table = 'real_time_' . $data['city_id'];

        $result = $this->db->select('logic_junction_id, hour, stop_delay, logic_flow_id')
            ->from($table)
            ->where('hour', date('H:i', strtotime($data['time_point'])))
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->group_by('logic_flow_id')
            ->get()->result_array();

        $ids = implode(',', array_column($result, 'logic_junction_id'));

        //获取自定义的返回格式
        $junctionsInfo = $this->waymap_model->getJunctionInfo($ids, [
            'key' => 'logic_junction_id',
            'value' => ['name', 'lng', 'lat']
        ]);

        array_map(function ($item) use ($junctionsInfo) {
            $junctionInfo = $junctionsInfo[$item['logic_junction_id']];
            return [
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionInfo['name'],
                'lng' => $junctionInfo['lng'],
                'lat' => $junctionInfo['lat'],
                'quota' => $this->createQuotaInfo($item),
                'alarm_info' => [],
                'junction_status' => $this->getJunctionStatus($item)
            ];
        }, $result);

        return $result;
    }

    private function createQuotaInfo($item)
    {
        $real_time_quota = $this->config->item('real_time_quota');

        array_map(function ($key, $value) use ($item) {
            return [
                'name' => $value['name'],
                'value' => $item[$key],
                'unit' => $value['unit']
            ];
        }, $real_time_quota);

        return $real_time_quota;
    }

    private function getJunctionStatus($item)
    {
        $junction_status = $this->config->item('junction_status')[$item['id']];

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
            return [];
        }
    }
}