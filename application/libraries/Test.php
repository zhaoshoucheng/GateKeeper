<?php
//
require __DIR__ . '/Collection.php';

$array = [
    ['hour' => '10:00', 'flow' => 'aaa', 'value' => 10],
    ['hour' => '10:00', 'flow' => 'bbb', 'value' => 8],
    ['hour' => '10:30', 'flow' => 'aaa', 'value' => 6],
    ['hour' => '10:30', 'flow' => 'bbb', 'value' => 15],
    ['hour' => '11:00', 'flow' => 'aaa', 'value' => 13],
    ['hour' => '11:00', 'flow' => 'bbb', 'value' => 5],
    ['hour' => '11:30', 'flow' => 'aaa', 'value' => 9],
    ['hour' => '11:30', 'flow' => 'bbb', 'value' => 16],
];

//dd(Collection::make($array)->forget(1));

function fun($v, $k) {
    return '';
}

$v = '$v';
$k = '$k';

$name = 'fun';

$num = 100000000;

$ar = [$v, $k];

echo microtime(true), PHP_EOL;
for ($i = 0; $i < $num; $i++)
    $name($v, $k);
echo microtime(true), PHP_EOL;
for ($i = 0; $i < $num; $i++)
    call_user_func($name, $v, $k);
echo microtime(true), PHP_EOL;
for ($i = 0; $i < $num; $i++)
    call_user_func_array($name, $ar);
echo microtime(true), PHP_EOL;
