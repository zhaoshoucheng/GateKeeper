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
 * @property \Feedback_model $feedback_model
 */
class FeedbackService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('feedback_model');
        $this->load->config('nconf');
        $this->load->helper('http_helper');
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

    /**
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function handleOptFeedback($params)
    {
        $params['description'] = $params['description'] ?? '';

        $mysqlData = [
            'city_id'     => $params['city_id'],
            'user_id'     => get_instance()->username,
            'isSatisfy'   => $params['isSatisfy'],
            'description' => $params['description'],
        ];

        $res = $this->feedback_model->insert('opt_feedback', $mysqlData);
        if (!$res) {
            throw new \Exception('优化方案反馈入库失败', ERR_DATABASE);
        }

        $data = [
            'msgtype' => 'text',
            'text' => [
                'content' => "结果：" . ($params['isSatisfy'] == 1 ? '合理' : '不合理')
                    . "; 反馈：" . ($params['description'] ? $params['description'] : '空'),
            ],
            'at' => [
                'atMobiles' => [
                    '13114526633',
                ],
                'isAtAll' => false,
            ],
        ];
        $url = "https://oapi.dingtalk.com/robot/send";
        $url .= "?access_token=f9947bd6e25c7ee0264108e242999a89d425e347eaea257e9e99405c54cab97f";
        httpPOST($url, $data, 0, 'json');
        return [];
    }

    /**
     * @param $params
     *
     * @return bool
     * @throws \Exception
     */
    public function insertFeedback($params)
    {
        $params['description'] = $params['desc'] ?? '';

        if (isset($params['desc'])) {
            unset($params['desc']);
        }

        $mysqlData = [
            'city_id'     => $params['city_id'],
            'user_id'     => get_instance()->username,
            'type'        => $params['type'],
            'question'    => $params['question'],
            'description' => $params['description'],
        ];

        $res = $this->feedback_model->insert('user_feedback', $mysqlData);
        if (!$res) {
            throw new \Exception('用户反馈创建失败', ERR_DATABASE);
        }

        return $res;
    }
}
