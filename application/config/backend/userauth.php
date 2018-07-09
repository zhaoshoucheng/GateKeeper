<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 16/12/5
 * Time: 14:14
 */
$config['sso_server'] = array(
    'remote_host'     => 'https://sso-iam.xiaojukeji.com/auth',
    'timeout'         => 1000,
    'connect_timeout' => 200,
);

$config['upm_server'] = array(
    'remote_host'     => 'http://10.169.145.40:8088',
    'timeout'         => 1000,
    'connect_timeout' => 200,
    //'retry'           => 1,
);

$config['uri'] = array(
    //sso相关
    'login' => '/sso/login',
    'logout' => '/ldap/logout/index',
    'codeCheck' => '/sso/api/check_code',
    'ticketCheck' => '/sso/api/check_ticket',

    //权限相关
    'getMenuList' => '/api/user/menu_lists', //获取所有有权限的菜单功能list /user/features/list ?
    'isValidFeature' => '/api/user/check_access', //是否有权限访问XX功能  /user/check/feature ?
    'getUserFeatureAndArea' => '/api/user/features', //获取用户功能权限列表和城市 /user/get/features?
    'getCityAuth' => '/api/city/owner', // /user/area/list?
    'getUserFlag' => '/api/flag/user', //获取用户拥有的标识位

    //upm权限系统
    'upm_getUserAreas'   => '/user/get/permissions',
    'upm_getMenuList'    => '/user/get/menus',
    'upm_getUserInfo'    => '/user/get/info',
    'upm_isValidFeature' => '/user/check/feature',
);

$config['appid'] = '789';
$config['appkey'] = '428ffe82b8f7048aa446a58bc988719e';
