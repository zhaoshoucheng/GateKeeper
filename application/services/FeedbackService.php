<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午5:40
 */

namespace Services;

/**
 * Class FeedbackService
 * @package Services
 *
 * @property \Feedback_model $feedback_model
 */
class FeedbackService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('feedback_model');
        $this->load->config('nconf');
    }

    /**
     * 获取用户反馈类型
     *
     * @return array
     */
    public function getTypes()
    {
        $types = $this->config->item('user_feedback_types');

        $result = [1 => 0];

        foreach ($types as $key => $type) {
            $result[$key . ''] = $type;
        }

        return $result;
    }

    public function insertFeedback($params)
    {
        $params['description'] = $params['desc'] ?? '';
        $params['user_id'] = get_instance()->username;

        if (isset($params['desc'])) {
            unset($params['desc']);
        }

        $res = $this->feedback_model->insertFeedback($params);

        if (!$res) {
            throw new \Exception('用户反馈创建失败', ERR_DATABASE);
        }

        return $res;
    }
}