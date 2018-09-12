<?php

/********************************************
 * # desc:    降级model
 * # author:  niuyufu@didichuxing.com
 * # date:    2018-09-11
 ********************************************/
class Downgrade_model extends CI_Model
{
    protected $token;
    protected $userid = '';
    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }
        $this->load->config('nconf');
        $this->load->helper('http');
        $this->token = $this->config->item('waymap_token');
        $this->userid = $this->config->item('waymap_userid');

        $this->config->load('realtime_conf');
        $this->load->model('waymap_model');
    }

    public function getCacheFileName($method, $url, $params)
    {
        $method = strtoupper($method);
        $url = strtoupper($url);
        ksort($params);
        $data = http_build_query($params);
        return md5($method . $url . $data) . '.json';
    }

    public function saveOpen($params){
        $open = intval($params["open"]);
        $expired = $params["expired"];//过期时间戳
        $notice = $params["notice"];
        $openData = [
            "open"=>$open,
            "expired"=>$expired,
            "notice"=>$notice,
        ];
        $this->config->load('cron', TRUE);
        $basedir = $this->config->item('basedir', 'cron');
        $openFile = $this->config->item('open_file', 'cron');
        if (file_put_contents($basedir . $openFile, json_encode($openData)) === false) {
            throw new Exception("file_put_contents {$basedir}{$openFile} failed", 1);
        }
        return true;
    }

    public function isOpen(){
        $openInfo = $this->getOpen();
        return $openInfo['open'];
    }

    public function getOpen(){
        $this->config->load('cron', TRUE);
        $basedir = $this->config->item('basedir', 'cron');
        $openFile = $this->config->item('open_file', 'cron');
        $openContent = file_get_contents($basedir . $openFile);
        $openInfo = json_decode($openContent,true);
        if(isset($openInfo['open']) &&
            isset($openInfo['expired']) &&
            isset($openInfo['notice']) &&
            strtotime($openInfo['expired'])>time() &&
            $openInfo['open']==1
        ){
            return $openInfo;
        }
        $openData = [
            "open"=>0,
            "expired"=>0,
            "notice"=>'',
        ];
        return $openData;
    }
}
