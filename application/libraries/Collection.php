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

    /**
     * 获取指定键值
     *
     * @param null $key
     * @return null
     */
    public function get($key = null)
    {
        return $this->getByDot($key);
    }

    /**
     * 设置键值
     *
     * @param $key
     * @param $value
     * @return Collection
     */
    public function set($key, $value)
    {
        return $this->setByDot($key, $value);
    }

    /**
     * 判断数组中是否存在指定键
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return $this->hasByDot($key);
    }

    public function add($key, $value)
    {
        if(!$this->has($key))
            $this->set($key, $value);
        return $this;
    }

    public function collapse()
    {
        return $this->collapseTo();
    }

    public function divide()
    {
        return [$this->arrayKeys(), $this->arrayValues()];
    }

    public function dot()
    {
        return $this->dotTo();
    }

    public function except($keys)
    {
        return $this->exceptByArray($keys);
    }

    public function first($callback = null, $default = null)
    {
        return $this->firstOn($callback, $default);
    }

    public function flatten()
    {
        return $this->flattenTo();
    }

    public function forget($key)
    {
        return $this->forgetByDot($key);
    }

    public function last($callback = null, $default = null)
    {
        return $this->lastOn($callback, $default);
    }

    public function prepend($value, $key = null)
    {
        return $this->prependOn($value, $key);
    }

    public function pull($key)
    {
        return $this->pullOn($key);
    }

    public function where($key, $compare = null, $value = null)
    {
        return $this->whereBy($key, $compare, $value);
    }

    public static function wrap($value)
    {
        return is_array($value) ? $value : [$value];
    }

    public function all()
    {
        return $this->toArray();
    }

    public function avg($key = null)
    {
        return $this->avgBy($key);
    }

    public function chunk($num)
    {
        return $this->arrayChunk($num);
    }

    public function combine($array)
    {
        $array = $array instanceof static ? $array->toArray() : $array;
        return $this->arrayCombine($array);
    }

    public function cancat($array)
    {
        $collect = $array instanceof static ? $array : static::make($array);
        $collect->foreach(function ($v) {
            $this->arrayPush($v);
        });
        return $this;
    }

    public function contains($param, $value = null)
    {
        if($value !== null) return $this->containsByKeyValue($param, $value);
        if(is_callable($param)) return $this->containsByCallback($param);
        return $this->containsByValue($param);
    }

    public function diff($array)
    {
        $array = $array instanceof static ? $array->toArray() : $array;
        return $this->arrayDiff($array);
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

    public function filter($callback = null)
    {
        return $this->arrayFilter($callback);
    }

    public function flip()
    {
        return $this->arrayFlip();
    }

    public function forPage($offset, $limit)
    {
        return $this->arrayFilter(function ($k) use ($offset, $limit) {
            return $k >= $offset && $k < $offset + $limit;
        }, ARRAY_FILTER_USE_KEY);
    }

    public function groupBy($param, $callback = null, $preserveKeys = false)
    {
        if(is_callable($param)) return $this->groupByCallback($param, $callback, $preserveKeys);
        if(is_array($param)) return $this->groupByArray($param, $callback, $preserveKeys);
        return $this->groupByString($param, $callback, $preserveKeys);
    }
}