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
            return [];
        }

        $table = $this->tb . $data['city_id'];
        // 判断数据表是否存在
        if ($this->isTableExisted($table)) {
            com_log_warning('_itstool_JuctionCompareReport_table_error', 0, '数据表不存在', compact("table"));
            return [];
        }

        // 获取基准、评估日期指标加权平均值所需数据
        $publicData = [
            'logic_junction_id' => $data['logic_junction_id'],
            'quota_key'         => $data['quota_key'],
        ];

        /* 获取基准日期指标加权平均值 */
        $baseStartDate = strtotime($data['base_start_date']);
        $baseEndDate = strtotime($data['base_end_date']);
        $baseDateArr = [];
        for ($i = $baseStartDate; $i <= $baseEndDate; $i += 24 * 3600) {
            $baseDateArr[] = date('Y-m-d', $i);
        }
        $publicData['date'] = $baseDateArr;
        $baseQuotaData = $this->getQuotaInfoByDate($table, $publicData);

        /* 获取评估日期指标加权平均值 */
        $evaluateStartDate = strtotime($data['evaluate_start_date']);
        $evaluateEndDate = strtotime($data['evaluate_end_date']);
        $evaluateDateArr = [];
        for ($i = $evaluateStartDate; $i <= $evaluateEndDate; $i += 24 * 3600) {
            $evaluateDateArr[] = date('Y-m-d', $i);
        }
        $publicData['date'] = $evaluateDateArr;
        $evaluateQuotaData = $this->getQuotaInfoByDate($table, $publicData);


        $result = [];


        return $result;
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
            return [];
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