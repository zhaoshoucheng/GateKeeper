<?php

require __DIR__ . '/Collection.php';

$collection = Collection::make([
   ['id' => 1, 'age' => '15', 'name' => 'aaa'],
   ['id' => 1, 'age' => '14', 'name' => 'aab'],
   ['id' => 1, 'age' => '15', 'name' => 'ccc'],
   ['id' => 1, 'age' => '14', 'name' => 'ccd'],
   ['id' => 2, 'age' => '15', 'name' => 'eee'],
   ['id' => 2, 'age' => '14', 'name' => 'eef'],
   ['id' => 2, 'age' => '15', 'name' => 'ggg'],
   ['id' => 2, 'age' => '14', 'name' => 'ggh'],
]);

dd($collection->groupBy(function ($v) {
        return substr($v['name'], 0, 2);
    }, null));
