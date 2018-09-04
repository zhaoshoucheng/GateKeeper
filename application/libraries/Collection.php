<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/23
 * Time: 下午4:16
 */

if(!defined('COLLECTION_DIR'))
    define('COLLECTION_DIR', __DIR__ . '/Collection/');

require COLLECTION_DIR .'tools.php';
require COLLECTION_DIR .'Trait/CollectionPrivateMethod.php';

class Collection
{
    use CollectionPrivateMethod;

    public function __toString()
    {
        return dump($this->toArray());
    }

    public function add($key, $value)
    {
        return !$this->has($key) ?
            $this->set($key, $value) :
            $this;
    }

    public function all()
    {
        return $this->toArray();
    }

    public function avg($key = null, callable $callback = null)
    {
        $array = $key == null ?
            $this :
            $this->column($key);

        $cnt =  $array->count();
        if($cnt == 0) return 0;

        return $callback == null ?
            $array->sum() / $cnt :
            $callback($array->sum() / $cnt);
    }

    public function concat($array)
    {
        return static::make($array)
            ->each(function ($v) {
                $this->push($v);
            });
    }

    public function contains($param, $value = null)
    {
        return $this->when($value !== null, function (Collection $c) use ($param, $value) {
            return $c->containsByKeyValue($param, $value);
        })->when(is_callable($param), function (Collection $c) use ($param) {
            return $c->containsByCallback($param);
        })->when($value === null && !is_callable($param), function (Collection $c) use ($param) {
            return $c->containsByValue($param);
        });
    }

    public function collapse()
    {
        return $this->reduce(function (Collection $carry, $item) {
            return $carry->arrayMerge($item);
        }, static::make([]));
    }

    public function divide()
    {
        return [$this->keys(), $this->values()];
    }

    public function dot()
    {
        return $this->dotTo();
    }

    public function decrement($key, $value = 1)
    {
        $this->set($key, $this->get($key, 0) - $value);
    }

    public function each($callback)
    {
        return $this->foreach($callback);
    }

    public function eachSpread($callback)
    {
        return $this->each(function ($v) use ($callback) {
            return call_user_func_array($callback, $v);
        });
    }

    public function except($keys)
    {
        if(is_array($keys)) return $this->exceptByArray($keys);
        return $this->exceptByKey($keys);
    }

    public function first(callable $callback = null, $default = null)
    {
        if($callback == null) return $this->empty() ? $default : $this->reset();
        $this->foreach(function ($v, $k) use (&$res, $callback) {
            if($callback($v, $k)) {$res = $v; return false;}
        });
        return $res ?? $default;
    }

    public function flatten()
    {
        return $this->dot()->values();
    }

    public function forget($key)
    {
        $key = $key instanceof static ? $key->toArray() : $key;
        if(is_array($key)) return $this->forgetByArray($key);
        return $this->forgetByDot($key);
    }

    public function forPage($offset, $limit)
    {
        return $this->arrayFilter(function ($k) use ($offset, $limit) {
            return $k >= $offset && $k < $offset + $limit;
        }, ARRAY_FILTER_USE_KEY);
    }

    public function get($key = null, $default = null)
    {
        return $this->getByDot($key, $default);
    }

    public function groupBy($param, $callback = null, $preserveKeys = false)
    {
        if(is_callable($param)) return $this->groupByCallback($param, $callback, $preserveKeys);
        if(is_array($param)) return $this->groupByArray($param, $callback, $preserveKeys);
        return $this->groupByKey($param, $callback, $preserveKeys);
    }

    public function has($key)
    {
        return $this->hasByDot($key);
    }

    public function increment($key, $value = 1)
    {
        $this->set($key, $this->get($key, 0) + $value);
    }

    public function implode($key, $gule = null)
    {
        return $gule == null ? implode($key, $this->data) : $this->column($key)->implode($gule);
    }

    public function isEmpty()
    {
        return $this->empty();
    }

    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    public function keyBy($key)
    {
        return is_callable($key) ? $this->groupByCallback($key) : $this->groupByKey($key);
    }

