<?php
/********************************************
# desc:    概览数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-07-25
********************************************/

class Overview_model extends CI_Model
{
    private $tb = 'real_time_';
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
     * 获取拥堵概览
     * @param $data['city_id']    interger Y 城市ID
     * @param $data['date']       string   Y 日期 Y-m-d
     * @param $data['time_point'] string   Y 时间 H:i:s
     * @return array
     */
    public function getCongestionInfo($data)
    {
        $result = [];
        $table = $this->tb . $data['city_id'];

        /*
         * 获取实时路口停车延误记录
         * 现数据表记录的是每个路口各相位的指标数据
         * 所以路口的停车延误指标计算暂时定为：路口各相位的(停车延误 * 轨迹数量)相加 / 路口各相位轨迹数量之和
         */
        $this->db->select('SUM(`stop_delay` * `traj_count`) / SUM(`traj_count`) as stop_delay,
            logic_junction_id,
            hour,
            updated_at'
        );

        $where = "updated_at = (select updated_at from $table ORDER by updated_at DESC LIMIT 1)";
        $this->db->from($table);
        $this->db->where($where);
        $this->db->group_by('logic_junction_id');
        $res = $this->db->get();

        $res = $res->result_array();
        if (empty($res)) {
            return [];
        }

        $result = $this->formatCongestionInfoData($res);

        return $result;
    }

    /**
     * 格式化拥堵概览数据
     * @param $data 数据
     * @return array
     */
    private function formatCongestionInfoData($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        // 拥堵数量
        $result['count'] = [];
        // 拥堵占比
        $result['ratio'] = [];

        // 路口总数
        $junctionTotal = count($data);

        // 路口状态配置
        $junctionStatusConf = $this->config->item('junction_status');
        // 路口状态计算规则
        $junctinStatusFormula = $this->config->item('junction_status_formula');

        foreach ($data as $k=>$v) {
            $result['count'][$junctinStatusFormula($v['stop_delay'])][$k] = 1;
        }

        echo "<pre>";print_r($result);

        return $result;
    }
}