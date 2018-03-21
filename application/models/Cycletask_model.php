<?php


class Cycletask_model extends CI_Model
{
    private $_table = 'cycle_task';

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
    
    function process() {
        try {
            $this->its_tool->trans_begin();
            $now = time();
            // $today = strtotime(date('Y-m-d'), $now);
            $time = date('H:i:s', $now);
            // 获取所有待投递的任务
            // $query = $this->its_tool->select('*')->from($this->_table)->where('last_exec_time <', $today)->where('expect_exec_time <', $time)->order_by('id')->get();
            $sql = "select * from cycle_task where last_exec_time < ? and expect_exec_time < ? for update";
            $query = $this->its_tool->query($sql, array($now, $time));
            $result = $query->result_array();
            if (empty($result)) {
                $this->its_tool->trans_rollback();
                return;
            }
            foreach ($result as $value) {
                $conf_id = $value['id'];
                // 任务状态置为已投递
                $query = $this->its_tool->where('id', $conf_id)->update($this->_table, ['last_exec_time' => $now]);
                // task_result表插入一条待执行任务
                $task = [
                    'city_id' => $value['city_id'],
                    'user' => $value['user'],
                    'dates' => $value['dates'],
                    'start_time' => $value['start_time'],
                    'end_time' => $value['end_time'],
                    'type' => 1,
                    'conf_id' => $conf_id,
                    'kind' => $value['kind'],
                    'junctions' => $value['junctions'],
                    'rate' => 0,
                    'status' => 0,
                    'expect_try_time' => $now,
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