    public function keysOfMaxValue()
    {
        return $this->keys($this->max());
    }

    public function last($callback = null, $default = null)
    {
        if($callback == null)
            return $this->empty() ?
                $default :
                $this->end();

        return $this->reverse()->first($callback);
    }

    public function only($key)
    {
        return is_array($key) ?
            $this->onlyByArray($key) :
            $this->onlyByKey($key);
    }

    public function pluck($key)
    {
        return $this->map(function ($v) use ($key) {
            return $v[$key] ?? null;
        })->filter()->values();
    }

    public function prepend($value, $key = null)
    {
        if($key == null) { $this->unshift($value); return $this;};
        return static::make([$key => $value])->merge($this->toArray());
    }

    public function pull($key)
    {
        $result = $this->get($key);
        $this->forget($key);
        return $result;
    }

    public function set($key, $value)
    {
        return $this->setByDot($key, $value);
    }

    public function sortBy($param)
    {
        return is_callable($param) ? $this->sortByCallback($param) : $this->sortByKey($param);
    }

    public function take($num)
    {
        return $num > 0 ?
            $this->slice(0, $num) :
            $this->reverse()->slice(0, -$num);
    }

    public function toJson()
    {
        return $this->jsonEncode();
    }

    public function unless($bool, callable $callable)
    {
        if(!$bool)
            $callable($this);

        return $this;
    }

    public function when($bool, callable $callable)
    {
        if($bool)
            return $callable($this);

        return $this;
    }

    public function where($key, $compare = null, $value = null)
    {
        return is_array($key) ?
            $this->whereByArray($key) :
            $this->whereByKey($key, $compare, $value);
    }

    public function whereIn($key, $values)
    {
        return $this->filter(function ($v) use ($key, $values) {
            return in_array($v[$key], $values);
        });
    }

    public function changeKeyCase($case = CASE_LOWER)
    {
        return $this->arrayChangeKeyCase($case);
    }

    public function chunk($num, $preserveKey = false)
    {
        return $this->arrayChunk($num, $preserveKey);
    }

    public function column($columnKey, $indexKey = null)
    {
        return $this->arrayColumn($columnKey, $indexKey);
    }

    public function combine($array)
    {
        return $this->arrayCombine($array);
    }

    public function countValues()
    {
        return $this->arrayCountValues();
    }

    public function diffAssoc($array, ...$_)
    {
        return $this->arrayDiffAssoc($array, ...$_);
    }

    public function diffKey($array, ...$_)
    {
        return $this->arrayDiffKey($array, ...$_);
    }

    public function diffUassoc($array, ...$_)
    {
        return $this->arrayDiffUassoc($array, ...$_);
    }

    public function diffUkey($array, ...$_)
    {
        return $this->arrayDiffUkey($array, ...$_);
    }

    public function diff($array, ...$_)
    {
        return $this->arrayDiff($array, ...$_);
    }

    public function fillKeys($keys, $value)
    {
        return $this->arrayFillKeys($keys, $value);
    }

    public function fill($start_index, $num, $value)
    {
        return $this->arrayFill($start_index, $num, $value);
    }

    public function filter($callback = null, $flag = 0)
    {
        return $this->arrayFilter($callback, $flag);
    }

    public function flip()
    {
        return $this->arrayFlip();
    }

    public function intersectAssoc($array, ...$_)
    {
        return $this->arrayIntersectAssoc($array, ...$_);
    }

    public function intersectKey($array, ...$_)
    {
        return $this->arrayIntersectKey($array, ...$_);
    }

    public function intersectUassoc($array, $keyCompareFunc ,...$_)
    {
        return $this->arrayIntersectUassoc($array, $keyCompareFunc ,...$_);
    }

    public function intersectUkey($array, $keyCompareFunc ,...$_)
    {
        return $this->arrayIntersectUkey($array, $keyCompareFunc ,...$_);
    }

    public function intersect($array)
    {
        return $this->arrayIntersect($array);
    }

    public function keyExists($key)
    {
        return $this->arrayKeyExists($key);
    }

