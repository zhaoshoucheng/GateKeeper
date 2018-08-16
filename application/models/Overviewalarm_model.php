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
        $this->load->model('waymap_model');
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

        // 获取溢流报警个数
        $spilloverCount = $this->db->select('count(DISTINCT logic_junction_id) as num');
        $spilloverCount = $this->db->from($this->tb);
        $where = 'city_id = ' . $data['city_id'] . ' and  date = "' . $data['date'] . '" and type = 1';
        $spilloverCount = $this->db->where($where);
        $spilloverCount = $this->db->get()->row_array();
        $spilloverCountRes = $spilloverCount['num'];

        // 获取过饱和报警个数
        $saturationCount = $this->db->select('count(DISTINCT logic_junction_id) as num');
        $saturationCount = $this->db->from($this->tb);
        $where = 'city_id = ' . $data['city_id'] . ' and  date = "' . $data['date'] . '" and type = 2';
        $saturationCount = $this->db->where($where);
        $saturationCount = $this->db->get()->row_array();
        $saturationCountRes = $saturationCount['num'];

        $result = $this->formatTodayAlarmInfoData($spilloverCountRes, $saturationCountRes);

        return $result;
    }

    /**
     * 格式化今日报警概览信息数据
     * @param $data 报警信息数组
     * @return array
     */
    private function formatTodayAlarmInfoData($spilloverCount, $saturationCount)
    {
        $result = [];

        $total = intval($spilloverCount) + intval($saturationCount);

        // 报警类别配置
        $alarmCate = $this->config->item('alarm_category');

        foreach ($alarmCate as $k=>$v) {
            if ($k == 1) {
                // 溢流
                $num = intval($spilloverCount);
            } else {
                // 过饱和
                $num = intval($saturationCount);
            }
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num'  => $num,
            ];

            $result['ratio'][$k] = [
                'cate'  => $v['name'],
                'ratio' => ($total >= 1) ? round(($num / $total) * 100 ) . '%' : '0%',
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
        $this->db->group_by('logic_junction_id, date');
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatSevenDaysAlarmChangeData($res, $sevenDates);

        return $result;
    }

    /**
     * 格式化七日报警变化数据
     * @param $data       数据集合
     * @param $sevenDates 七日日期集合
     * @return array
     */
    private function formatSevenDaysAlarmChangeData($data, $sevenDates)
    {
        $result = [];

        $tempData = [];
        foreach ($data as $k=>$v) {
            $tempData[$v['date']][$v['logic_junction_id']] = 1;
        }

        if (empty($tempData)) {
            return [];
        }

        foreach ($sevenDates as $k=>$v) {
            $result['dataList'][$v] = [
                'date'  => $v,
                'value' => isset($tempData[$v]) ? count($tempData[$v]) : 0,
            ];
        };

        if (!empty($result['dataList'])) {
            $result['dataList'] = array_values($result['dataList']);
        }

        return $result;
    }

    /**
     * 实时报警列表
     * @param $data['city_id']    interger Y 城市ID
     * @param $data['date']       string   Y 日期 Y-m-d
     * @param $data['time_point'] string   Y 时间 H:i:s
     * @return array
     */
    public function realTimeAlarmList($data)
    {
        if (empty($data)) {
            return [];
        }

        // 获取最近时间
        $lastHour = $this->getLastestHour($data['city_id'], $data['date']);
        $lastTime = date('Y-m-d') . ' ' . $lastHour;
        $cycleTime = date('Y-m-d H:i:s', strtotime($lastTime) + 120);

        $result = [];
        $where = 'city_id = ' . $data['city_id'] . ' and date = "' . $data['date'] . '"';
        $where .= " and last_time >= '{$lastTime}' and last_time <= '{$cycleTime}'";
        $this->db->select('type, logic_junction_id, logic_flow_id, start_time, last_time');
        $this->db->from($this->tb);
        $this->db->where($where);
        $this->db->order_by('type asc, (last_time - start_time) desc');
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatRealTimeAlarmListData($res);

        return $result;
    }

    /**
     * 格式化实时报警列表
     * @param $data 数据集合
     * @return array
     */
    public function formatRealTimeAlarmListData($data)
    {
        $result = [];

        // 需要获取路口name的路口ID口中
        $junctionIds = implode(',', array_unique(array_column($data, 'logic_junction_id')));

        // 获取路口信息
        $junctionsInfo = $this->waymap_model->getJunctionInfo($junctionIds);
        $junctionIdName = array_column($junctionsInfo, 'name', 'logic_junction_id');

        // 获取路口相位信息
        $flowsInfo = $this->waymap_model->getFlowsInfo($junctionIds);

        // 报警类别
        $alarmCate = $this->config->item('alarm_category');

        foreach ($data as $k=>$val) {
            // 持续时间
            $durationTime = round((strtotime($val['last_time']) - strtotime($val['start_time'])) / 60, 2);
            if ($durationTime == 0) {
                // 当前时间
                $nowTime = time();
                $tempDurationTime = ($nowTime - strtotime($val['start_time'])) / 60;
                // 默认持续时间为2分钟 有的只出现一次，表里记录last_time与start_time相等
                if ($tempDurationTime < 2) {
                    $durationTime = $tempDurationTime;
                } else {
                    $durationTime = 2;
                }
            }

            if (!empty($junctionIdName[$val['logic_junction_id']])
                && !empty($flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']])) {
                $result['dataList'][$k] = [
                    'start_time'        => date('H:i', strtotime($val['start_time'])),
                    'duration_time'     => $durationTime,
                    'logic_junction_id' => $val['logic_junction_id'],
                    'junction_name'     => $junctionIdName[$val['logic_junction_id']] ?? '',
                    'logic_flow_id'     => $val['logic_flow_id'],
                    'flow_name'         => $flowsInfo[$val['logic_junction_id']][$val['logic_flow_id']] ?? '',
                    'alarm_comment'     => $alarmCate[$val['type']]['name'] ?? '',
                    'alarm_key'         => $val['type'],
                ];
            }
        }

        if (empty($result['dataList'])) {
            return [];
        }
        $result['dataList'] = array_values($result['dataList']);

        return $result;
    }

    /**
     * 获取指定日期最新的数据时间
     * @param $table
     * @param null $date
     * @return false|string
     */
    private function getLastestHour($cityId, $date = null)
    {
        if(($hour = $this->redis_model->getData("its_realtime_lasthour_$cityId"))) {
            return $hour;
        }

        $date = $date ?? date('Y-m-d');

        $result = $this->db->select('hour')
            ->from('real_time_' . $cityId)
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
