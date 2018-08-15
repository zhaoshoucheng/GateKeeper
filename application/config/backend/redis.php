<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['redis'] = array(
    'default'=>array(
        'timeout'=>0.1,
        'password'=>NULL,
        'servers'=>array(
            array('host'=>"100.69.239.57","port"=>"3060"),
        )
    ),
);
