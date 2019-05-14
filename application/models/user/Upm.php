<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 16/12/5
 * Time: 12:00
 */

use \Didi\Cloud\Collection\Collection;

class Upm extends CI_Model
{
    private $_config_server = null;
    private $_config_uri = null;
    private $_appid = null;
    private $_host = null;
    public function __construct()
    {
        $this->load->helper('http');
        $this->load->config('backend/userauth', true);
        $this->_config_server = $this->config->item('upm_server');
        $this->_config_uri = $this->config->item('uri');
        $this->_appid = $this->config->item('appid');
        $this->_appkey = $this->config->item('appkey');
        $this->_host = $this->_config_server['remote_host'];
    }

    public function getUserInfo($userName){
        $params = $this->make_sign();
        $params['userName'] = $userName;
        $res = httpGET($this->_host.$this->_config_uri['upm_getUserInfo'], $params, $this->_config_server['timeout']);
        $json = $this->valid_json($res);
        if (!$json || $json['errno'] != 0 || empty($json['data'])) {
            log_message('error', 'getuserinfo failed, ret:'.$res);
            return false;
        }
        $ret = $json['data'];
        return $ret;
    }

    /**
     * 验证是否有资源访问权限
     * @param feature string 资源标识/url
     */
    public function isValidFeature($userName, $uri)
    {
        $params = $this->make_sign();
        $params['userName'] = $userName;
        $params['feature'] = $uri;
        $ret = httpGET($this->_host.$this->_config_uri['upm_isValidFeature'] , $params);
        $json = $this->valid_json($ret);
        if (!$json || $json['code'] != 200) {
            return FALSE;
        }
        return $json['data'];
    }

    public function getUserAreas($userName) {
        $params = $this->make_sign();
        $params['userName'] = $userName;
        $url = $this->_host.$this->_config_uri['upm_getUserAreas'];
        $ret = httpGET($url, $params);
        $json = $this->valid_json($ret);
        if (!$json || $json['code'] != 200) {
            return FALSE;
        }
        return array(
            'citys' => $json['data']['areas']
        );
    }


    public function getUserMenus($userName) {
        if(ENVIRONMENT=="development"){
            $userName = "18953101270";
        }
        $params = $this->make_sign();
        $params['userName'] = $userName;
        $url = $this->_host.$this->_config_uri['upm_getMenuList'];
        $ret = httpGET($url, $params);
        $json = $this->valid_json($ret);
        if (!$json || $json['code'] != 200) {
            return FALSE;
        }
        return $json['data'];
    }

    public function getUserPermissions($userName) {
        if(ENVIRONMENT=="development"){
            $userName = "18953101270";
        }
        $params = $this->make_sign();
        $params['userName'] = $userName;
        $url = $this->_host.$this->_config_uri['upm_getUserAreas'];
        $ret = httpGET($url, $params);
        $json = $this->valid_json($ret);
        if (!$json || $json['code'] != 200) {
            return FALSE;
        }
        return $json['data'];
    }

    /**
     * 验证flag权限
     * @param $userName
     * @param $flag
     * @return bool
     */
    public function hasPermissionByFlag($userName,$flag) {
        if(ENVIRONMENT=="development"){
            $userName = "18953101270";
        }
        $params = $this->make_sign();
        $params['userName'] = $userName;
        $url = $this->_host.$this->_config_uri['upm_getUserAreas'];
        $ret = httpGET($url, $params);
        $json = $this->valid_json($ret);
        if (!$json || $json['code'] != 200) {
            return FALSE;
        }
        foreach ($json['data']['flags'] as $value){
            if($value["name"]==$flag){
                return true;
            }
        }
        return false;
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

    function make_sign()
    {
        $params = array();
        $params['appId'] = $this->_appid;
        $params['appKey'] = $this->_appkey;
        $params['time'] = time();
        $params['sign'] = md5($this->_appid . $this->_appkey . $params['time']);
        return $params;
    }
}
