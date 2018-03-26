<?php

date_default_timezone_set('Asia/Shanghai');
class Task_model extends CI_Model
{
    private $_table = 'task_result';

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
        $task['updated_at'] = date('Y-m-d H:i:s');
        $bRet = $this->its_tool->where('id', $task_id)->update($this->_table, $task);
        return $bRet;
    }

    function updateTaskStatus($task_id, $ider, $status, $comment = null) {
        try {
            $this->its_tool->trans_begin();

            $sql = "select * from task_result where id = ? for update";
            $query = $this->its_tool->query($sql, array($task_id));
            $result = $query->result_array();
            if (empty($result)) {
                $this->its_tool->trans_rollback();
                return false;
            }

            $task = $result[0];
            $task_status = intval($task['status']);
            $task_comment = $task['task_comment'];

            $weight = pow(10, $ider);
            $bit_value = $task_status / $weight % 10;
            $task['status'] = $task_status - $bit_value * $weight + $status * $weight;
            

            if ($comment !== null) {
                if ($task_comment === null or $task_comment === '') {
                    $data[$ider][$status] = $comment;
                } else {
                    $data = json_decode($task_comment, true);
                    $data[$ider][$status] = $comment;
                }
                $task['task_comment'] = json_encode($data);
            }

            $this->updateTask($task_id, $task);
            $this->its_tool->trans_commit();
            return true;
        } catch (\Exception $e) {
            $this->its_tool->trans_rollback();
            return false;
        }
    }

    function getTask($user, $city_id, $type, $kind, $cols = '*') {
        $aRet = $this->its_tool->select($cols)->from($this->_table)->where('user', $user)->where('city_id', $city_id)->where('kind', $kind)->where('type', $type)->order_by('id', 'DESC')->get()->result_array();
        // var_dump($this->its_tool->last_query());
        return $aRet;
    }

    function getSuccTask($user, $city_id, $type, $kind, $task_type, $cols = '*') {
        $aRet  = $this->its_tool->select('task_result.id as task_id, task_result.dates as dates')->from($this->_table)->join('cycle_task', 'task_result.conf_id = cycle_task.id')->where('task_result.user', $user)->where('task_result.city_id', $city_id)->where('task_result.kind', $kind)->where('task_result.type', $type)->where('task_result.rate', 100)->where('task_result.status', $this->completed_status)->where('cycle_task.type', $task_type)->order_by('task_result.id', 'DESC')->limit(1)->get()->result_array();
        // var_dump($this->its_tool->last_query());
        return $aRet;
    }

    function process() {
        try {
            $this->its_tool->trans_begin();
            $now = time();

            // 所有超过重试次数任务设置为失败
            // $query = $this->its_tool->where('try_times > ', $this->max_try_times)->update($this->_table, ['status' => -1, 'task_end_time' => $now, 'updated_at' => $now]);

            // 取出一条待执行任务
            $query = $this->its_tool->select('*')->from($this->_table)->where('status', 0)->where('task_start_time', 0)->where('expect_try_time <=', $now)->where('try_times <=', $this->max_try_times)->limit(1)->get();
            $result = $query->result_array();
            if (empty($result)) {
                // 木有待投递任务
                $this->its_tool->trans_rollback();
                return true;
            }

            $task = $result[0];
            $task_id = $task['id'];
            $sql = "select * from task_result where id = ? and status = ? and task_start_time = ? and expect_try_time <= ? and try_times <= ? for update";
            $query = $this->its_tool->query($sql, array($task_id, 0, 0, $now, $this->max_try_times));
            $result = $query->result_array();
            if (empty($result)) {
                // 获取到的任务已经被处理了
                $this->its_tool->trans_rollback();
                return true;
            }

            $task = $result[0];
            $city_id = $task['city_id'];
            $dates = $task['dates'];
            $start_time = $task['start_time'];
            $end_time = $task['end_time'];

            $ret = $this->getDateVersion($dates);
            $ret = json_decode($ret, true);
            if ($ret['errorCode'] == -1) {
                // maptypeversion 未就绪
                if ($task['try_times'] < $this->max_try_times) {
                    $this->updateTask($task_id, array(
                        'expect_try_time' => $now + 10 * 60,
                        'try_times' => intval($task['try_times']) + 1,
                    ));
                    $content = "{$task_id} {$dates} mapversion unready.";
                    sendMail($this->to, $this->subject, $content);
                } else {
                    $this->updateTask($task_id, array(
                        'status' => -1,
                        'try_times' => intval($task['try_times']) + 1,
                        'task_end_time' => $now,
                    ));
                    $content = "{$task_id} maptypeversion unready and failed.";
                    sendMail($this->to, $this->subject, $content);
                }
                $this->its_tool->trans_commit();
                return true;
            }
            $task['dateVersion'] = $ret['data'];

            // 任务状态置为已投递
            $query = $this->its_tool->where('id', $task_id)->update($this->_table, ['task_start_time' => time()]);
            $this->its_tool->trans_commit();
            return $task;
        } catch (\Exception $e) {
            $this->its_tool->trans_rollback();
            return false;
        }
    }

    function getDateVersion($date) {
        $data = [
                    // 'date'  => '2017-06-01,2017-10-10,2018-03-10',
                    'date' => $date,
                    'token'     => $this->config->item('waymap_token'),
                ];
        $res = httpPOST($this->config->item('waymap_interface') . '/flow-duration/map/getDateVersion', $data);
        return $res;
    }
}