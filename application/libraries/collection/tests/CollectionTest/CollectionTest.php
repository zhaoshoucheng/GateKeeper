<?php
/**
 * Created by PhpStorm.
 * User: LiCxi
 * Date: 2018/9/27
 * Time: 下午8:36
 */

use PHPUnit\Framework\TestCase;
use Didi\Cloud\Collection\Collection;

class CollectionTest extends TestCase
{
    public function testGet()
    {
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];
        $target = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $this->assertEquals($target, Collection::make($source)->get());


        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];
        $target = [
            'id' => 1,
            'name' => 'Larissa',
            'age' => 21,
            'gender' => 'W'
        ];

        $this->assertEquals($target, Collection::make($source)->get(0));

        $source = [
            'id' => 1,
            'name' => 'Larissa',
            'age' => 21,
            'gender' => 'W'
        ];
        $target = 'Larissa';

        $this->assertEquals($target, Collection::make($source)->get('name'));

        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];
        $target = 'Larissa';

        $this->assertEquals($target, Collection::make($source)->get('0.name'));

        $source = [
            'id' => 1,
            'name' => 'Larissa',
            'age' => 21,
            'gender' => 'W'
        ];
        $target = '1996-12-21';

        $this->assertEquals($target, Collection::make($source)->get('birthday', '1996-12-21'));

        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];
        $target = 'default';

        $this->assertEquals($target, Collection::make($source)->get('0.name', 'default', false));
    }

    public function testSet()
    {
        $this->assertEquals([1 => 'value'], Collection::make([])->set(1, 'value')->get());

        $this->assertEquals(['key' => 'value'], Collection::make([])->set('key', 'value')->get());

        $this->assertEquals(['one' => [ 'two' => 'value']], Collection::make([])->set('one.two', 'value')->get());

        $this->assertEquals(['one.two' => 'value'], Collection::make([])->set('one.two', 'value', false)->get());

        $this->assertEquals(['one' => 'three'], Collection::make(['one' => ['two' => 'value']])->set('one', 'three')->get());

        $this->assertEquals(['one' => ['two' => 'three']], Collection::make(['one' => 'three'])->set('one', ['two' => 'three'])->get());

        $this->assertEquals(['one' => ['two' => 'three']], Collection::make(['one' => 'two'])->set('one.two', 'three')->get());
    }

    public function testHas()
    {
        $this->assertEquals(true, Collection::make(['value'])->has(0));

        $this->assertEquals(false, Collection::make(['value'])->has(1));

        $this->assertEquals(true, Collection::make(['value'])->has(0, 'value'));

        $this->assertEquals(false, Collection::make(['value'])->has(0, 'key'));

        $this->assertEquals(false, Collection::make(['value'])->has(1, 'value'));

        $this->assertEquals(true, Collection::make(['key' => 'value'])->has('key'));

        $this->assertEquals(true, Collection::make(['key' => 'value'])->has('key', 'value'));

        $this->assertEquals(false, Collection::make(['key' => 'value'])->has('value'));

        $this->assertEquals(true, Collection::make(['one' => ['two' => 'three']])->has('one.two'));

        $this->assertEquals(true, Collection::make(['one' => ['two' => 'three']])->has('one.two', 'three'));

        $this->assertEquals(false, Collection::make(['one' => ['two' => 'three']])->has('one.two', 'four'));

        $this->assertEquals(false, Collection::make(['one' => ['two' => 'three']])->has('one.two', 'three', false));

        $this->assertEquals(true, Collection::make(['one' => ['two' => 'three']])->has('one', ['two' => 'three']));
    }

    public function testForget()
    {
        $this->assertEquals([], Collection::make([])->forget(0)->get());

        $this->assertEquals([], Collection::make(['val'])->forget(0)->get());

        $this->assertEquals([], Collection::make(['key' => 'value'])->forget('key')->get());

        $this->assertEquals(['one' => []], Collection::make(['one' => ['two' => 'three']])->forget('one.two')->get());

        $this->assertEquals(['one' => ['two' => 'three']], Collection::make(['one' => ['two' => 'three']])->forget('one.two', false)->get());
    }

    public function testGroupBy()
    {
        /**
         * GroupByInt
         */
        $source = [
            [1, 0.5],
            [2, 0.5]
        ];
        $target = [
            1 => [[1, 0.5]],
            2 => [[2, 0.5]]
        ];

        $this->assertEquals($target, Collection::make($source)->groupBy(0)->get());

        /**
         * GroupByString
         */
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];
        $target = [
            'Larissa' => [['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W']],
            'Jacquelyn' => [['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M']],
            'Khaleesi' => [['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W']],
            'Amelie' => [['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M']],
            '伊芙琳' => [['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W']],
            '阿祖娜' => [['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M']],
        ];

        $this->assertEquals($target, Collection::make($source)->groupBy('name')->get());

        /**
         * GroupByStringArray
         */
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];
        $target = [
            'W' => [
                'Larissa' => [[ 'id' => 1,  'name' => 'Larissa', 'age' => 21, 'gender' => 'W' ]],
                'Khaleesi' => [[ 'id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W' ]],
                '伊芙琳' => [[ 'id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W' ]],
            ],
            'M' => [
                'Jacquelyn' => [[ 'id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M' ]],
                'Amelie' => [[ 'id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M', ]],
                '阿祖娜' => [[ 'id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M',]],
            ],
        ];

        $this->assertEquals($target, Collection::make($source)->groupBy(['gender', 'name'])->get());

        /**
         * GroupByStringCallbackArray
         */

        $this->assertEquals($target, Collection::make($source)->groupBy(['gender', function($v) {
            return $v['name'];
        }])->get());

        /**
         * GroupByStringAndCallback
         */
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5],
        ];
        $target = [
            'Larissa' => ['id' => 1, 'name' => 'Larissa', 'age' => 21],
            'Jacquelyn' => ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16],
            'Khaleesi' => ['id' => 3, 'name' => 'Khaleesi', 'age' => 18],
            'Amelie' => ['id' => 4, 'name' => 'Amelie', 'age' => 84],
            '伊芙琳' => ['id' => 5, 'name' => '伊芙琳', 'age' => 46],
            '阿祖娜' => ['id' => 6, 'name' => '阿祖娜', 'age' => 5],
        ];

        $this->assertEquals($target, Collection::make($source)->groupBy('name', function ($v) {
            return current($v);
        })->get());

        /**
         * GroupByStringArrayAndCallback
         */

        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];
        $target = [
            'W' => [
                'Larissa' => [ 'id' => 1,  'name' => 'Larissa', 'age' => 21, 'gender' => 'W' ],
                'Khaleesi' => [ 'id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W' ],
                '伊芙琳' => [ 'id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W' ],
            ],
            'M' => [
                'Jacquelyn' => [ 'id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M' ],
                'Amelie' => [ 'id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M', ],
                '阿祖娜' => [ 'id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M',],
            ],
        ];

        $this->assertEquals($target, Collection::make($source)->groupBy(['gender', 'name'], function ($v) {
            return current($v);
        })->get());

        /**
         * GroupByStringAndCallbackArrayAndCallback
         */

        $this->assertEquals($target, Collection::make($source)->groupBy(['gender', function ($v) {
            return $v['name'];
        }], function ($v) {
            return current($v);
        })->get());

        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];
        $target = [
            'Larissa' => [0 => ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W']],
            'Jacquelyn' => [1 => ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M']],
            'Khaleesi' => [2 => ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W']],
            'Amelie' => [3 => ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M']],
            '伊芙琳' => [4 => ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W']],
            '阿祖娜' => [5 => ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M']],
        ];

        $this->assertEquals($target, Collection::make($source)->groupBy('name', null, true)->get());
    }

    public function testAvg()
    {
        $this->assertEquals(2, Collection::make([1,2,3])->avg());

        $this->assertEquals(2.5, Collection::make([1,2,3,4])->avg());

        $this->assertEquals(3, Collection::make([1,2,3,4])->avg(null, function ($avg) { return round($avg); }));

        $this->assertEquals(0, Collection::make([[1], [2]])->avg());

        $this->assertEquals(0, Collection::make([[1], [2]])->avg(0));

        $this->assertEquals(0, Collection::make([[1], [2]])->avg(0, function ($avg) { return round($avg); }));

        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $this->assertEquals(3.5, Collection::make($source)->avg('id'));
    }

    public function testEach()
    {
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $target = '';
        Collection::make($source)->each(function ($v) use (&$target) {
            $target .= $v['id'];
        });

        $this->assertEquals('123456', $target);

        $target = '';
        Collection::make($source)->each(function ($v, $k) use (&$target) {
            $target .= ($v['id'] . $k);
        });

        $this->assertEquals('102132435465', $target);

        $target = '';
        Collection::make($source)->each(function ($v) use (&$target) {
            if($v['id'] >= 3) {
                return false;
            }
            $target .= $v['id'];
            return true;
        });

        $this->assertEquals('12', $target);

        $source = [
            ['color' => 'red', 'code' => '#FFF000'],
            ['color' => 'green', 'code' => '#EEEFFF'],
            ['color' => 'blue', 'code' => '#F0F0F0'],
        ];

        $target = [];
        Collection::make($source)->eachSpread(function ($color, $code) use (&$target) {
            $target[] = compact('color', 'code');
        });

        $this->assertEquals($source, $target);

        $target = [];
        Collection::make($source)->eachSpread(function ($color) use (&$target) {
            $target[] = compact('color');
        });

        $source = [
            ['color' => 'red'],
            ['color' => 'green'],
            ['color' => 'blue'],
        ];

        $this->assertEquals($source, $target);
    }

    public function testAdd()
    {
        $this->assertEquals([1 => 'a'], Collection::make([])->add(1, 'a')->get());
        $this->assertEquals([1 => 'a'], Collection::make([1 => 'a'])->add(1, 'a')->get());
    }

    public function testCollapse()
    {
        $source = [
            [1,2,3, 'k' => 4],
            [5,6,7, 'k' => 8]
        ];

        $target = [1,2,3,5,6,7,'k' => 8];

        $this->assertEquals($target, Collection::make($source)->collapse()->get());
    }

    public function testIncrementOrDecrement()
    {
        $this->assertEquals(['k' => 2], Collection::make(['k' => 1])->increment('k')->get());
        $this->assertEquals(['k' => 2], Collection::make(['k' => 1])->increment('k', 1)->get());
        $this->assertEquals(['k' => 3], Collection::make(['k' => 1])->increment('k', 2)->get());

        $this->assertEquals(['k' => 2], Collection::make(['k' => 3])->decrement('k')->get());
        $this->assertEquals(['k' => 2], Collection::make(['k' => 3])->decrement('k', 1)->get());
        $this->assertEquals(['k' => 1], Collection::make(['k' => 3])->decrement('k', 2)->get());
    }

    public function testFirstOrLast()
    {
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $target = ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'];

        $this->assertEquals($target, Collection::make($source)->first());

        $target = ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'];

        $this->assertEquals($target, Collection::make($source)->first(function ($item) {
            return $item['id'] > 3;
        }));

        $target = ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'];

        $this->assertEquals($target, Collection::make($source)->last());

        $target = ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'];

        $this->assertEquals($target, Collection::make($source)->last(function ($item) {
            return $item['id'] < 5;
        }));
    }

    public function testPull()
    {
        $this->assertEquals(1, Collection::make(['k' => 1])->pull('k'));
        $this->assertEquals(null, Collection::make(['k' => 1])->pull('m'));
        $this->assertEquals(2, Collection::make(['k' => 1])->pull('m', 2));
    }

    public function testWhere()
    {
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $target = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W']
        ];

        $this->assertEquals($target, Collection::make($source)->where('id', 1)->get());
        $this->assertEquals($target, Collection::make($source)->where('id', '=', 1)->get());
        $this->assertEquals($target, Collection::make($source)->where('id', '==', 1)->get());
        $this->assertEquals($target, Collection::make($source)->where('id', '<=', 1)->get());
        $this->assertEquals($target, Collection::make($source)->where('id', '<', 2)->get());

        $target = [
            5 => ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M']
        ];

        $this->assertEquals($target, Collection::make($source)->where('id', '>=', 6)->get());
        $this->assertEquals($target, Collection::make($source)->where('id', '>', 5)->get());

        $target = [
            1 => ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            2 => ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            3 => ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            4 => ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            5 => ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $this->assertEquals($target, Collection::make($source)->where('id', '!=', 1)->get());
    }

    public function testWhereIn()
    {
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $target = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W']
        ];

        $this->assertEquals($target, Collection::make($source)->whereIn('id', [1])->get());
        $this->assertEquals([], Collection::make($source)->whereIn('id', [-1])->get());
    }

    public function testArrayAccess()
    {
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $target = ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'];

        $collection = Collection::make($source);

        $this->assertEquals($target, $collection[0]);
        $this->assertEquals(1, $collection[0]['id']);
        $this->assertEquals(5, $collection[5]['age']);

        $collection = Collection::make($target);

        $this->assertEquals('Larissa', $collection['name']);
    }

    public function testIterator()
    {
        $source = [
            ['id' => 1, 'name' => 'Larissa', 'age' => 21, 'gender' => 'W'],
            ['id' => 2, 'name' => 'Jacquelyn', 'age' => 16, 'gender' => 'M'],
            ['id' => 3, 'name' => 'Khaleesi', 'age' => 18, 'gender' => 'W'],
            ['id' => 4, 'name' => 'Amelie', 'age' => 84, 'gender' => 'M'],
            ['id' => 5, 'name' => '伊芙琳', 'age' => 46, 'gender' => 'W'],
            ['id' => 6, 'name' => '阿祖娜', 'age' => 5, 'gender' => 'M'],
        ];

        $collection = Collection::make($source);

        $target = '123456';
        $tmp = '';

        foreach ($collection as $item) {
            $tmp .= $item['id'];
        }

        $this->assertEquals($target, $tmp);

        $target = '011223344556';
        $tmp = '';

        foreach ($collection as $key => $item) {
            $tmp .= ($key . $item['id']);
        }

        $this->assertEquals($target, $tmp);

    }

    public function testCount()
    {
        $source = [1,2,3,4];

        $this->assertEquals(4, count(Collection::make($source)));
        $this->assertEquals(1, count(Collection::make(1)));
    }

    public function testJsonSerializable()
    {
        $source = [1,2,3,4];

        $this->assertEquals('[1,2,3,4]', json_encode(Collection::make($source)));
    }
}