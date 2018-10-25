<?php
/**
 * 用户反馈模块
 */

defined('BASEPATH') OR exit('No direct script access allowed');

use Services\FeedbackService;

class Feedback extends MY_Controller
{
    /**
     * @var FeedbackService
     */
    protected $feedbackService;

    public function __construct()
    {
        parent::__construct();

        $this->feedbackService = new FeedbackService();
    }

    /**
     * 创建用户反馈
     *
     * @throws Exception
     */
    public function addFeedback()
    {
        $params = $this->input->post(null, true);

        $this->validate([
            'city_id' => 'required|is_natural_no_zero',
            'type' => 'required|in_list[1,2,3,4,5,6,7]',
            'question' => 'required|trim|min_length[1]'
        ]);

        $data = $this->feedbackService->insertFeedback($params);

        $this->response($data);
    }

    /**
     * 获取用户反馈类型
     */
    public function getTypes()
    {
        $result = $this->feedbackService->getTypes();

        $this->response($result);
    }
}