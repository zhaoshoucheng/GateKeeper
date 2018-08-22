<?php

/**
 * @description public internal essential sdk package~
 * @author by rico<liuruizhou@didichuxing.com>
 * @version 2015-8.25
 *
 */

class auth_user {

    private $_tokenIndex = null;
    private $_token = null;
    private $_userName = null;
    private $_authClient = null;
    private $_map = array();
    private static $_instance = array();

    /*public static function init($code, $authClient) {
        if (!$code) {
            throw new \Exception('auth code cannot be empty~!', 0);
        }
        if (!($authClient instanceof auth_client)) {
            throw new Exception('bad auth_client', 0);
        }
        $instance = new self($authClient);
        $instance->_init($code, $authClient);
        return $instance;
    }*/

    public static function instance($username, $token, $client) {
        if (!($client instanceof auth_client)) {
            throw new Exception('bad auth_client', 0);
        }
        if (!$username) {
            throw new Exception('username cannot be empty!', 0);
        }
        if (!$token) {
            throw new Exception('token cannot be empty!', 0);
        }
        if (!isset(self::$_instance[$username])) {
            self::$_instance[$username] = new self($client);
        }
        self::$_instance[$username]->_userName = $username;
        self::$_instance[$username]->_token = $token;
        return self::$_instance[$username];
    }

    protected function __construct($authClient) {
        $this->_authClient = $authClient;
    }

    public function getToken() {
        return $this->_token;
    }

    /*private function _init($code, $authClient) {

        $args = array('all' => 1, 'code' => $code);

        $ret = $this->_authClient->request('auth/api/token/index', $args, 'post');
        $this->_tokenIndex = array();
        if ($ret) {
            $this->_tokenIndex = $ret;
            $this->_token = $this->_tokenIndex['access_token'];
            $this->_userName = $this->_tokenIndex['user']['username'];
            if (isset($this->_tokenIndex['features'])) {
                $this->_map['features'] = $this->_tokenIndex['features'];
            }
            if (isset($this->_tokenIndex['subordinates'])) {
                $this->_map['subOrdinatess'] = $this->_tokenIndex['subordinates'];
            }
            if (isset($this->_tokenIndex['flag_options'])) {
                $this->_map['flag'] = $this->_tokenIndex['flag_options'];
            }
            self::$_instance[$this->_authClient->getAppid()] = &$this;
        } else {
            $this->_token = null;
        }
        self::$_instance[$this->_userName] = &$this;
    }*/

    public function getAppid() {
        return $this->_appid;
    }

    public function getUid() {
        return $this->_tokenIndex['user']['id'];
    }

    public function getUsername() {
        return $this->_userName;
    }

    public function getZhName() {
        return $this->_tokenIndex['user']['username_zh'];
    }

    public function getEmail() {
        return $this->_tokenIndex['user']['email'];
    }

    public function getPhone() {
        return $this->_tokenIndex['user']['phone'];
    }

    public function getRoles() {
        return $this->_tokenIndex['user']['roles'];
    }

    public function isCurrentSystemAdmin() {
        return $this->_tokenIndex['is_admin'];
    }

    private function _getCommonRequestArgs() {
        return array(
            'access_token' => $this->_token,
            'username' => $this->_userName,
            'app_id' => $this->_authClient->getAppid(),
        );
    }

    public function getFeatureList($sort=1) {
        if (!isset($this->_map['featureList'])) {
            $this->_map['featureList'] = $this->_getFeatureData($sort);
        }
        return $this->_map['featureList'];
    }

    public function getFeatureUrls($sort=1) {
        if (!isset($this->_map['featureUrls'])) {
            $tmp = $this->_getFeatureData($sort);
            if ($tmp && is_array($tmp)) {
                foreach ($tmp as $v) {
                    $this->_map['featureUrls'][] = $v['url'];
                }
            } else {
                $this->_map['featureUrls'] = array();
            }
        }
        return $this->_map['featureUrls'];
    }

    public function checkUrlAuth($url, $strickMatch = true) {
        $authUrls = $this->getFeatureUrls();
        if (!$authUrls) {
            return false;
        }
        $authUrls = array_flip($authUrls);
        if (isset($authUrls[$url])) {
            return true;
        }
        return false;
    }

    private function _getFeatureData($sort=1) {

        if (!isset($this->_map['features'])) {
            $args = $this->_getCommonRequestArgs();
            $args['sort'] = $sort;
            $ret = $this->_authClient->request('auth/api/user/features', $args, 'post');
            if ($ret) {
                $this->_map['features'] = $ret['features'];
            } else {
                $this->_map['features'] = false;
            }
        }
        return $this->_map['features'];
    }

