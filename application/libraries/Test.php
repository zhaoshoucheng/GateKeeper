<?php

require 'Collection.php';

$collection = Collection::make([
    ['id' => 1, 'name' => 'aaa', 'age' => 13],
    ['id' => 2, 'name' => 'bbb', 'age' => 12],
    ['id' => 1, 'name' => 'ccc', 'age' => 12],
    ['id' => 2, 'name' => 'ddd', 'age' => 12],
    ['id' => 1, 'name' => 'aaa', 'age' => 13],
    ['id' => 2, 'name' => 'bbb', 'age' => 12],
    ['id' => 1, 'name' => 'ccc', 'age' => 12],
    ['id' => 2, 'name' => 'ddd', 'age' => 12],
]);

$res = $collection->arrayWalk(function ($c, $k) {
    echo get_class($c), PHP_EOL;
})->toArray();
