<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['redis'] = array(
    'default'=>array(
        'timeout'=>0.1,
        'password'=>NULL,
        'servers'=>array(
            array('host'=>"127.0.0.1","port"=>"6379"),
        )
    ),
);
