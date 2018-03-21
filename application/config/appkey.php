<?php

/***************************************************************
# name: appkey.php
# date: 2017-12-18
# desc:
# author: luoqing
****************************************************************/
$config['authirized_apps'] = array(
    '1' => array(
        'name'  => 'test',
        'secret'=> 'a49e0d34a63',
        'open_api'   => array(
            '/signal/junction/traffic_info',
            '/signal/road/effect_cmp',
        ),
        'white_ips' => array(
        
        )
    ),
    '2' => array(
        'name'  => 'yihualu',
        'secret'=> 'b50dfddfsfs',
        'open_api'   => array(
            '/signal/junction/traffic_info',
        ),
        'white_ips' => array(
        
        )
    ),
    '1001' => array(
        'name'  => 'diyu',
        'secret'=> '8b5146df4333055cc5534e',
        'open_api'   => array(
            '/signal/junction/traffic_info',
        ),
        'white_ips' => array(
        
        )
    ),
    '1002' => array(
        'name'  => 'signal',
        'secret'=> '675146fht43df055sg553cd',
        'open_api'   => array(
            '/signal/track/links_tracks',
        ),
        'white_ips' => array(
        
        )
    ),

);
