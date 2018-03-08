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
}