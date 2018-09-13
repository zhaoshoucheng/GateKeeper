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
        $basedir = $this->config->item('basedir', 'cron');
        $cacheFile = $this->getCacheFileName($route, $method, $params);
        try {
            if (!file_exists($basedir . $cacheFile)) {
                throw new \Exception($basedir . $cacheFile." not exists.");
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
        $open = intval($params["open"]);
        $expired = $params["expired"];//过期时间戳
        $notice = $params["notice"];
        $openData = [
            "open" => $open,
            "expired" => $expired,
            "notice" => $notice,
        ];
        $this->config->load('cron', TRUE);
        $basedir = $this->config->item('basedir', 'cron');
        $openFile = $this->config->item('open_file', 'cron');
        if (file_put_contents($basedir . $openFile, json_encode($openData)) === false) {
            throw new Exception("file_put_contents {$basedir}{$openFile} failed", 1);
        }
        return true;
    }

    public function isOpen()
    {
        $openInfo = $this->getOpen();
        return $openInfo['open'];
    }

    public function getOpen()
    {
        $this->config->load('cron', TRUE);
        $basedir = $this->config->item('basedir', 'cron');
        $openFile = $this->config->item('open_file', 'cron');
        try {
            if (file_exists($basedir . $openFile)) {
                $openContent = file_get_contents($basedir . $openFile);
                $openInfo = json_decode($openContent, true);
                if (isset($openInfo['open']) &&
                    isset($openInfo['expired']) &&
                    isset($openInfo['notice']) &&
                    $openInfo['open'] == 1 &&
                    strtotime($openInfo['expired']) > time()
                ) {
                    return $openInfo;
                }
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