    public function getSubordinates() {
        if (!isset($this->_map['subOrdinatess'])) {
            $args = $this->_getCommonRequestArgs();
            $ret = $this->_authClient->request('auth/api/leadership/subordinates', $args, 'post');
            if ($ret) {
                $this->_map['subOrdinatess'] = $ret;
            } else {
                $this->_map['subOrdinatess'] = false;
            }
        }
        return $this->_map['subOrdinatess'];
    }

    public function getFlagList() {

        if (!isset($this->_map['flag'])) {
            $this->_map['flag'] = $this->_getFlagList();
        }
        return $this->_map['flag'];
        /*
         * 这个地方后面会分配成唯一接口的处理的
          if (!$this->_map['flags']) {
          $url = $this->_authClient->url('auth/api/flag/index');
          $args = $this->_getCommonRequestArgs();
          $ret = $this->_authClient->request($url, $args, 'post');
          if ($ret) {
          $this->_map['flags'] = $ret;
          } else {
          $this->_map['flags'] = false;
          }
          }
          return $this->_map['flags'];
         *
         */
    }

    public function getFlagOptionList() {

        if (!isset($this->_map['flagOption'])) {
            $args = $this->_getCommonRequestArgs();
            $this->_map['flagOption'] = $this->_authClient->request('auth/api/flag/index', $args, 'post');
        }
        return $this->_map['flagOption'];
    }

    public function hasFlagOption($flag_key, $option_key) {
        $args = $this->_getCommonRequestArgs();
        $args['flag_key'] = $flag_key;
        $args['option_key'] = $option_key;

        $rs = $this->_authClient->request('auth/api/flag/has', $args, 'post');
        return $rs;
    }

    private function _getFlagList() {

        if (!isset($this->_map['flag'])) {
            $args = $this->_getCommonRequestArgs();
            $ret = $this->_authClient->request('auth/api/flag/user', $args, 'post');
            if ($ret) {
                $this->_map['flag'] = $ret['flag'];
            } else {
                $this->_map['flag'] = false;
            }
        }
        return $this->_map['flag'];
    }

    public function getCityAuth($treeid = 0) {
        if (!isset($this->_map['cityAuth'])) {
            $args = $this->_getCommonRequestArgs();
            if ($treeid) {
                $args['tree_id'] = $treeid;
            }
            $this->_map['cityAuth'] = $this->_authClient->request('auth/api/city/owner', $args, 'post');
        }
        return $this->_map['cityAuth'];
    }

    public function getAllCityList($id = null, $parent = null, $level = null, $aid = null) {
        if (!isset($this->_map['allCity'])) {
            $args = $this->_getCommonRequestArgs();
            if ($id !== null) {
                $args['id'] = $id;
            }
            if ($parent !== null) {
                $args['parent'] = $parent;
            }
            if ($level !== null) {
                $args['level'] = $level;
            }
            if ($aid !== null) {
                $args['aid'] = $aid;
            }
            $ret = $this->_authClient->request('auth/api/city/index', $args, 'post');
            if ($ret) {
                $this->_map['allCity'] = $ret;
            } else {
                $this->_map['allCity'] = false;
            }
        }
        return $this->_map['allCity'];
    }

}

class auth_client {

    const exception_init = -1;
    const exception_request = -2;
    const exception_data = -3;
    const domain = "mis.diditaxi.com.cn";  //qq机房内网：10.231.158.59:8000; eb机房内网:10.121.72.44:8000 域名：mis.diditaxi.com.cn
    const domain_test = 'mis-test.diditaxi.com.cn'; //host:北京机房10.10.38.4 请使用域名访问
    const ua = 'auth_client';
    const version = '1.0';

    private $_devMode = false;
    private $_devHost = null;
    private $_appid = null;

    public function __construct($appid, $devMode = false) {
        if (!$appid) {
            throw new \Exception('appid cannot be empty for auth_user init~!', self::exception_init);
        }
        $this->_appid = $appid;
        $this->_devMode = $devMode;
    }

    public function setDevHost($host) {
        $this->_devHost = $host;
    }

    public function getAppid() {
        return $this->_appid;
    }

    public function url($url) {
        if (!$this->_devMode) {
            $host = self::domain;
        } else {
            if ($this->_devHost) {
                $host = $this->_devHost;
            } else {
                $host = self::domain_test;
            }
        }
        $ret = 'http://' . $host . '/' . $url;
        return $ret;
    }

