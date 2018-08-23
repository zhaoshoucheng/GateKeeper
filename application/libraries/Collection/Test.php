<?php

require 'Collection.php';

$collection = new Collection([
    ['id' => 1, 'name' => 'aaa', 'age' => 13],
    ['id' => 2, 'name' => 'bbb', 'age' => 12],
    ['id' => 3, 'name' => 'ccc', 'age' => 11],
    ['id' => 4, 'name' => 'ddd', 'age' => 10],
]);

$collection->orderBy('id', SORT_DESC)
    ->groupBy('age');

print_r($collection->toArray());