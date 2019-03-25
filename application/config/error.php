<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|-----------------------------------------------------
| 错误信息配置
|-----------------------------------------------------
*/

// code
define('ERR_SUCCESS', 0);                 // 成功
define('ERR_DEFAULT', 100000);            // 默认
define('ERR_PARAMETERS', 100400);         // 参数传递错误码
define('ERR_REQUEST_TIMING_API', 200001); // 请求配时API出错
define('ERR_REQUEST_WAYMAP_API', 200002); // 请求路网API出错
define('ERR_REQUEST_TRAJ_API', 200003); // 请求轨迹API出错
define('ERR_UNKNOWN', 900001); // 未知错误


/**
 * 错误码共7位
 * 第1位：1表示全局错误,2表示各api错误
 * 第2-4位:表示api接口类型
 * 第5-7位:表示具体的错误类型
 */
//全局错误
define('ERR_AUTH_URI',1000002);
define('ERR_AUTH_AREA',1000003);
define('ERR_MENU_FAILED',1000004);
define('ERR_AUTH_LOGIN',1000005);
define('ERR_AUTH_IP',1000006);
define('ERR_OPERATION_LIMIT',1000007);
define('ERR_DATABASE', 1000008);
define('ERR_HTTP_FAILED', 1000009);
define('ERR_AUTH_KEY', 1000010);
define('ERR_REQUEST_LONG', 1000011);
define('ERR_DB_QUERY_LONG', 1000012);
define('ERR_AUTH_PERMISSION',1000013);

//api错误
//map接口错误
define('ERR_ROAD_MAPINFO_FAILED', 2000001);

//tide



// message
$config['errmsg'] = [
	ERR_REQUEST_TIMING_API => 'Failed to connect to timing service.', // 请求不到配时API
	ERR_REQUEST_WAYMAP_API => 'Failed to connect to waymap service.', // 请求不到路网API

	//全局
    ERR_AUTH_URI => "对不起，您没有此功能权限",
    ERR_AUTH_AREA => "对不起，您没有此地区权限",
    ERR_MENU_FAILED=> "获取菜单失败",
    ERR_AUTH_LOGIN => "请登录系统",
    ERR_AUTH_IP => 'IP地址不合法',
    ERR_OPERATION_LIMIT => '操作次数到达上限,请明日再试',
    ERR_DATABASE => '数据库错误',
    ERR_HTTP_FAILED => 'http请求失败',

    ERR_ROAD_MAPINFO_FAILED => '查询新四link数据失败',
];