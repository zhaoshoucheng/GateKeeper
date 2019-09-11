<?php

/********************************************
 * # desc:    区域数据模型
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-08-23
 ********************************************/
use Didi\Cloud\Collection\Collection;

class Opttaskresultroad_model extends CI_Model {
    private $tb = 'opt_task_result_road';

    /**
     * @var \CI_DB_query_builder
     */
    protected $db;

    /**
     * Opttaskresultroad_model constructor.
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
     * 获取结果列表
     *
     * @return array
     */
    public function ResultList($city_id, $task_id, $select = '*') {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('city_id', $city_id)
            ->where('task_id', $task_id)
            ->where('is_deleted', 0)
            ->order_by('created_at', 'DESC')
            ->limit(100)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    /**
     * 获取结果统计
     *
     * @return array
     */
    public function ResulCnt($city_id, $task_id, $cond) {
        $res = $this->db->select('count(*) as cnt')
            ->from($this->tb)
            ->where('city_id', $city_id)
            ->where('task_id', $task_id)
            ->where($cond)
            ->where('is_deleted', 0)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array()[0]['cnt'] : 0;
    }

    /**
     * 任务详情
     *
     * @return array
     */
    public function ResultTaskInfo($result_id, $select = '*') {
        $res = $this->db->select($select)
            ->from($this->tb)
            ->where('id', $result_id)
            ->where('is_deleted', 0)
            ->get();
        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
}
