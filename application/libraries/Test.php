<?php

require __DIR__ . '/Collection/Test/data.php';
require __DIR__ . '/Collection.php';

$collection = Collection::make([
    ['id' => 1, 'hour' => '10:30', 'flow' => 'aaa', 'age' => 1],
    ['id' => 2, 'hour' => '10:30', 'flow' => 'bbb', 'age' => 2],
    ['id' => 3, 'hour' => '11:30', 'flow' => 'aaa', 'age' => 5],
    ['id' => 4, 'hour' => '11:30', 'flow' => 'bbb', 'age' => 8],
    ['id' => 5, 'hour' => '12:30', 'flow' => 'aaa', 'age' => 6],
    ['id' => 6, 'hour' => '12:30', 'flow' => 'bbb', 'age' => 3],
    ['id' => 7, 'hour' => '13:30', 'flow' => 'aaa', 'age' => 30],
    ['id' => 8, 'hour' => '13:30', 'flow' => 'bbb', 'age' => 12],
]);

$res = $collection->groupBy(['flow', 'hour'], function ($c) {
    dd($c->get(0));
});
dd($res);
