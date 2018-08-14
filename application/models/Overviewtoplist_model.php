<?php
/********************************************
# desc:    TOP列表数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Overviewtoplist_model extends CI_Model
{
    private $tb = 'real_time_';

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
        $table = $this->tb . $data['city_id'];

        $hour = $this->getLastestHour($data['city_id'], $data['date']);

        $result = $this->db->select('logic_junction_id, hour, ' . $method . '(' . $column . ') as ' . $column)
            ->from($table)
            ->where('updated_at >=', $data['date'] . ' 00:00:00')
            ->where('updated_at <=', $data['date'] . ' 23:59:59')
            ->where('hour', $hour)
            ->where('traj_count >', 10)
            ->group_by('logic_junction_id')
            ->order_by($method . '(' . $column . ')', 'desc')
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

    private function getLastestHour($cityId, $date = null)
    {
        return "15:32:02";
        if(($hour = $this->redis_model->getData("its_realtime_lasthour_$cityId"))) {
            return $hour;
        }
        $date = $date ?? date('Y-m-d');

        $result = $this->db->select('hour')
            ->from($this->tb . $cityId)
            ->where('updated_at >=', $date . ' 00:00:00')
            ->where('updated_at <=', $date . ' 23:59:59')
            ->order_by('hour', 'desc')
            ->limit(1)
            ->get()->first_row();

        if(!$result)
            return date('H:i:s');

        return $result->hour;
    }
}