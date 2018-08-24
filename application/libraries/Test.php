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

$res = $collection->groupBy(['id', 'name', 'age'], function ($collection) {
    return implode(',', $collection->arrayColumn('name'));
})->toArray();

print_r($res);