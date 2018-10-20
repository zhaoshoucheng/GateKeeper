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

        $validate = Validate::make($params, [
            'city_id' => 'min:1',
            'type' => 'min:0',
            'question' => 'min:1',
        ]);

        if(!$validate['status']) {
            throw new Exception($validate['errmsg'], ERR_PARAMETERS);
        }

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