<?php

require 'Collection.php';
require 'TwoDimensionCollection.php';

$collection = new TwoDimensionCollection([
    ['id' => 1, 'name' => 'aaa', 'age' => 13],
    ['id' => 2, 'name' => 'bbb', 'age' => 12],
    ['id' => 3, 'name' => 'ccc', 'age' => 12],
    ['id' => 4, 'name' => 'ddd', 'age' => 12],
]);

//$twoCollection = new TwoDimensionCollection([
//    '2015' => ['one' => 12, 'two' => 11, 'three' => 13, 'four' => 10],
//    '2016' => ['one' => 12, 'two' => 11, 'three' => 12, 'four' => 10],
//    '2017' => ['one' => 12, 'two' => 11, 'three' => 11, 'four' => 20],
//    '2018' => ['one' => 42, 'two' => 11, 'three' => 10, 'four' => 10],
//]);
//
//print_r($twoCollection->getXMax());

$res = $collection
    ->filter(function ($v) { return $v['id'] > 1; })
    ->groupBy('age')
    ->orderBy('age')
    ->toArray();

print_r($res);