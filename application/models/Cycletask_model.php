<?php


class Cycletask_model extends CI_Model
{
    private $_table = 'cycle_task_conf';

    function __construct()
    {
        parent::__construct();
        $this->its_tool = $this->load->database('default', true);

    }

    function addTask($task)
    {
        $task['created_at'] = date('Y-m-d H:i:s');
        $task['updated_at'] = date('Y-m-d H:i:s');
        $ret = $this->its_tool->insert($this->_table, $task);
        return $ret;
    }
}