<?php

date_default_timezone_set('Asia/Shanghai');
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

    function isWorkday($now) {
        $idx = date('N', $now);
        $idx = intval($idx);
        return $idx <= 5;
    }

    function process() {
        try {
            $this->its_tool->reconnect();
            $this->its_tool->trans_begin();
            $now = time();
            $today = strtotime(date('Y-m-d', $now));
            $time = date('H:i:s', $now);
            // 获取所有待投递的任务
            // $query = $this->its_tool->select('*')->from($this->_table)->where('last_exec_time <', $today)->where('expect_exec_time <', $time)->order_by('id')->get();
            $sql = "/*{\"router\":\"m\"}*/select * from cycle_task where last_exec_time < ? and expect_exec_time < ? for update";
            $query = $this->its_tool->query($sql, array($today, $time));
            $result = $query->result_array();
            if (empty($result)) {
                $this->its_tool->trans_rollback();
                return;
            }
            foreach ($result as $value) {
                com_log_notice('_its_task', $value);
                $conf_id = $value['id'];

                $dates = '';
                if ($value['type'] == 1) {
                    $dates = date('Y-m-d', $now - 86400);
                } elseif ($value['type'] == 2) {
                    $is_workday = $this->isWorkday($now);
                    for ($i=1; $i < 8; $i++) {
                        $t = $now - $i * 86400;
                        if ($is_workday == $this->isWorkday($t)) {
                            if ($dates == '') {
                                $dates .= date('Y-m-d', $t);
                            } else {
                                $dates .= ',' . date('Y-m-d', $t);
                            }
                        }
                    }
                } elseif ($value['type'] == 3) {
                    for ($i=1; $i < 5; $i++) {
                        $t = $now - $i * 7 * 86400;
                        if ($dates == '') {
                            $dates .= date('Y-m-d', $t);
                        } else {
                            $dates .= ',' . date('Y-m-d', $t);
                        }
                    }
                } else {
                    $this->its_tool->trans_rollback();
                    return;
                }
                // 任务状态置为已投递
                $query = $this->its_tool->where('id', $conf_id)->update($this->_table, ['last_exec_time' => $now]);
                // task_result表插入一条待执行任务
                $task = [
                    'city_id' => $value['city_id'],
                    'user' => $value['user'],
                    'dates' => $dates,
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
                com_log_notice('_its_task', $task);
                $query = $this->its_tool->insert('task_result', $task);
            }
            $this->its_tool->trans_commit();
        } catch (\Exception $e) {
            $this->its_tool->trans_rollback();
        }

    }
}