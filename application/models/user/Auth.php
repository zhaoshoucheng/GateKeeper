<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 16/12/5
 * Time: 12:00
 */

class Auth extends CI_Model
{
    private $_config_server = null;
    private $_config_uri = null;
    private $_appid = null;
    private $_host = null;
    private $_map = array();
    public function __construct()
    {
        $this->load->helper('http');
        $this->load->config('backend/userauth', true);
        $this->_config_server = $this->config->item('sso_server', 'backend/userauth');
        $this->_config_uri = $this->config->item('uri', 'backend/userauth');
        $this->_appid = $this->config->item('appid', 'backend/userauth');
        $this->_appkey = $this->config->item('appkey', 'backend/userauth');
        $this->_host = $this->_config_server['remote_host'];
    }
    /**
     * 请求登录
     */
    public function getLoginUrl()
    {
        return $this->_host . $this->_config_uri['login'] . '?app_id=' . $this->_appid;
        //return $this->_host . $this->_config_uri['login'] . '?app_id=' . $this->_appid . '&jumpto='.urlencode($callback);
    }

    /**
     * 用户是否登录
     * @param
     */
    public function isValidticket($ticket, $username)
    {
        $ret = httpPOST($this->_host.$this->_config_uri['ticketCheck'], array('ticket' => $ticket));
        $json = $this->valid_json($ret);
        if (!$json || $json['errno'] != 0) {
            return FALSE;
        }
        return TRUE;
    }
    /**
     * 验证接口响应数据
     * @param json array 接口响应数据
     * @return bool
     */
    private function valid_json($ret)
    {
        $json = json_decode($ret, true);
        if (!$json || !is_array($json)) {
            return FALSE;
        }
        if (!isset($json['errno'])){
            $json['errno'] = 0;
        }
        return $json;
    }



    // -----------------分割线，以下都是sso的权限验证接口-----------------------
    /**
     * 验证是否有资源访问权限
     * @param feature string 资源标识/url
     */
    public function isValidFeature($ticket, $uri)
    {
        $ret = httpPOST($this->_host.$this->_config_uri['isValidFeature'] , array('access_token' => $ticket, 'feature' => $uri));
        $json = $this->valid_json($ret);
        if (!$json || $json['errno'] != 0) {
            return FALSE;
        }
        return TRUE;
    }

    public function getUserFeatureAndArea($ticket, $username)
    {
        $ret = httpPOST($this->_host.$this->_config_uri['getUserFeatureAndArea'] , array('access_token' => $ticket, 'username' => $username, 'app_id' => $this->_appid));
        $json = $this->valid_json($ret);
        if (!$json || $json['errno'] != 0) {
            return FALSE;
        }
        return $json['data'];
    }

    public function getCityAuth($ticket, $username, $treeid = 0) {
        if (!isset($this->_map['cityAuth'])) {
            $ret = httpPOST($this->_host.$this->_config_uri['getCityAuth'] , array('access_token' => $ticket, 'username' => $username, 'app_id' => $this->_appid));
            $json = $this->valid_json($ret);
            if (!$json || $json['errno'] != 0) {
                return FALSE;
            }
            $this->_map['cityAuth'] = $json['data'];
        }
        MyLog::debug("get city auth - user:{$username} - token:{$ticket} - app_id:{$this->_appid} - city_auth:" . json_encode($this->_map));
        return $this->_map['cityAuth'];
    }

}
