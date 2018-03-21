<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 16/12/5
 * Time: 14:14
 */
$config['sso_server'] = array(
    'remote_host'     => 'http://mis.diditaxi.com.cn/auth',
    'timeout'         => 1000,
    'connect_timeout' => 200,
);

/*
$config['upm_server'] = array(
    'remote_host'     => 'http://100.69.238.12:8000/sec/upmapi',
    'timeout'         => 1000,
    'connect_timeout' => 200,
    //'retry'           => 1,
);
*/

$config['uri'] = array(
    //sso相关
    'login' => '/sso/login',
    'logout' => '/ldap/logout/index',
    'codeCheck' => '/sso/api/check_code',
    'ticketCheck' => '/sso/api/check_ticket',
    //权限相关
    'getUserInfo' => '/api/user/index',  //获取用户信息
    'getMenuList' => '/api/user/menu_lists', //获取所有有权限的菜单功能list
    'isValidFeature' => '/api/user/check_access', //是否有权限访问XX功能
    'getUserFeatureAndArea' => '/api/user/features', //获取用户功能权限列表和城市
    'getCityAuth' => '/api/city/owner',
    //'getUserFlag' => '/api/flag/user', //获取用户拥有的标识位
    //upm权限系统
    //'upm_getUserInfo' => '/user/get/info',
    //'upm_getUserAreas' => '/user/area/listchild',
    //'upm_isValidFeature' => '/user/check/feature',
);

$config['appid'] = '789';
$config['appkey'] = '428ffe82b8f7048aa446a58bc988719e';