    public function request($url, $args, $method = 'get', $timeout = 3) {
        $target = $this->url($url);
        $method = strtoupper($method);

        $req = array(
            'useragent' => self::ua . '|' . self::version,
            'method' => $method,
            'url' => $target,
            'timeout' => $timeout,
            'params' => $args,
        );
        $dt = http::requestHigh($req);
        if ($dt) {
            $ret = json_decode($dt, true);
            if ($ret['errno'] != 0) {
                $tmp = !$this->_devMode ? '' : (' args:' . var_export($args, true) . ',');
                throw new \Exception('return data error for url:' . (!$this->_devMode ? $url : $target) . ',' . $tmp . ' data:' . $dt, self::exception_data);
            }
        } else {
            $url = !$this->_devMode ? $url : $this->url($url);
            $tmp = !$this->_devMode ? '' : (',args:' . var_export($args, true) . '');
            throw new \Exception('request failed for url:' . (!$this->_devMode ? $url : $target) . $tmp, self::exception_request);
        }

        return $ret['data'];
    }

}

class sso_client {

    protected $_appid = null;
    protected $_appkey = null;
    protected $_cookiePath = null;
    protected $_cookiePre = '';
    protected $_cookieDomain = "";
    protected $_code = '';

    const TICKET_COOKIE_NAME = 'ticket';
    const USER_COOKIE_NAME = 'username';
    const COOKIE_EXPIRED = 14400;

    const exception_init_error = -1;
    //生产环境用这个
    // protected $_ssoCheckCodeUrl = 'http://mis.diditaxi.com.cn/auth/sso/api/check_code';
    // protected $_ssoAuthUrl = 'http://mis.diditaxi.com.cn/auth/sso/api/check_ticket';
    // protected $_ssoLoginUrl = 'http://mis.diditaxi.com.cn/auth/sso/login';
    // protected $_ssoLogoutUrl = 'http://mis.diditaxi.com.cn/auth/ldap/logout';
    // protected $_authGetLoginUserUrl = 'http://mis.diditaxi.com.cn//auth/api/user/index';
    //线下测试用这个
    protected $_ssoCheckCodeUrl = 'https://sso-iam.xiaojukeji.com/auth/sso/api/check_code';
    protected $_ssoAuthUrl = 'https://sso-iam.xiaojukeji.com/auth/sso/api/check_ticket';
    protected $_ssoLoginUrl = 'https://sso-iam.xiaojukeji.com/auth/sso/login';
    protected $_ssoLogoutUrl = 'https://sso-iam.xiaojukeji.com/auth/ldap/logout';
    protected $_authGetLoginUserUrl = 'https://sso-iam.xiaojukeji.com/auth/api/user/index';
    protected $_indexUrl = 'http://10.95.100.106:8088/sso/index';

    public function __construct($appid, $appkey, $path = '/', $domain = "") {
        if(!$appid || !$appkey){
            throw new \Exception('bad arguments for init~!', self::exception_init_error);
        }
        $this->_appid = $appid;
        $this->_appkey = $appkey;
        $this->_cookiePath = $path;
        $this->_cookieDomain = $domain;

    }

    // public function setCookiePrefix($cookiePrefix){
    //      $this->_cookiePre = $cookiePrefix;
    // }

    public function getAppid(){
        return $this->_appid;
    }

    public function getAppkey(){
        return $this->_appkey;
    }

    public function checkLogin() {
       if (!$ticket=$this->getTicket()) {
            return false;
       } else {
            $params = array(
                'ticket' => $ticket,
                'app_id' => $this->_appid
            );
            $res = http::request($this->_ssoAuthUrl, $params, 'post');
            $r = json_decode($res, true);
            if (intval($r['errno']) === 0) {
                return true;
            }
            return false;
       }
    }

    public function getIndexUrl(){
        return $this->_indexUrl;
    }

    public function getSsoCenterJumpUrl($currentUrl){
        return $this->_ssoLoginUrl.'?app_id='.$this->_appid.'&jumpto='.urlencode($currentUrl).'&version='.urlencode(JET_VERSION);
    }

    public function setLogin($username, $ticket, $ext = array()){
        setcookie($this->_cookiePre.self::TICKET_COOKIE_NAME, $ticket, time()+self::COOKIE_EXPIRED, $this->_cookiePath, $this->_cookieDomain, false, true);
        setcookie($this->_cookiePre.self::USER_COOKIE_NAME, $username, time()+self::COOKIE_EXPIRED, $this->_cookiePath, $this->_cookieDomain, false, true);
        //echo $this->_cookiePre.self::USER_COOKIE_NAME;
    }

    public function refreshTicket(){
        $ticket = $this->getTicket();
        setcookie($this->_cookiePre.self::TICKET_COOKIE_NAME, $ticket, time()+self::COOKIE_EXPIRED, $this->_cookiePath, $this->_cookieDomain, false, true);
        $username = $this->getUsername();
        setcookie($this->_cookiePre.self::USER_COOKIE_NAME, $username, time()+self::COOKIE_EXPIRED, $this->_cookiePath, $this->_cookieDomain, false, true);
    }

