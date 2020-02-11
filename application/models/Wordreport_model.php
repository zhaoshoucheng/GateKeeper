<?php

class Wordreport_model extends CI_Model{

    private $_table = 'word_report';

    public function __construct(){
        parent::__construct();
        $this->db = $this->load->database('default', true);
    }

    /*
     * 保存数据入库
     * city_id 城市id
     * title word标题
     * type 报告类型
     * time_range报告分析日期
     * file_path 文件保存路径
     * task_id 任务id
     * status 任务状态
     * user_info 提交用户
     * */
    public function updateWordReport($taskID,$filePath,$status){
        return $this->db->where('task_id', $taskID)
            ->update($this->_table, array(
                'status'=>$status,
                'file_path'=>$filePath
            ));
    }

    /*
     * 新建生成报告任务
     * */
    public function createWordReport($data){
        $ret = $this->db->insert($this->_table,$data);
        return $ret;
    }

    public function queryWordReport($taskID){
        $ret = $this->db->from($this->_table)->where('task_id',$taskID)->get()->result_array();
        return $ret;
    }

    /**
     * @param $cityId
     * @param $type
     * @param $pageNum
     * @param $pageSize
     *
     * @return array
     */
    public function getCountUploadFile($cityId, $type, $pageNum, $pageSize)
    {
        $res = $this->db->select('count(*) as num')
            ->from('word_report')
            ->where('deleted_at', "1970-01-01 00:00:00")
            ->where('city_id', $cityId)
            ->where('type', $type)
            ->get();
        return $res instanceof CI_DB_result ? $res->row_array() : $res;
    }

    /**
     * @param $cityId
     * @param $type
     * @param $pageNum
     * @param $pageSize
     * @param $namespace
     *
     * @return array
     */
    public function getSelectUploadFile($cityId, $type, $pageNum, $pageSize)
    {
        $res = $this->db->select('title, create_at, time_range, file_path')
            ->from('word_report')
            ->where('deleted_at', "1970-01-01 00:00:00")
            ->where('city_id', $cityId)
            ->where('type', $type)
            ->order_by('id', 'DESC')
            ->limit($pageSize, ($pageNum - 1) * $pageSize)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
}