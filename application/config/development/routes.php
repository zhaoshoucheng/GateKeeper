<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
$route['Flow/(:any)'] = 'flow/proxy/$1';
$route['SignalControl/(:any)'] = 'SignalControl/proxy/$1';
$route['TimingRelease/(:any)'] = 'TimingRelease/proxy/$1';
$route['AdaptMovement/(:any)'] = 'AdaptMovement/proxy/$1';
$route['Xmmtrace/(:any)'] = 'Xmmtrace/proxy/$1';
$route['itstool/(.+)'] = "$1";
$route['signalpro/api/(.+)'] = "$1";
$route['signalpro/proxy/zsy/(.+)'] = "proxy/zsy/$1";
