<?php

/********************************************
 * # desc:    降级model
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-09-11
 ********************************************/
class Downgrade_model extends CI_Model
{
    protected $db;
    protected $token;
    protected $userid = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function getCacheFileName($url, $method, $params)
    {
        $method = strtoupper($method);
        $url = strtoupper($url);
        ksort($params);
        $data = http_build_query($params);
        return md5($method . $url . $data) . '.json';
    }

    public function isCacheUrl($route, $method, $params)
    {
        $this->config->load('cron', TRUE);
        $checkItems = $this->config->item('checkItems', 'cron');
        //路由相同、请求类型、请求参数:都相同
        foreach ($checkItems as $item) {
            $karr1 = array_keys($item['params']);
            $karr2 = array_keys($params);
            ksort($karr1);
            ksort($karr2);
            if (strtoupper($item['url']) == strtoupper($route) &&
                $karr1 == $karr2 &&
                $item['method'] = $method
            ) {
                return true;
            }
        }
        return false;
    }

    public function getUrlCache($route, $method, $params)
    {
        $this->config->load('cron', TRUE);

        //未开通城市排除
        $downgradeCityId = isset($params['city_id']) ? intval($params['city_id']) : 0;
        $cityIds = $this->config->item('city_ids', 'cron');
        if(!in_array($downgradeCityId,$cityIds)){
            return "";
        }

        $basedir = $this->config->item('basedir', 'cron');
        $cacheFile = $this->getCacheFileName($route, $method, $params);
        try {
            if (!file_exists($basedir . $cacheFile)) {
                throw new \Exception($basedir . $cacheFile . " not exists.");
            }
            $content = file_get_contents($basedir . $cacheFile);
            return $content;
        } catch (\Exception $e) {
            com_log_warning('downgrade_model_getUrlCache', 0, $e->getMessage(), compact("cacheFile"));
        }
        return "";
    }

    public function saveOpen($params)
    {
        /*$open = intval($params["open"]);
        $expired = $params["expired"];//过期时间戳
        $notice = $params["notice"];
        $openData = [
            "open" => $open,
            "expired" => $expired,
            "notice" => $notice,
        ];*/
        $this->config->load('cron', TRUE);
        $basedir = $this->config->item('basedir', 'cron');
        $openFile = $this->config->item('open_file', 'cron');
        if (file_put_contents($basedir . $openFile, json_encode($params)) === false) {
            throw new Exception("file_put_contents {$basedir}{$openFile} failed", 1);
        }
        return true;
    }

    public function isOpen($cityId = 0)
    {
        $openInfo = $this->getOpen($cityId);
        return $openInfo['open'];
    }

    public function getOpen($cityId = 0)
    {
        $this->config->load('cron', TRUE);
        $basedir = $this->config->item('basedir', 'cron');
        $openFile = $this->config->item('open_file', 'cron');
        try {
            if (file_exists($basedir . $openFile)) {
                $openContent = file_get_contents($basedir . $openFile);
                $openInfo = json_decode($openContent, true);

                //降级城市列表
                $cityIds = [];
                if (!empty($openInfo['city_ids'])) {
                    $cityIds = explode(",", $openInfo['city_ids']);
                }

                //是否开启降级
                $downgradeFlag = 0;
                if (isset($openInfo['open']) &&
                    isset($openInfo['expired']) &&
                    $openInfo['open'] == 1 &&
                    strtotime($openInfo['expired']) > time()
                ) {

                    $downgradeFlag = 1;
                }

                //=========>城市降级判断逻辑
                //1、已降级且未设定城市时
                if($downgradeFlag && empty($cityIds)){
                    return $openInfo;
                }

                //2、已降级且设定了"降级城市列表"
                //2.1、"不带城市的接口"降级
                //2.2、"带城市的接口"且城市在"降级城市列表"中降级
                if($downgradeFlag && !empty($cityIds)){
                    if($cityId==0){
                        return $openInfo;
                    }
                    if($cityId!=0 && in_array($cityId, $cityIds)){
                        return $openInfo;
                    }
                }
                //<=========城市降级判断逻辑
            }
        } catch (\Exception $e) {
            com_log_warning('downgrade_model_getopen', 0, $e->getMessage(), compact("openFile"));
        }
        $openData = [
            "open" => 0,
            "expired" => 0,
            "notice" => '',
        ];
        return $openData;
    }
}
