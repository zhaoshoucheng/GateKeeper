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

// message
$config['errmsg'] = [
	ERR_REQUEST_TIMING_API => 'Failed to connect to timing service.', // 请求不到配时API
	ERR_REQUEST_WAYMAP_API => 'Failed to connect to waymap service.'  // 请求不到路网API
];