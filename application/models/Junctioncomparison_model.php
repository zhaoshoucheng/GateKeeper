<?php
/********************************************
# desc:    单点路口优化对比报告模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-08-23
********************************************/

class Junctioncomparison_model extends CI_Model
{
    private $tb = 'flow_duration_v6_';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $this->load->model('waymap_model');
    }

    /**
     * 获取单点路口优化对比
     * @param $data['logic_junction_id']   string   Y 路口ID
     * @param $data['city_id']             interger Y 城市ID
     * @param $data['base_start_date']     string   Y 基准开始日期 格式：yyyy-mm-dd
     * @param $data['base_end_date']       string   Y 基准结束日期 格式：yyyy-mm-dd
     * @param $data['evaluate_start_date'] string   Y 评估开始日期 格式：yyyy-mm-dd
     * @param $data['evaluate_end_date']   string   Y 评估结束日期 格式：yyyy-mm-dd
     * @param $data['week']                array    Y 星期 0-6
     * @param $data['schedule_start']      string   Y 时段开始时间 例：00:00
     * @param $data['schedule_end']        string   Y 时段结束时间 例：00:30
     * @param $data['quota_key']           string   Y 指标key
     * @return array
     */
    public function getQuotaInfo($data)
    {
        if (empty($data)) {
            return (object)[];
        }

        $table = $this->tb . $data['city_id'];
        // 判断数据表是否存在
        if (!$this->isTableExisted($table)) {
            com_log_warning('_itstool_JuctionCompareReport_table_error', 0, '数据表不存在', compact("table"));
            return (object)[];
        }

        // 获取路口所有相位
        $allFlows = $this->waymap_model->getFlowsInfo($data['logic_junction_id']);
        if (empty($allFlows)) {
            return (object)[];
        }

        // 获取基准、评估日期指标加权平均值所需数据
        $publicData = [
            'logic_junction_id' => $data['logic_junction_id'],
            'quota_key'         => $data['quota_key'],
        ];

        /* 获取基准日期指标加权平均值 计算出需要查的周几具体日期*/
        $baseStartDate = strtotime($data['base_start_date']);
        $baseEndDate = strtotime($data['base_end_date']);
        $baseDateArr = [];
        $baseWeekDays = [];
        for ($i = $baseStartDate; $i <= $baseEndDate; $i += 24 * 3600) {
            $baseDateArr[] = date('Y-m-d', $i);
            foreach ($data['week'] as $k=>$v) {
                if (date('w', $i) == $v) {
                    $baseWeekDays[$i] = date('Y-m-d', $i);
                }
            }
        }
        $publicData['date'] = $baseDateArr;
        $baseQuotaData = $this->getQuotaInfoByDate($table, $publicData);
        // 相位->日期->时间->值
        $newBaseQuotaData = [];
        foreach ($baseQuotaData as $val) {
            $newBaseQuotaData[$val['logic_flow_id']][$val['date']][$val['hour']] = $val['quota_value'];
        }

        /* 获取评估日期指标加权平均值 计算出需要查的周几具体日期*/
        $evaluateStartDate = strtotime($data['evaluate_start_date']);
        $evaluateEndDate = strtotime($data['evaluate_end_date']);
        $evaluateDateArr = [];
        $evaluateWeekDays = [];
        for ($i = $evaluateStartDate; $i <= $evaluateEndDate; $i += 24 * 3600) {
            $evaluateDateArr[] = date('Y-m-d', $i);
            foreach ($data['week'] as $k=>$v) {
                if (date('w', $i) == $v) {
                    $evaluateWeekDays[$i] = date('Y-m-d', $i);
                }
            }
        }
        $publicData['date'] = $evaluateDateArr;
        $evaluateQuotaData = $this->getQuotaInfoByDate($table, $publicData);
        // 相位->日期->时间->值
        $newEvaluateQuotaData = [];
        foreach ($evaluateQuotaData as $val) {
            $newEvaluateQuotaData[$val['logic_flow_id']][$val['date']][$val['hour']] = $val['quota_value'];
        }

        if (empty($newBaseQuotaData) && empty($newEvaluateQuotaData)) {
            com_log_warning('_itstool_JuctionCompareReport_data_error', 0, '评估&基准数据都没有', compact("publicData"));
            return (object)[];
        }

        // 处理时段
        $scheduleArr = [];
        $scheduleStart = strtotime($data['schedule_start']);
        $scheduleEnd = strtotime($data['schedule_end']);
        for ($i = $scheduleStart; $i <= $scheduleEnd; $i += 30 * 60) {
            $scheduleArr[] = date('H:i', $i);
        }

        $function = $data['quota_key'] . 'DataFormat';

        if (!method_exists($this, $function)) {
            return (object)[];
        }

        $formatData = [
            'baseQuotaData'     => $newBaseQuotaData,
            'evaluateQuotaData' => $newEvaluateQuotaData,
            'baseWeekDays'      => $baseWeekDays,
            'evaluateWeekDays'  => $evaluateWeekDays,
            'schedule'          => $scheduleArr,
        ];

        /**
         * 排队长度 => queue_lengthDataFormat
         * 停车延误 => stop_delayDataFormat
         * 通过速度 => speedDataFormat
         * 停车次数 => stop_time_cycleDataFormat
         * 停车比率 => stop_rateDataFormat
         * 溢流指数 => spillover_rateDataFormat
         */
        $result = $this->$function($allFlows[$data['logic_junction_id']], $formatData);

        return $result;
    }

    /**
     * 处理指标 排队长度 数据
     * @param $flowData                  array  路口flow信息 ['相位ID'=>'相位名称']
     * @param $data['baseQuotaData']     array  基准日期指标数据
     * @param $data['evaluateQuotaData'] array  评估日期指标数据
     * @param $data['baseWeekDays']      array  时段配置在基准日期段中所有星期日期 ['2018-08-08', ...]
     * @param $data['evaluateWeekDays']  array  时段配置在评估日期段中所有星期日期 ['2018-08-08', ...]
     * @param $data['schedule']          array  时段配置时间点 ['07:00', '07:30', ...]
     * @return array
     */
    private function queue_lengthDataFormat($flowData, $data)
    {
        $result = [];

        foreach ($flowData as $k=>$v) {
            // flow信息
            $result['flow_info'] = [
                'logic_flow_id' => $k,
                'flow_name'     => $v,
            ];

            // 基准
            foreach ($data['baseWeekDays'] as $day) {
                foreach ($data['schedule'] as $hour) {
                    $result['base_list'][$k][$day][$hour] = $data['baseQuotaData'][$k][$day][$hour];
                }
            }

            // 评估
            foreach ($data['evaluateWeekDays'] as $day) {
                foreach ($data['schedule'] as $hour) {
                    $result['base_list'][$k][$day][$hour] = $data['evaluateQuotaData'][$k][$day][$hour];
                }
            }
        }

    }

    /**
     * 处理指标 停车延误 数据
     * @param $flowData                  array  路口flow信息 ['相位ID'=>'相位名称']
     * @param $data['baseQuotaData']     array  基准日期指标数据
     * @param $data['evaluateQuotaData'] array  评估日期指标数据
     * @param $data['baseWeekDays']      array  时段配置在基准日期段中所有星期日期
     * @param $data['evaluateWeekDays']  array  时段配置在评估日期段中所有星期日期
     * @param $data['schedule_start']    string 时段开始时间
     * @param $data['schedule_end']      string 时段线束时间
     */
    private function stop_delayDataFormat($flowData, $data)
    {
        
    }

    /**
     * 处理指标 通过速度 数据
     * @param $flowData                  array  路口flow信息 ['相位ID'=>'相位名称']
     * @param $data['baseQuotaData']     array  基准日期指标数据
     * @param $data['evaluateQuotaData'] array  评估日期指标数据
     * @param $data['baseWeekDays']      array  时段配置在基准日期段中所有星期日期
     * @param $data['evaluateWeekDays']  array  时段配置在评估日期段中所有星期日期
     * @param $data['schedule_start']    string 时段开始时间
     * @param $data['schedule_end']      string 时段线束时间
     */
    private function speedDataFormat($flowData, $data)
    {
        
    }

    /**
     * 处理指标 停车次数 数据
     * @param $flowData                  array  路口flow信息 ['相位ID'=>'相位名称']
     * @param $data['logic_junction_id'] string 路口ID
     * @param $data['city_id']           string 城市ID
     * @param $data['baseQuotaData']     array  基准日期指标数据
     * @param $data['evaluateQuotaData'] array  评估日期指标数据
     * @param $data['baseWeekDays']      array  时段配置在基准日期段中所有星期日期
     * @param $data['evaluateWeekDays']  array  时段配置在评估日期段中所有星期日期
     * @param $data['schedule_start']    string 时段开始时间
     * @param $data['schedule_end']      string 时段线束时间
     */
    private function stop_time_cycleDataFormat($flowData, $data)
    {
        
    }

    /**
     * 处理指标 停车比率 数据
     * @param $flowData                  array  路口flow信息 ['相位ID'=>'相位名称']
     * @param $data['baseQuotaData']     array  基准日期指标数据
     * @param $data['evaluateQuotaData'] array  评估日期指标数据
     * @param $data['baseWeekDays']      array  时段配置在基准日期段中所有星期日期
     * @param $data['evaluateWeekDays']  array  时段配置在评估日期段中所有星期日期
     * @param $data['schedule_start']    string 时段开始时间
     * @param $data['schedule_end']      string 时段线束时间
     */
    private function stop_rateDataFormat($flowData, $data)
    {
        
    }

    /**
     * 处理指标 溢流指数 数据
     * @param $flowData                  array  路口flow信息 ['相位ID'=>'相位名称']
     * @param $data['baseQuotaData']     array  基准日期指标数据
     * @param $data['evaluateQuotaData'] array  评估日期指标数据
     * @param $data['baseWeekDays']      array  时段配置在基准日期段中所有星期日期
     * @param $data['evaluateWeekDays']  array  时段配置在评估日期段中所有星期日期
     * @param $data['schedule_start']    string 时段开始时间
     * @param $data['schedule_end']      string 时段线束时间
     */
    private function spillover_rateDataFormat($flowData, $data)
    {
        
    }

    /**
     * 获取单点路口优化对比
     * @param $table                     string   Y 数据表
     * @param $data['logic_junction_id'] string   Y 路口ID
     * @param $data['date']              array    Y 所需要查询的日期
     * @param $data['quota_key']         string   Y 指标key
     * @return array
     */
    private function getQuotaInfoByDate($table, $data)
    {
        $quotaFormula = 'sum(`' . $data['quota_key'] . '` * `traj_count`) / sum(`traj_count`)';
        $this->db->select("logic_flow_id, hour,date,  {$quotaFormula} as quota_value");
        $this->db->from($table);
        $where = 'logic_junction_id = "' . $data['logic_junction_id'] . '"';
        $where .= ' and traj_count >= 10';
        $this->db->where($where);
        $this->db->where_in('date', $data['date']);
        $this->db->group_by('date, hour');
        $res = $this->db->get()->result_array();
        if (!$res) {
            return (object)[];
        }

        return $res;
    }

    /**
     * 校验数据表是否存在
     */
    private function isTableExisted($table)
    {
        $isExisted = $this->db->table_exists($table);
        return $isExisted;
    }
}