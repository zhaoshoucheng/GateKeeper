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

        $result = formatCongestionInfoData($res);

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

        // 路口总数
        $junctionTotal = count($data);

        // 拥堵数量
        $result['count'] = [
            // 畅通路口数
            'open'       => 0,
            // 缓行路口数
            'amble'      => 0,
            // 拥堵路口数
            'congestion' => 0,
        ];

        // 路口状态配置
        $junctionStatusConf = $this->config->item('junction_status');
        // 组织新路口状态数组，用于判断路口状态 英文key => 计算规则
        $junctionSatus = array_column($junctionStatusConf, 'en_key', 'formula');

        foreach ($data as $k=>$v) {
            foreach ($junctionSatus as $key=>$val) {
                if ($val($v['stop_delay'])) {
                    $result['count'][$key] += 1;
                }
            }
        }

        // 拥堵占比
        $result['ratio'] = [
            'open'       => $result['count']['open'] / $junctionTotal . '%',
            'amble'      => $result['count']['amble'] / $junctionTotal . '%',
            'congestion' => $result['count']['congestion'] / $junctionTotal . '%',
        ];

        echo "<pre>";print_r($result);

        return $result;
    }
}