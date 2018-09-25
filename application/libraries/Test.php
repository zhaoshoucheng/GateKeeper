<?php
//
require __DIR__ . '/Collection.php';

//$array = [
//    ['hour' => '10:00', 'flow' => 'aaa', 'value' => 10],
//    ['hour' => '10:00', 'flow' => 'bbb', 'value' => 8],
//    ['hour' => '10:30', 'flow' => 'aaa', 'value' => 6],
//    ['hour' => '10:30', 'flow' => 'bbb', 'value' => 15],
//    ['hour' => '11:00', 'flow' => 'aaa', 'value' => 13],
//    ['hour' => '11:00', 'flow' => 'bbb', 'value' => 5],
//    ['hour' => '11:30', 'flow' => 'aaa', 'value' => 9],
//    ['hour' => '11:30', 'flow' => 'bbb', 'value' => 16],
//];

$array = [];

$h = range(0, 23);
$m = [':00', ':30'];

$f = ['aaa', 'bbb', 'ccc', 'ddd', 'eee', 'fff', 'ggg'];

for ($i = 0; $i < 100000; ++$i)
{
    $array[] = [
        'hour' => array_rand($h) . $m[array_rand($m)],
        'name' => $f[array_rand($f)],
        'value' => rand(0, 1000)
    ];
}

$time1 = microtime(true);
Collection::make($array)->groupBy(['name', function($v) {return $v['hour'];}, 'value'], function ($v) {
    return array_map(function ($v) {
        return $v['value'];
    }, $v);
});

$time2 = microtime(true);

$res = [];
foreach ($array as $value)
{
    $res[$value['name']][$value['hour']][$value['value']][] = $value['value'];
}

$time3 = microtime(true);

echo $time2 - $time1, PHP_EOL;
echo $time3 - $time2;

