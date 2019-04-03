<?php
/*
 * 这个文件存放各种白名单
 */

// 不用sso登陆就可以访问接口的IP
$config['white_escape_sso'] = [
    '100.90.164.31:8013',
    '100.90.164.31:8088',
    '100.90.164.31:8083',
    '100.90.164.31:8082',
    '100.90.164.31:8089',
    '100.90.164.31:8099',
    '100.90.164.31:8097',
    '100.90.164.31:8034',
    '10.179.132.61:8088',
    '100.95.100.106:8088',
    'www.itstool.com',
    '100.90.165.32:8088',
    'localhost:8000', //本地调试环境
];

// 白名单Ip及token就可以登陆
$config['white_token_clientip_escape'] = [
    '100.90.165.32' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //沙盒ip
    '100.90.164.31' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //验证token+ip
    '100.90.163.51' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //验证token+ip
    '100.90.163.52' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //验证token+ip
    '100.90.227.60' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //验证token+ip
    '100.90.106.51' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //验证token+ip
    '127.0.0.1' => [], //不验token
    '10.160.128.94' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //city_brain

    '10.85.112.153' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //realtimealarm
    '10.160.129.193' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //realtimealarm
    '10.161.74.50' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //realtimealarm

    '100.69.176.20' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //diyu
];

// diyu内网token就可以登陆,仅限内网访问
$config['white_token_escape'] = [
    '02efffde3a9b5a8f8f04f7c00fb92cb0',
];