<?php
/********************************************
# desc:    TOP列表数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Overviewtoplist_model extends CI_Model
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

    public function stopDelayTopList($data)
    {
        return $this->topList('stop_delay', $data);
    }

    public function stopTimeCycleTopList($data)
    {
        return $this->topList('stop_time_cycle', $data);
    }

    private function topList($column, $data)
    {
        $result = [];

        $table = 'real_time_' . $data['city_id'];

        $result = $this->db->select('logic_junction_id, hour, logic_flow_id' . $column)
            ->from($table)
            ->where('hour', date('H:i', strtotime($data['time_point'])))
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->group_by('logic_flow_id')
            ->limit($data['pagesize'])
            ->get()->result_array();

        $ids = implode(',', array_column($result, 'logic_junction_id'));

        $junctionIdNames = $this->waymap_model->getJunctionInfo($ids, ['key' => 'logic_junction_id', 'value' => 'name']);

        array_map(function ($item) use ($column, $junctionIdNames) {
            return [
                'time' => $item['hour'],
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionIdNames[$item['logic_junction_id']] ?? '',
                $column => $item[$column],
                'quota_unit' => ''
            ];
        }, $result);

        return $result;
    }
}