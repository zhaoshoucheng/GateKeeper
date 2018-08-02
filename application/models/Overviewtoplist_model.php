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
        return $this->topList('stop_delay', $data, 'avg');
    }

    public function stopTimeCycleTopList($data)
    {
        return $this->topList('stop_time_cycle', $data, 'max');
    }

    /**
     * 依据时间获取指定字段指定数目的数据
     *
     * @param $column
     * @param $data
     * @param $method max|sum|avg
     * @return array
     */
    private function topList($column, $data, $method)
    {
        $table = 'real_time_' . $data['city_id'];

        $result = $this->db->select('logic_junction_id, hour, ' . $method . '(' . $column . ') as ' . $column)
            ->from($table)
            ->where('hour', $data['time_point'])
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->group_by('logic_junction_id')
            ->order_by($method . '(' . $column . ')')
            ->limit($data['pagesize'])
            ->get()->result_array();

        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        $junctionIdNames = $this->waymap_model->getJunctionInfo($ids, ['key' => 'logic_junction_id', 'value' => 'name']);

        $realTimeQuota = $this->config->item('real_time_quota');

        $result = array_map(function ($item) use ($column, $junctionIdNames, $realTimeQuota) {
            return [
                'time' => $item['hour'],
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionIdNames[$item['logic_junction_id']] ?? '',
                $column => $realTimeQuota[$column]['round']($item[$column]),
                'quota_unit' => $realTimeQuota[$column]['unit']
            ];
        }, $result);

        return $result;
    }
}