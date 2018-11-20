<?php
/********************************************
# desc:    报警分析数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-11-19
********************************************/

class Alarmanalysis_model extends CI_Model
{
    private $tb = '';
    private $db = '';

    public function __construct()
    {
        parent::__construct();

        // load config
        $this->load->config('nconf');
    }

    /**
     * 报警es查询接口
     * @param $body json 查询DSL
     * @return array
     */
    public function search($body)
    {
        $hosts = $this->config->item('alarm_es_interface');
        $client = Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();

        $index = 'its_alarm_movement_month*';

        $params = [
            'index' => $index,
            'body'  => $body
        ];

        $response = $client->search($params);
        echo "<pre>";print_r($response);
        exit;
        return $response;
    }
}