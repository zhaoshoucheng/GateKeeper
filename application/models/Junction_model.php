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
    public function searchDB($select = '*', $where = [], $resultType = 'result_array', $groupBy = '', $limit= '')
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
        $where = [
            'task_id'     => $data['task_id'],
            'junction_id' => $data['junction_id'],
        ];
        if ((int)$data['search_type'] == 1) {
            $time_range = explode('-', $data['time_range']);
            $where = array_merge($where, [
                'type'       => 1,
                'start_time' => $time_range[0],
                'end_time'   => $time_range[1],
            ]);
        } else {
            $where = array_merge($where, [
                'type'       => 0,
                'time_point' => $data['time_point'],
            ]);
        }

        $result = $this->db->select($select)
                            ->from($this->tb)
                            ->where($where)
                            ->get();
        if (!$result) {
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
