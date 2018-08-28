<?php

require __DIR__ . '/Collection.php';

$collection = Collection::make([
   'a' => [
       'b' => [
           'c' => [
               'd' => 'abcd'
           ],
           'e' => [
               'f' => 'abef'
           ],
       ]
   ]
]);

var_dump($collection->last());
