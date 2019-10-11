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

    public function test2(){
        $Url = 'http://100.90.164.31:8001/signal-map/mapJunction/polygon';
        $data = [];
        $data["city_id"] = "5";
        $data["version"] = "2019093018";
        $data["polygon"] = "120.19832611083984,30.260030976266417;120.16802787780762,30.217393512825154;120.18519401550293,30.190021644057804;120.237036,30.217690;120.2239465713501,30.25250588146598;120.2118444442749,30.25921547660607";
        $ret = httpPOST($Url,$data);
        // print_r($ret);exit;
        $jsonData="";
        $arr = json_decode($ret,true);
        // print_r($arr["data"]["dataList"]);
        // array_column(input, column_key);
        $junctions = array_keys($arr["data"]["filter_juncs"]);
        foreach ($junctions as $junctionid) {
            echo "INSERT INTO `area_junction_relation` (`id`, `area_id`, `junction_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '137', '".$junctionid."', '0', '2019-10-10 10:28:40', '2019-10-10 10:28:40', '1970-01-01 00:00:00');<br/>";
        }
        exit;

    }

    public function test(){

        $arr = json_decode($jsonData,true);
        // print_r($arr["data"]["dataList"]);
        // array_column(input, column_key);
        $junctions = array_column($arr["data"]["dataList"], "logic_junction_id");
        foreach ($junctions as $junctionid) {
            echo "INSERT INTO `area_junction_relation` (`id`, `area_id`, `junction_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '127', '".$junctionid."', '0', '2018-08-30 21:29:40', '2018-08-30 21:29:40', '1970-01-01 00:00:00');<br/>";
        }
        exit;
        // print_r($junctions);exit;
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