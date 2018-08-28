<?php

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

$arr = [[1,2,3,4,5,6]];
echo Collection::make($arr)->forget('0.0');