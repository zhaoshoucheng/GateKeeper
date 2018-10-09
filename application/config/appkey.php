<?php

/***************************************************************
# name: appkey.php
# date: 2017-12-18
# desc:
# author: luoqing
****************************************************************/
$config['authirized_apps'] = array(
    '1001' => array(
        'name'  => 'cron',
        'secret'=> 'e9b0a9042d1840dcdb9b9c7095391949',
        'open_api'   => array(
            'Overview/getNowDate',
            'Overview/junctionsList',
            'Overviewalarm/sevenDaysAlarmChange',
            'Overviewalarm/todayAlarmInfo',
            'Overview/junctionSurvey',
            'Overviewalarm/realTimeAlarmList',
            'Overview/getCongestionInfo',
            'Overview/operationCondition',
            'Overviewtoplist/stopDelayTopList',
            'Overviewtoplist/stopTimeCycleTopList',
            'Feedback/getTypes',
        ),
        'white_ips' => array(
        )
    ),
    '1002' => array(
        'name'  => 'task',
        'secret'=> 'abfeb5d614beaed21c306b915a1ca1de',
        'open_api'   => array(
            'task/getTaskRate',
            'task/getTaskStatus',
        ),
        'white_ips' => array(
        )
    ),
    '1003' => array(
        'name'  => 'token',
        'secret'=> '3a01e6c56bcce94ee5de073df3d512d1',
        'open_api'   => array(
            'Overview/verifyToken',
        ),
        'white_ips' => array(
        )
    ),
    '1004' => array(
        'name'  => 'token',
        'secret'=> '3a01e6c56bcce94ee5de073df3d512d2',
        'open_api'   => array(
            'AdaptMovement/queryconf',
            'AdaptMovement/updateconf',
            'AdaptMovement/deleteconf',
        ),
        'white_ips' => array(
            "172.25.32.53",
            "100.70.160.62"
        )
    ),
);
