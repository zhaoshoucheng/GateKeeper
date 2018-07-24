<?php
/***************************************************************
# 干线绿波回调函数
# user:ningxiangbing@didichuxing.com
# date:2018-07-24
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialgreenwavecallback extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('redis_model');
    }

    /**
    * 将数据写入redis
    * @param data  数据
    * @param token 唯一标识
    */
    public function fillData()
    {
        $params = $this->input->post();
        $content = "form_data : " . ' token = ' . $params['token'] . ' && data = ' . $params['data'];
        sendMail('ningxiangbing@didichuxing.com', 'logs: 干线绿波结果存储传参', $content);

        if (!empty($params['data']) && !empty($params['token'])) {
            $res = $this->redis_model->setData($params['token'], $params['data']);
            if (!$res) {
                $content = "form_data : " . json_encode($params);
                $content .= '<br> result : ' . json_encode($res);
                sendMail('ningxiangbing@didichuxing.com', 'logs: 干线绿波结果存储失败', $content);
            }
        } else {
            $this->errno = ERR_PARAMETERS;
            $this->errmsg = '参数token或data为空！token = ' . $params['token'] . ' && data = ' . $params['data'];
            return;
        }

        return $this->response(['success.']);
    }

    public function getData()
    {
        $params = $this->input->post();
        $res = $this->redis_model->getData($params['token']);
        echo "<pre>";print_r($res);
        var_dump($res);
    }

    public function testsadd()
    {
        $key = 'arterialgreenwaveopt';
        $token = md5(mt_rand(100, 100000));

        $res = $this->redis_model->sadd($key, $token);

        return $this->response(['success.']);
    }

    public function testsmembers($key)
    {
        $key = 'arterialgreenwaveopt';

        $res = $this->redis_model->smembers($key);

        echo "<pre> res = ";print_r($res);exit;
    }
}