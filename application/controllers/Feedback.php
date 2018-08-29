<?php
/**
 * 用户反馈模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Feedback extends MY_Controller
{
    protected $types = [
        1 => '报警信息',
        2 => '指标计算',
        3 => '诊断问题',
        4 => '评估内容',
        5 => '优化结果',
        6 => '页面交互',
        0 => '其他',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('feedback_model');
    }

    public function addFeedback()
    {
        $params = $this->input->post();

        if(!isset($params['city_id']) || !is_numeric($params['city_id'])) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of city_id is wrong.';
            return;
        }

        if(!isset($params['type']) || !array_key_exists($params['type'], $this->types)) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'The value of type is wrong.';
            return;
        }

        if(!isset($params['question']) || empty(trim($params['question']))) {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = 'You must have question.';
            return;
        }

        $data = $this->feedback_model->addFeedback($params);

        return $this->response($data);
    }

    public function getTypes()
    {
        $types = [ 1 => 0 ];

        foreach ($this->types as $key => $type) { $types[$key.''] = $type; }

        $this->response($types);
    }
}