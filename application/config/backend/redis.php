<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['redis'] = array(
    'default'=>array(
        'timeout'=>0.1,
        'password'=>NULL,
        'servers'=>array(
            array('host'=>"100.69.139.14","port"=>"3660"),
            array('host'=>"100.69.138.23","port"=>"3660"),
            array('host'=>"100.69.101.52","port"=>"3660"),
            array('host'=>"100.69.143.14","port"=>"3660"),
        )
    ),
);
