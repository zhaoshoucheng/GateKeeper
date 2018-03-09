<?php


class Task_model extends CI_Model
{
    private $_table = 'task_result';

    function __construct() {
        parent::__construct();
        $this->its_tool = $this->load->database('default', true);

    }

    function addTask($task) {
        $task['created_at'] = date('Y-m-d H:i:s');
        $task['updated_at'] = date('Y-m-d H:i:s');
        $bRet = $this->its_tool->insert($this->_table, $task);
        if ($bRet === false) {
            return -1;
        }
        return $this->its_tool->insert_id();
    }

    function updateTask($task_id, $task) {
        $bRet = $this->its_tool->where('id', $task_id)->update($this->_table, $task);
        return $bRet;
    }

    function getTask($user, $city_id, $type, $kind, $cols = '*') {
        $aRet = $this->its_tool->select($cols)->from($this->_table)->where('user', $user)->where('city_id', $city_id)->where('kind', $kind)->where('type', $type)->order_by('id', 'DESC')->get()->result_array();
        // var_dump($this->its_tool->last_query());
        return $aRet;
    }

    function getSuccTask($user, $city_id, $type, $kind, $task_type, $cols = '*') {
        $aRet  = $this->its_tool->select('*')->from($this->_table)->join('cycle_task', 'task_result.conf_id = cycle_task.id')->where('task_result.user', $user)->where('task_result.city_id', $city_id)->where('task_result.kind', $kind)->where('task_result.type', $type)->where('task_result.rate', 100)->where('task_result.status', 2)->where('cycle_task.type', $task_type)->order_by('cycle_task.id', 'DESC')->limit(1)->get()->result_array();
        // var_dump($this->its_tool->last_query());
        return $aRet;
    }
}