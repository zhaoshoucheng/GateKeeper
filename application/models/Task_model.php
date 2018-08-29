<?php

date_default_timezone_set('Asia/Shanghai');
class Task_model extends CI_Model
{
    private $_table = 'task_result';

    private $max_try_times = 20;
    private $completed_status = 11;

    private $to = 'lizhaohua@didichuxing.com';
    private $subject = 'task scheduler';

    private $failed_status = [2, 12, 22, 20, 21, 22];
    private $run_status = [0, 1, 10, 11];
    private $success_status = [11];

    const TASK_TYPE_CYCLE   = 1;    //周期任务
    const TASK_TYPE_CUSTOM  = 2;    //自定义任务

    function __construct() {
        parent::__construct();
        $this->its_tool = $this->load->database('default', true);

        $this->load->helper('mail');
        $this->load->helper('http');
        $this->load->config('nconf');
    }

    public function getSuccessStatus(){
        return $this->success_status;
    }

    public function getRunStatus(){
        return $this->run_status;
    }

    function addTask($task) {
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

    function updateTaskStatus($task_id, $ider, $status, $comment = null) {
        try {
            $this->its_tool->reconnect();
            $this->its_tool->trans_begin();

            $sql = "/*{\"router\":\"m\"}*/select * from task_result where id = ? for update";
            $query = $this->its_tool->query($sql, array($task_id));
            $result = $query->result_array();
            if (empty($result)) {
                $this->its_tool->trans_rollback();
                return false;
            }

            $task = $result[0];
            $task_status = intval($task['status']);
            $task_comment = $task['task_comment'];


            // 如果已经失败了，但是状态更新不是失败，不更新
            if (in_array($task_status, $this->failed_status) and $status != 2) {
                $this->its_tool->trans_rollback();
                return false;
            }

            $weight = pow(10, $ider);
            $bit_value = $task_status / $weight % 10;
            $task['status'] = $task_status - $bit_value * $weight + $status * $weight;
            // 如果comment为空，不更新task_comment
            if ($comment != '' and $comment != null) {
                $task['task_comment'] = $comment;
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

    function getSuccCycleTask($user, $city_id, $cols = '*') {
        $aRet = $this->its_tool->select($cols)->from($this->_table)->where('user', $user)->where('city_id', $city_id)->where('type', self::TASK_TYPE_CYCLE)->where('rate', '100')->where_in('status', $this->success_status)->order_by('id', 'DESC')->get()->result_array();
        return $aRet;
    }

    function getSuccTask($user, $city_id, $type, $kind, $task_type, $cols = '*') {
        $aRet  = $this->its_tool->select('task_result.id as task_id, task_result.dates as dates')->from($this->_table)->join('cycle_task', 'task_result.conf_id = cycle_task.id')->where('task_result.user', $user)->where('task_result.city_id', $city_id)->where('task_result.kind', $kind)->where('task_result.type', $type)->where('task_result.rate', 100)->where('task_result.status', $this->completed_status)->where('cycle_task.type', $task_type)->order_by('task_result.id', 'DESC')->limit(1)->get()->result_array();
        // var_dump($this->its_tool->last_query());
        return $aRet;
    }

    /*
     * 根据id获取任务
     */
    public function getTaskById($taskId, $cols = '*')
    {
        return $this->its_tool->select($cols)->from($this->_table)->where('id', $taskId)->get()->first_row('array');
    }

    /*
     * 获取一个任务的相关任务
     */
    public function getDayCycleTaskSummary($user, $cityId, $date)
    {
        return $this->its_tool
            ->select('task_result.id as task_id, task_result.dates as dates, cycle_task.type as cycleType, task_result.status as status, task_result.task_comment as task_comment, task_result.rate as rate')
            ->from($this->_table)
            ->join('cycle_task', 'task_result.conf_id = cycle_task.id')
            ->where('task_result.user', $user)
            ->where('task_result.city_id', $cityId)
            ->where('task_result.created_at > ', "{$date} 00:00:00")
            ->where('task_result.created_at <= ', "{$date} 23:59:59")
            ->where('task_result.kind', 2)
            ->where('task_result.type', 1)
            ->get()
            ->result_array();
    }


    function process() {
        try {
            $this->its_tool->reconnect();
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
            $sql = "select * from task_result where id = ? for update";
            $query = $this->its_tool->query($sql, array($task_id));
            $result = $query->result_array();
            if (empty($result)) {
                // 获取到的任务已经被处理了
                $this->its_tool->trans_rollback();
                return true;
            }

            $task = $result[0];
            //已经执行过
            if($task['status']!=0 ||$task['task_start_time']!=0 ||$task['expect_try_time']>$now || $task['try_times']>$this->max_try_times){
                $this->its_tool->trans_rollback();
                return true;
            }

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
            $dateVersion = $ret['data'];
            foreach ($dateVersion as $date => $version) {
                if ($version == '') {
                    $this->updateTask($task_id, array(
                        'expect_try_time' => $now + 10 * 60,
                        'try_times' => intval($task['try_times']) + 1,
                    ));
                    $content = "{$task_id} {$dates} mapversion unready.";
                    sendMail($this->to, $this->subject, $content);
                    $this->its_tool->trans_commit();
                    return true;
                }
            }
            $task['dateVersion'] = $dateVersion;

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
                    'user_id'   => $this->config->item('waymap_userid'),
                ];
        $res = httpPOST($this->config->item('waymap_interface') . '/signal-map/map/getDateVersion', $data);
        return $res;
    }
}
