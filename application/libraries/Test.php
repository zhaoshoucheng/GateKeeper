<?php

$collection = new Collection([
    ['id' => 1, 'name' => 'aaa', 'age' => 13],
    ['id' => 2, 'name' => 'bbb', 'age' => 12],
    ['id' => 3, 'name' => 'ccc', 'age' => 11],
    ['id' => 4, 'name' => 'ddd', 'age' => 10],
]);

$twoCollection = new TwoDimensionCollection([
    '2015' => ['one' => 12, 'two' => 11, 'three' => 13, 'four' => 10],
    '2016' => ['one' => 12, 'two' => 11, 'three' => 12, 'four' => 10],
    '2017' => ['one' => 12, 'two' => 11, 'three' => 11, 'four' => 20],
    '2018' => ['one' => 42, 'two' => 11, 'three' => 10, 'four' => 10],
]);

print_r($twoCollection->getXMax());