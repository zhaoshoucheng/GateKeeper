<?php
/********************************************
# desc:    路口数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-03-05
********************************************/

class Junction_model extends CI_Model
{
    private $tb = 'junction_index';
    private $db = '';

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

        $is_existed = $this->db->table_exists($this->tb);
        if (!$is_existed) {
            throw new \Exception('数据表不存在！', ERR_DATABASE);
        }

        $this->load->config('nconf');
        $this->load->model('waymap_model');
        $this->load->model('timing_model');
    }

    /**
     * 查询方法
     * @param $select      select colum  默认 *
     * @param $where       where条件     默认 1
     * @param $resultType  返回方式 row_array|result_array
     * @param $groupBy     group by
     * @param $limit       limit 例：1,100
     */
    public function searchDB($select = '*', $where = '1', $resultType = 'result_array', $groupBy = '', $limit= '')
    {
        $this->db->select($select);
        $this->db->from($this->tb);
        if ($where != '1') {
            $this->db->where($where);
        }
        if ($groupBy != '') {
            $this->db->group_by($groupBy);
        }
        if ($limit != '') {
            $this->db->limit($limit);
        }

        $res = $this->db->get();
        switch ($resultType) {
            case 'row_array':
                return $res instanceof CI_DB_result ? $res->row_array() : $res;
                break;
            default:
                return $res instanceof CI_DB_result ? $res->result_array() : $res;
                break;
        }
    }

    /**
    * 获取路口地图底图数据
    * @param $data['junction_id']     string   Y 逻辑路口ID
    * @param $data['dates']           string   Y 评估/诊断任务日期 ['20180102','20180103']
    * @param $data['search_type']     interger Y 查询类型 1：按方案查询 0：按时间点查询
    * @param $data['time_point']      string   N 时间点 格式 00:00 PS:当search_type = 0 时 必传
    * @param $data['time_range']      string   N 方案的开始结束时间 (07:00-09:15) 当search_type = 1 时 必传 时间段
    * @param $data['task_time_range'] string   Y 评估/诊断任务开始结束时间 格式 00:00-24:00
    * @param $data['timingType']      interger Y 配时来源 1：人工 2：反推
    * @return array
    */
    public function getJunctionMapData($data)
    {
        if (empty($data)) {
            return [];
        }

        $junction_id = trim($data['junction_id']);

        $result = [];

        // 获取配时数据 地图底图数据源用配时的
        $timing_data = [
            'junction_id' => $junction_id,
            'dates'       => $data['dates'],
            'timingType'  => $data['timingType']
        ];
        if ((int)$data['search_type'] == 1) { // 按方案查询
            $time_range = array_filter(explode('-', $data['time_range']));
            $timing_data['time_range'] = trim($time_range[0]) . '-' . date("H:i", strtotime($time_range[1]) - 60);
        } else { // 按时间点查询
            $timing_data['time_point'] = trim($data['time_point']);
            $timing_data['time_range'] = trim($data['task_time_range']);
        }

        $timing = $this->timing_model->getTimingDataForJunctionMap($timing_data);
        if (!$timing || empty($timing)) {
            return [];
        }

        /*------------------------------------
        | 获取路网路口各相位经纬度及路口中心经纬度 |
        -------------------------------------*/
        // 获取地图版本
        $map_version = $this->waymap_model->getMapVersion(implode(',', $data['dates']));
        if (empty($map_version)) {
            return [];
        }

        // 获取路网路口各相位坐标
        $ret = $this->waymap_model->getJunctionFlowLngLat(current($map_version), $junction_id, array_keys($timing['list']));
        if (empty($ret['data'])) {
            return [];
        }
        foreach ($ret['data'] as $k=>$v) {
            if (!empty($timing['list'][$v['logic_flow_id']])) {
                $result['dataList'][$k]['logic_flow_id'] = $v['logic_flow_id'];
                $result['dataList'][$k]['flow_label'] = $timing['list'][$v['logic_flow_id']];
                $result['dataList'][$k]['lng'] = $v['flows'][0][0];
                $result['dataList'][$k]['lat'] = $v['flows'][0][1];
            }
        }
        // 获取路口中心坐标
        $result['center'] = '';
        $center_data['logic_id'] = $junction_id;
        $center = $this->waymap_model->getJunctionCenterCoords($junction_id);

        $result['center'] = $center;
        $result['map_version'] = $map_version;

        if (!empty($result['dataList'])) {
            $result['dataList'] = array_values($result['dataList']);
        }

        return $result;
    }

    /**
    * 获取路口信息用于轨迹
    * @param $data['task_id']     interger 任务ID
    * @param $data['junction_id'] string   路口ID
    * @param $data['flow_id']     string   flow_id
    * @param $data['search_type'] interger 搜索类型 1：按方案时间段 0：按时间点
    * @param $data['time_point']  string   时间点 当search_type = 0 时有此参数
    * @param $data['time_range']  string   时间段 当search_type = 1 时有此参数
    * @return array
    */
    public function getJunctionInfoForTheTrack($data)
    {
        if (empty($data)) {
            return [];
        }

        $result = [];

        $select = 'task_id, junction_id, dates, start_time, end_time, clock_shift, movements';
        $where  = "task_id = {$data['task_id']} and junction_id = '{$data['junction_id']}'";
        if ((int)$data['search_type'] == 1) {
            $time_range = explode('-', $data['time_range']);
            $where .= " and type = 1 and start_time = '{$time_range[0]}' and end_time = '{$time_range[1]}'";
        } else {
            $where .= " and type = 0 and time_point = '{$data['time_point']}'";
        }

        $result = $this->db->select($select)
                            ->from($this->tb)
                            ->where($where)
                            ->get();
        if (!$result) {
            $content = "form_data = " . json_encode($data);
            $content .= "<br>sql = " . $this->db->last_query();
            $content .= "<br>result = " . $result;
            sendMail($this->email_to, 'logs: 获取时空/散点图（'.$type.'）->获取路口详情为空', $content);
            return [];
        }

        $result = $result->row_array();
        if (isset($result['movements'])) {
            $result['movements'] = json_decode($result['movements'], true);
            foreach ($result['movements'] as $v) {
                if ($v['movement_id'] == trim($data['flow_id'])) {
                    $result['flow_id'] = $v['movement_id'];
                    $result['af_condition'] = $v['af_condition'] ?? '';
                    $result['bf_condition'] = $v['bf_condition'] ?? '';
                    $result['num'] = $v['num'] ?? 0;
                    unset($result['movements']);
                }
            }
        }

        return $result;
    }

    /**
    * 获取任务创建用户 暂时这么做
    */
    public function getTaskUser($taskId)
    {
        $this->db->select('user');
        $this->db->from('task_result');
        $this->db->where('id', $taskId);
        $result = $this->db->get()->row_array();

        return $result['user'];
    }
}