    public function keyFirst()
    {
        return $this->arrayKeyFirst();
    }

    public function keyLast()
    {
        return $this->arrayKeyLast();
    }

    public function keys($searchValue = null, $strict = false)
    {
        return $this->arrayKeys($searchValue, $strict);
    }

    public function map($callback, ...$_)
    {
        return $this->arrayMap($callback, ...$_);
    }

    public function median($key = null, $callback = null)
    {
        return $this->avg($key, $callback);
    }

    public function mergeRecursive(...$_)
    {
        return $this->arrayMergeRecursive(...$_);
    }

    public function merge(...$_)
    {
        return $this->arrayMerge(...$_);
    }

    public function mode($key = null)
    {
        return ($key ==  null ? $this : $this->column($key))->countValues()->keysOfMaxValue();
    }

    public function multisort($arraySortOrder = SORT_ASC, $arraySortFlags = SORT_REGULAR, ...$_)
    {
        return $this->arrayMultisort($arraySortOrder, $arraySortFlags, ...$_);
    }

    public function pad($size, $value)
    {
        return $this->arrayPad($size, $value);
    }

    public function pop()
    {
        return $this->arrayPop();
    }

    public function product()
    {
        return $this->arrayProduct();
    }

    public function push($value, ...$_)
    {
        return $this->arrayPush($value, ...$_);
    }

    public function rand($num = 1)
    {
        return $this->arrayRand($num);
    }

    public function reduce($callback, $initial = null)
    {
        return $this->arrayReduce($callback, $initial);
    }

    public function replaceRecursive($array, ...$_)
    {
        return $this->arrayReplaceRecursive($array, ...$_);
    }

    public function replace($array, ...$_)
    {
        return $this->arrayReplace($array, ...$_);
    }

    public function reverse($preserveKeys = false)
    {
        return $this->arrayReverse($preserveKeys);
    }

    public function search($needle, $strict = false)
    {
        return $this->arraySearch($needle, $strict);
    }

    public function shift()
    {
        return $this->arrayShift();
    }

    public function slice($offset, $length = null, $preserveKeys = false)
    {
        return $this->arraySlice($offset, $length, $preserveKeys);
    }

    public function splice($offset, $length = null, $replacement = [])
    {
        return $this->arraySplice($offset, $length, $replacement);
    }

    public function sum()
    {
        return $this->arraySum();
    }

    public function udiffAssoc($array, $valueCompareFunc, ...$_)
    {
        return $this->arrayUdiffAssoc($array, $valueCompareFunc, ...$_);
    }

    public function udiffUAssoc($array, $valueCompareFunc, $keyCompareFunc, ...$_)
    {
        return $this->arrayUdiffUAssoc($array, $valueCompareFunc, $keyCompareFunc, ...$_);
    }

    public function udiff($array, $valueCompareFunc, ...$_)
    {
        return $this->arrayUdiff($array, $valueCompareFunc, ...$_);
    }

    public function uintersectAssoc($array, $valueCompareFunc, ...$_)
    {
        return $this->arrayUintersectAssoc($array, $valueCompareFunc, ...$_);
    }

    public function uintersectUassoc($array, $valueCompareFunc, $keyCompareFunc, ...$_)
    {
        return $this->arrayUintersectUassoc($array, $valueCompareFunc, $keyCompareFunc, ...$_);
    }

    public function uintersect($array, $valueCompareFunc, ...$_)
    {
        return $this->arrayUintersect($array, $valueCompareFunc, ...$_);
    }

    public function unique($sortFlags = SORT_STRING)
    {
        return $this->arrayUnique($sortFlags);
    }

    public function unshift($value, ...$_)
    {
        return $this->arrayUnshift($value, ...$_);
    }

    public function values()
    {
        return $this->arrayValues();
    }

    public function walkRecursive($callback, $userdata = null)
    {
        return $this->arrayWalkRecursive($callback, $userdata);
    }

    public function walk($callback, $userdata = null)
    {
        return $this->arrayWalk($callback, $userdata);
    }
}