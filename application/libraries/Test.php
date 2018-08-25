<?php

require 'Collection.php';

$collection = new Collection([
    ['id' => 1, 'name' => 'aaa', 'age' => 13],
    ['id' => 2, 'name' => 'bbb', 'age' => 12],
    ['id' => 3, 'name' => 'ccc', 'age' => 12],
    ['id' => 4, 'name' => 'ddd', 'age' => 12],
]);

$res = $collection
    ->filter(function ($v) { return $v['id'] > 1; })
    ->groupBy('age', function ($v) {
        return implode('-', array_column($v, 'name'));
    })->toArray();

print_r($res);