    public function getTicket(){
        $ticket = isset($_COOKIE[$this->_cookiePre.self::TICKET_COOKIE_NAME]) ? $_COOKIE[$this->_cookiePre.self::TICKET_COOKIE_NAME] : "";
        return $ticket;
    }

    public function getUsername(){
        $ticket = $this->getTicket();
        $params = array('access_token'=>$ticket,'app_id'=>$this->_appid);
        $res = http::request($this->_authGetLoginUserUrl, $params, 'post');
        $r = json_decode($res, true);
        return $r['data']['user']['username'];//返回username

    }

    public function validCode($code){
        $params = array('code'=>$code, 'app_key'=>$this->_appkey, 'app_id'=>$this->_appid);
        $res = http::request($this->_ssoCheckCodeUrl, $params, 'post');
        $r = json_decode($res, true);
        if (intval($r['errno']) === 0) {
            return $r['data'];//返回ticket和username
        }
        return false;
    }

    public function logout(){
        //清理本地cookie
        setcookie($this->_cookiePre.self::TICKET_COOKIE_NAME, '', time()-31500000, $this->_cookiePath, $this->_cookieDomain);
        setcookie($this->_cookiePre.self::USER_COOKIE_NAME, '', time()-31500000, $this->_cookiePath, $this->_cookieDomain);
        //重定向至sso logout
        header("location: " . $this->_ssoLogoutUrl.'?app_id='.$this->_appid."&jumpto=".$this->_indexUrl);
        exit;
    }
}

/**
 * @desc class about http request
 * @author liuruizhou@didichuxing.com
 * @version 2015-8-25
 */
class http {

    /**
     *url,
     *params = array(),
     *method = GET( or POST)
     *multi = false(or true)
     *extheaders = array()
     *cookie = '', full str
     *referer = null
     *return_header = null,
     *return_rich_info = null
     *useragent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2041.*4 Safari/537.36'
     *proxy = null
     *timeout = 10
     */
    public static function requestHigh($args) {
        if (!$args || !$args['url']) {
            return false;
        }
        extract($args);

        $method = $method ? : 'GET';
        $timeout = $timeout ? : 5;

        if (!function_exists('curl_init')) {
            exit('Need to open the curl extension');
        }

        $method = strtoupper($method);
        $ci = curl_init();
        $default_ua = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2041.4 Safari/537.36';
        $useragent = $useragent ? : $default_ua;
        curl_setopt($ci, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, ($return_header || $return_rich_info) ? true : false);

        //curl_setopt($ci, CURLOPT_HTTP_VERSION,'1.0');


        if ($proxy) {
            curl_setopt($ci, CURLOPT_PROXY, $proxy);
        }

        if ($cookie)
            curl_setopt($ci, CURLOPT_COOKIE, $cookie);

        if ($referer)
            curl_setopt($ci, CURLOPT_REFERER, $referer);

        $headers = (array) $extheaders;
        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params)) {
                    if ($multi) {
                        foreach ($multi as $key => $file) {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    } else {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params)) {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                            . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($headers) {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ci);
        if ($return_rich_info) {
            $info = curl_getinfo($ci);
            $ret = array();
            $ret['info'] = $info;
            $ret['response']['header'] = substr($response, 0, $info['header_size']);
            $ret['response']['content'] = substr($response, $info['header_size']);

            curl_close($ci);
            return $ret;
        }

        curl_close($ci);
        return $response;
    }

// end function

    /**
     *  init a http request
     * @param $url URL
     * @param $params, arguments~ as   array('content'=>'test', 'format'=>'json');
     * @param $method    GET|POST
     * @param $multi
     * @param $extheaders
     * @param cookie string
     * @param  referer string
     * @param $proxy proxy string as proxy.tencent.com:8080
     * @param $extheaders
     * @return string
     */
    public static function request($url, $params = array(), $method = 'GET', $multi = false, $extheaders = array(), $cookie = '', $referer = null, $proxy = null, $timeout = 10) {
        if (!function_exists('curl_init'))
            exit('Need to open the curl extension');
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:11.0) Gecko/20100101 Firefox/11.0');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);

        if ($proxy) {
            curl_setopt($ci, CURLOPT_PROXY, $proxy);
        }

        if ($cookie)
            curl_setopt($ci, CURLOPT_COOKIE, $cookie);

        if ($referer)
            curl_setopt($ci, CURLOPT_REFERER, $referer);


        $headers = (array) $extheaders;
        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($params)) {
                    if ($multi) {
                        foreach ($multi as $key => $file) {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    } else {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params)) {
                    $url = $url . (strpos($url, '?') ? '&' : '?')
                            . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($headers) {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }

}



class util{

    protected static $_map = array();

    public static function set($key, $val){
        self::$_map[$key] = $val;
    }

    public static function get($key){
        return self::$_map[$key];
    }



}