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
    '100.90.164.31:8094',
    '100.90.164.31:8034',
    '100.90.164.31:8100',
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

    '10.161.129.23' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //realtimealarm
    '10.161.85.0' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //realtimealarm
    '10.161.89.186' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //realtimealarm
    
    '100.69.176.20' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //diyu


    '10.169.238.102' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.169.250.206' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.169.205.165' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.168.196.64' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.169.14.86' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.169.26.135' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.168.117.127' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.167.81.59' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.169.5.78' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.168.232.111' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.160.76.67' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.161.164.116' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.169.250.201' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"],  //信号机
    '10.88.128.45' => ["05984e73effb5fe2baeeda5e7c10be8e"], // 麒麟提供的内网机器

    '10.160.92.91' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"], // 配时服务
    '10.160.96.161' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"], // 配时服务
    '10.160.131.159' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"], // 配时服务
    '10.160.96.160' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"], // 配时服务

    '100.70.160.62' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"], // 安全中心
    '100.90.165.32' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"], // 安全中心
    '10.161.84.26' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"], // diyu大屏
    '10.161.124.22' => ["01beffde3a9b5a8f8f04f7c00fb92cb0"], // diyu大屏
];

// diyu内网token就可以登陆,仅限内网访问
$config['white_token_escape'] = [
    '02efffde3a9b5a8f8f04f7c00fb92cb0',
];