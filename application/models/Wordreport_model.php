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
    public function updateWordReport($taskID,$status){
        return $this->db->where('task_id', $taskID)
            ->update($this->_table, array(
                'status'=>$status
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




}