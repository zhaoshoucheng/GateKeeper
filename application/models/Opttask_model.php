<?php

/********************************************
 * # desc:    区域数据模型
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-08-23
 ********************************************/
use Didi\Cloud\Collection\Collection;

class Opttask_model extends CI_Model {
    private $tb = 'opt_task';

    /**
     * @var \CI_DB_query_builder
     */
    protected $db;

    /**
     * Opttask_model constructor.
     * @throws \Exception
     */
    public function __construct() {
        parent::__construct();

        $this->db = $this->load->database('default', true);

        $isExisted = $this->db->table_exists($this->tb);

        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     * 获取任务列表
     *
     * @return array
     */
    public function TaskList($city_id, $task_type, $limit, $offset, $select = '*') {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $city_id)
            ->where('task_type', $task_type)
            ->where('delete_at', "1971-01-01 00:00:00")
            ->order_by('update_at', 'DESC')
            ->limit($limit, $offset)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 获取任务列表
     *
     * @return array
     */
    public function TaskListByCityID($city_id, $task_type, $select = '*') {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $city_id)
            ->where('task_type', $task_type)
            ->where('delete_at', "1971-01-01 00:00:00")
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 获取任务总数
     *
     * @return array
     */
    public function TaskTotal($city_id, $task_type)
    {
        $res = $this->db
            ->from($this->tb)
            ->where('city_id', $city_id)
            ->where('task_type', $task_type)
            ->where('delete_at', "1971-01-01 00:00:00")
            ->count_all_results();

        return $res;
    }

    /**
     * 创建任务
     *
     * @return int
     */
    public function CreateTask($city_id, $task_name, $task_type, $road_id, $config)
    {
        $obj = [
            'city_id' => $city_id,
            'task_name' => $task_name,
            'task_type' => $task_type,
            'road_id' => $road_id,
            'config' => json_encode($config),
            'status' => 0,
        ];
        $res = $this->db->insert($this->tb, $obj);
        if ($res == false) {
            return 0;
        } else {
            return $this->db->insert_id();
        }
    }

    /**
     * 更新任务
     *
     * @return bool
     */
    public function UpdateTask($task_id, $city_id, $task_name, $task_type, $road_id, $config)
    {
        $obj = [
            'city_id' => $city_id,
            'task_name' => $task_name,
            'task_type' => $task_type,
            'road_id' => $road_id,
            'config' => json_encode($config),
        ];
        $res = $this->db->where('id', $task_id)->update($this->tb, $obj);
        return $res;
    }

     /**
     * 任务详情
     *
     * @return array
     */
    public function TaskInfo($task_id, $select = '*')
    {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('id', $task_id)
            ->where('delete_at', "1971-01-01 00:00:00")
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 更新任务状态
     *
     * @return bool
     */
    public function UpdateTaskStatus($task_id, $fields)
    {
        $res = $this->db->where('id', $task_id)->update($this->tb, $fields);

        return $res;
    }
}
