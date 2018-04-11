<?php

date_default_timezone_set('Asia/Shanghai');
class Taskdateversion_model extends CI_Model
{
    private $_table = 'task_date_version';

    private $max_try_times = 20;
    private $completed_status = 11;

    private $to = 'lizhaohua@didichuxing.com';
    private $subject = 'task scheduler';

    function __construct() {
        parent::__construct();
        $this->its_tool = $this->load->database('default', true);

        $this->load->helper('mail');
        $this->load->helper('http');
        $this->load->config('nconf');
    }

    function insert_batch($task_dates) {
        // $task['created_at'] = date('Y-m-d H:i:s');
        // $task['updated_at'] = date('Y-m-d H:i:s');
        if (empty($task_dates)) {
            return 0;
        }
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');
        foreach ($task_dates as &$task_date) {
            $task_date['created_at'] = $created_at;
            $task_date['updated_at'] = $updated_at;
        }
        $bRet = $this->its_tool->insert_batch($this->_table, $task_dates);
        if ($bRet === false) {
            return -1;
        }
        return $bRet;
    }

    function select($task_id, $dates) {
        $bRet = $this->its_tool->select('*')->from($this->_table)->where('task_id', $task_id)->where_in('date', $dates)->get()->result_array();
        return $bRet;
    }

    function delete($task_id) {
        $bRet = $this->its_tool->delete($this->_table, ['task_id' => $task_id]);
    }
}