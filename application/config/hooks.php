<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/


$hook['pre_system'][] = array(
    'function' => 'gen_logid',
    'filename' => 'log_helper.php',
    'filepath' => 'helpers'
);

$hook['pre_system'][] = array(
    'function' => 'gen_traceid',
    'filename' => 'log_helper.php',
    'filepath' => 'helpers'
);

$hook['pre_system'][] = array(
    'function' => 'log_request',
    'filename' => 'log_helper.php',
    'filepath' => 'helpers'
);