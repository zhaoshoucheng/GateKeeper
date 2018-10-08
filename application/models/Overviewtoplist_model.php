<?php
/********************************************
# desc:    TOP列表数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Overviewtoplist_model extends CI_Model
{
    private $tb = 'real_time_';
    private $db = '';

    public function __construct()
    {
        parent::__construct();

        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $this->load->config('realtime_conf');
        $this->load->model('waymap_model');
        $this->load->model('redis_model');
        $this->load->model('common_model');
    }

    public function stopDelayTopList($data)
    {
        $table = $this->tb . $data['city_id'];
        if (!$this->isTableExisted($table)) {
            return [];
        }

        $hour = $this->common_model->getLastestHour($data['city_id'], $data['date']);

        $result = $this->db->select('logic_junction_id, hour, sum(stop_delay * traj_count) / sum(traj_count) as stop_delay')
            ->from($table)
            ->where('hour', $hour)
            ->where('traj_count >=', 10)
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->group_by('logic_junction_id')
            ->order_by('sum(stop_delay * traj_count) / sum(traj_count)', 'desc')
            ->limit($data['pagesize'])
            ->get()->result_array();

        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        $junctionIdNames = $this->waymap_model->getJunctionInfo($ids, ['key' => 'logic_junction_id', 'value' => 'name']);

        $realTimeQuota = $this->config->item('real_time_quota');

        $result = array_map(function ($item) use ($junctionIdNames, $realTimeQuota) {
            return [
                'time' => $item['hour'],
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionIdNames[$item['logic_junction_id']] ?? '未知路口',
                'stop_delay' => $realTimeQuota['stop_delay']['round']($item['stop_delay']),
                'quota_unit' => $realTimeQuota['stop_delay']['unit']
            ];
        }, $result);

        return $result;
    }

    public function stopTimeCycleTopList($data)
    {
        $table = $this->tb . $data['city_id'];
        if (!$this->isTableExisted($table)) {
            return [];
        }

        $hour = $this->common_model->getLastestHour($data['city_id'], $data['date']);

        $result = $this->db->select('logic_junction_id, hour, stop_time_cycle, logic_flow_id')
            ->from($table)
            ->where('hour', $hour)
            ->where('traj_count >=', 10)
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->order_by('stop_time_cycle', 'desc')
            ->limit($data['pagesize'])
            ->get()->result_array();

        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        $junctionIdNames = $this->waymap_model->getJunctionInfo($ids, ['key' => 'logic_junction_id', 'value' => 'name']);

        $flowsInfo = $this->waymap_model->getFlowsInfo($ids);

        $realTimeQuota = $this->config->item('real_time_quota');

        $result = array_map(function ($item) use ($junctionIdNames, $realTimeQuota, $flowsInfo) {
            return [
                'time' => $item['hour'],
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionIdNames[$item['logic_junction_id']] ?? '未知路口',
                'logic_flow_id' => $item['logic_flow_id'],
                'flow_name' => $flowsInfo[$item['logic_junction_id']][$item['logic_flow_id']] ?? '未知方向',
                'stop_time_cycle' => $realTimeQuota['stop_time_cycle']['round']($item['stop_time_cycle']),
                'quota_unit' => $realTimeQuota['stop_time_cycle']['unit']
            ];
        }, $result);

        return $result;
    }

    /**
     * 依据时间获取指定字段指定数目的数据
     *
     * @param $column
     * @param $data
     * @param $method max|sum|avg
     * @return array
     */
    private function topList($column, $data, $select)
    {
        $table = $this->tb . $data['city_id'];

        if (!$this->isTableExisted($table)) {
            return [];
        }

        $hour = $this->common_model->getLastestHour($data['city_id'], $data['date']);

        $result = $this->db->select('logic_junction_id, hour, ' . $select . ' as ' . $column)
            ->from($table)
            ->where('hour', $hour)
            ->where('traj_count >=', 10)
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->group_by('logic_junction_id')
            ->order_by($select, 'desc')
            ->limit($data['pagesize'])
            ->get()->result_array();

        $ids = implode(',', array_unique(array_column($result, 'logic_junction_id')));

        $junctionIdNames = $this->waymap_model->getJunctionInfo($ids, ['key' => 'logic_junction_id', 'value' => 'name']);

        $realTimeQuota = $this->config->item('real_time_quota');

        $result = array_map(function ($item) use ($column, $junctionIdNames, $realTimeQuota) {
            return [
                'time' => $item['hour'],
                'logic_junction_id' => $item['logic_junction_id'],
                'junction_name' => $junctionIdNames[$item['logic_junction_id']] ?? '未知路口',
                $column => $realTimeQuota[$column]['round']($item[$column]),
                'quota_unit' => $realTimeQuota[$column]['unit']
            ];
        }, $result);

        return $result;
    }

    /*
     * 校验数据表是否存在
     */
    private function isTableExisted($table)
    {
        $isExisted = $this->db->table_exists($table);
        return $isExisted;
    }
}