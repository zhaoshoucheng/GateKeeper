<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/11/26
 * Time: 下午5:41
 */

class Demo extends MY_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('http');
    }

    /*http://data.sts.didichuxing.com/signal-map/mapJunction/suggest?keyword=%E6%96%87%E5%8C%96&type=didi&city_id=12&token=0faa6ca90df19d26635391c511d124a1&user_id=roadNet*/
    public function mapJunctionSuggest(){
        $params = $this->input->get();
        $params['token']="0faa6ca90df19d26635391c511d124a1";
        $params['user_id']="roadNet";
        $Url = 'http://100.69.238.11:8000/its/signal-map/mapJunction/suggest';
        $ret = httpGET($Url,$params);
        $finalRet = json_decode($ret,true);
        $this->response($finalRet['data']);

    }

    public function getShortUrl(){
        $params = $this->input->post();

        $url = "http://100.69.238.11:8000/daijia/shortserver/admin/add";
        $data = array();
        $data['appkey'] = "oveS7s8f3DymeHjnrUy0lfqBW1x1n3KD";
        $data['url']  = $params['url'];
        $ret  = httpPOST($url,$data);
        $finalRet = json_decode($ret,true);
        $this->response($finalRet['short_url']);
    }
}