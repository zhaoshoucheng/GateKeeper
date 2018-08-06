<?php
/********************************************
# desc:    实时报警数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Overviewalarm_model extends CI_Model
{
    private $tb = 'real_time_alarm';
    private $db = '';

    public function __construct()
    {
        parent::__construct();

        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('realtime_conf.php');
    }

    /**
     * 获取今日报警概览信息
     * @param $data['city_id']    interger Y 城市ID
     * @param $data['date']       string   Y 日期 Y-m-d
     * @param $data['time_point'] string   Y 时间 H:i:s
     * @return array
     */
    public function todayAlarmInfo($data)
    {
        if (empty($data)) {
            return [];
        }
        $result = [];

        $this->db->select('logic_junction_id, logic_flow_id, updated_at, type');
        $date = $data['date'] . ' ' . $data['time_point'];
        $where = 'city_id = ' . $data['city_id'] . ' and  day(`updated_at`) = day("' . $date . '")';
        $this->db->from($this->tb);
        $this->db->where($where);
        $this->db->group_by('type, logic_junction_id');
        $res = $this->db->get();

        $res = $res->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatTodayAlarmInfoData($res);

        return $result;
    }

    /**
     * 格式化今日报警概览信息数据
     * @param $data 报警信息数组
     * @return array
     */
    private function formatTodayAlarmInfoData($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $tempJunctiomNum = [];
        foreach ($data as $k=>$v) {
            $tempJunctiomNum[$v['type']][$v['logic_junction_id']] = 1;
        }

        // 今日报警路口总数
        $junctionTotal = count($data);

        // 报警类别配置
        $alarmCate = $this->config->item('alarm_category');

        foreach ($alarmCate as $k=>$v) {
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num'  => isset($tempJunctiomNum[$k]) ? count($tempJunctiomNum[$k]) : 0,
            ];

            $result['ratio'][$k] = [
                'cate'  => $v['name'],
                'ratio' => isset($tempJunctiomNum[$k]) ? (count($tempJunctiomNum[$k]) / $junctionTotal) * 100 . '%' : '0%',
            ];
        }

        $result['count'] = array_values($result['count']);
        $result['ratio'] = array_values($result['ratio']);

        return $result;
    }

    /**
     * 获取七日报警变化
     * @param $data['city_id']    interger Y 城市ID
     * @param $data['date']       string   Y 日期 Y-m-d
     * @param $data['time_point'] string   Y 时间 H:i:s
     * @return array
     */
    public function sevenDaysAlarmChange($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        // 七日日期
        $sevenDates = [];

        // 前6天时间戳作为开始时间
        $startDate = strtotime($data['date'] . '-6 day');
        // 当前日期时间戳作为结束时间
        $endDate = strtotime($data['date']);

        for ($i = $startDate; $i <= $endDate; $i += 24 * 3600) {
            $sevenDates[] = date('Y-m-d', $i);
        }

        $this->db->select('logic_junction_id, date');
        $this->db->from($this->tb);
        $this->db->where('city_id = ' . $data['city_id']);
        $this->db->where_in('date', $sevenDates);
        $this->db->group_by('logic_junction_id');
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatSevenDaysAlarmChangeData($res);

        return $result;
    }

    /**
     * 格式化七日报警变化数据
     * @param $data 数据集合
     * @return array
     */
    private function formatSevenDaysAlarmChangeData($data)
    {
        $result = [];

        $tempData = [];
        foreach ($data as $k=>$v) {
            $tempData[date('m.d', strtotime($v['date']))][$v['logic_junction_id']] = 1;
        }

        if (empty($tempData)) {
            return [];
        }

        array_walk($tempData, function($item, $key){
            $result[$key] = [
                'date'  => $key,
                'value' => count($item),
            ];
        });

        echo "<pre>";print_r($result);

        return $result;
    }
}
