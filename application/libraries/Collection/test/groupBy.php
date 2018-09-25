<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/9/25
 * Time: 下午4:06
 */

require_once '../../Collection.php';
require_once 'data.php';
require_once '../tools.php';

$collection = Collection::make($array);

$groupByMap = [
    '$collection->groupBy(\'flow\')' => '0332a14c9208d4b2c312ff60c7b7cc27',
    '$collection->groupBy([\'flow\', \'hour\'])' => '328dc8ed514ac8f5a5f02cdcab5a41bd',
    '$collection->groupBy([\'flow\', function($v) { return $v[\'hour\']; }])' => '328dc8ed514ac8f5a5f02cdcab5a41bd',
    '$collection->groupBy(function($v) { return $v[\'hour\']; })' => '3703adc20ff8341c3414f2bb7834a1f6',
    '$collection->groupBy(\'flow\', function($v) { return implode(\',\', array_column($v, \'value\')); })' => '425ce6a6a2d18de3b48ce1e82ae1d276',
];

// 0332a14c9208d4b2c312ff60c7b7cc27
// 328dc8ed514ac8f5a5f02cdcab5a41bd
// 328dc8ed514ac8f5a5f02cdcab5a41bd
// 3703adc20ff8341c3414f2bb7834a1f6
// 425ce6a6a2d18de3b48ce1e82ae1d276
echo "groupBy\n";
foreach ($groupByMap as $code => $md5) {
    $run = 'return md5(' . $code . '->toJson());';
    if(eval($run) === $md5)
        echo output("true\n", 'GREEN');
    else
        echo output("false\n", 'RED');
}

