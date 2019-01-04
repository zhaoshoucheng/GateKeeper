<?php
/***************************************************************
# 干线绿波回调函数
# user:ningxiangbing@didichuxing.com
# date:2018-07-24
***************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Arterialgreenwavecallback extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Shanghai');
        $this->load->model('junction_model');
        $this->load->model('redis_model');
        $this->load->config('nconf');
        $this->load->model('traj_model');
    }

    /**
    * 将数据写入redis
    * @param data  数据
    * @param key   唯一标识
    */
    public function fillData()
    {
        $params = $this->input->post(NULL, TRUE);
        $result = $this->traj_model->fillData($params);
        return $this->response($result);

        $params = $this->input->post(NULL, TRUE);

        if (!empty($params['data']) && !empty($params['key'])) {
            $res = $this->redis_model->setData($params['key'], $params['data']);
            if (!$res) {
                $return = ['errno'=>100400, 'errmsg'=>'result:'.json_encode($res) . 'params:' . json_encode($params), 'data'=>['']];
                $content = "form_data : " . json_encode($params);
                $content .= '<br> result : ' . json_encode($res);
                sendMail('ningxiangbing@didichuxing.com', 'logs: 干线绿波结果存储失败', $content);
                echo json_encode($return);
                exit;
            }
        } else {
            $return = ['errno'=>100400, 'errmsg'=>'key 或 data 为空', 'data'=>['']];
            echo json_encode($return);
            exit;
        }

        $result = ['errno'=>0, 'errmsg'=>'', 'data'=>['success']];
        echo json_encode($result);
        exit;
    }

    public function getData()
    {
        $params = $this->input->post();
        $res = $this->redis_model->getData($params['key']);
        $res = json_decode($res, true);
        echo "<pre>";print_r($res);
    }


    public function testsmembers()
    {
        $key = 'ArterialGreenWaveExecutingKeyList';

        $res = $this->redis_model->smembers($key);

        echo "<pre> res = ";print_r($res);exit;
    }

    public function delList()
    {
        $key = 'ArterialGreenWaveExecutingKeyList';
        $this->redis_model->delList($key);
    }
}