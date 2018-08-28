<?php

date_default_timezone_set('Asia/Shanghai');
class Customtask_model extends CI_Model
{
    private $_table = 'custom_task';

    function __construct()
    {
        parent::__construct();
        $this->its_tool = $this->load->database('default', true);

    }

    function addTask($task)
    {
        $task['created_at'] = date('Y-m-d H:i:s');
        $task['updated_at'] = date('Y-m-d H:i:s');
        $bRet = $this->its_tool->insert($this->_table, $task);
        if ($bRet === false) {
            return -1;
        }
        return $this->its_tool->insert_id();
    }

    // function updateTask($task_id, $task)
    // {
    //     $bRet = $this->its_tool->where('id', $task_id)->update($this->_table, $task);
    //     return $bRet;
    // }
    
    function getall() {
        $aRet = $this->its_tool->get($this->_table)->result_array();
        return $aRet;
    }

    function process() {
        try {
            $this->its_tool->trans_begin();
            // 获取所有待投递的任务
            // $query = $this->its_tool->select('*')->from($this->_table)->where('status', 0)->order_by('id')->get();
            $sql = "/*{\"router\":\"m\"}*/select * from custom_task where status = 0 order by id for update";
            $query = $this->its_tool->query($sql);
            $result = $query->result_array();
            if (empty($result)) {
                $this->its_tool->trans_rollback();
                return;
            }
            foreach ($result as $value) {
                $conf_id = $value['id'];
                // 任务状态置为已投递
                $query = $this->its_tool->where('id', $conf_id)->update($this->_table, ['status' => 1]);
                // task_result表插入一条待执行任务
                $task = [
                    'city_id' => $value['city_id'],
                    'user' => $value['user'],
                    'dates' => $value['dates'],
                    'start_time' => $value['start_time'],
                    'end_time' => $value['end_time'],
                    'type' => 2,
                    'conf_id' => $conf_id,
                    'kind' => $value['kind'],
                    'junctions' => $value['junctions'],
                    'rate' => 0,
                    'status' => 0,
                    'expect_try_time' => time(),
                    'try_times' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $query = $this->its_tool->insert('task_result', $task);
            }
            $this->its_tool->trans_commit();
        } catch (\Exception $e) {
            $this->its_tool->trans_rollback();
        }
        
    }
}