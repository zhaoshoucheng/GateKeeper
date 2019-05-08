<?php
//线下环境测试使用
//正式环境:程晓澄提供

defined('BASEPATH') OR exit('No direct script access allowed');
//----版本号----//
define('JET_VERSION', '1.0');
//----这里写由权限平坦下发的appid(必须配置)----//
define('JET_AUTH_APPID', 1708);
//----这里写由权限平坦下发的app key(必须配置)----//
define('JET_AUTH_APPKEY', '428ffe82b8f7048aa446a58bc988719e');
define('JET_SSO_APPID', JET_AUTH_APPID); //这里配置你申请的appid
define('JET_SSO_APPKEY', JET_AUTH_APPKEY); //这里配置你申请的appkey
//----cookie域名----//
define('JET_SSO_COOKIE_DOMAIN', "");
//----cookie path----//
define('JET_SSO_COOKIE_PATH', '/');

class Sso extends CI_Controller
{
	public function __construct(){
		parent::__construct();
	}

    public function login(){
        $code = $_GET['code'];
        if(!$code){
            echo 'empty code~!';
            exit;
        }
        $jumpto = urldecode($_GET['jumpto']);
        try {
            $sso_client = new sso_client(JET_SSO_APPID, JET_SSO_APPKEY);
            $login = $sso_client->checkLogin();
            if (!$login) {
                $res = $sso_client->validCode($code);
                if ($res === false) {
                    echo 'code error';
                    exit;
                }
                $sso_client->setLogin($res['username'], $res['ticket']);
            } else {
                $sso_client->refreshTicket();
            }
            //jumpto的地方最好做一下跟自己的域名一样判断，不要允许跨域名跳转
            $targetHost = parse_url($jumpto, PHP_URL_HOST);
            $currentHost = $_SERVER['HTTP_HOST'];
            if ($targetHost != $currentHost || !$jumpto || $jumpto == 'index') {
                $jumpto = $sso_client->getIndexUrl();
            }
            header("location: " . $jumpto);
        } catch (\Exception $e) {
            echo 'login failed!';
        }
        //com_log_warning('login_info', 0, '', $_REQUEST);
    }

    public function logout(){
        $sso_client = new sso_client(JET_SSO_APPID, JET_SSO_APPKEY);
        $sso_client->logout();
        //com_log_warning('login_info', 0, '', $_REQUEST);
    }

    public function index(){
        print_r($_COOKIE);
    }
}
