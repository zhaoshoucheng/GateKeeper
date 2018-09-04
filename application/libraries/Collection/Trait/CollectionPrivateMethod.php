<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/27
 * Time: 上午10:44
 */

require_once 'ArrayRawMethod.php';

trait CollectionPrivateMethod
{
    use ArrayRawMethod;

    public static function make($data = [])
    {
        return new static($data);
    }

    public function toArray()
    {
        return $this->data;
    }

    /**
     * 遍历数组，回调函数返回 false 则跳出循环
     *
     * @param Callable $callback function($k, $v) { ... }
     * @return $this
     */
    public function foreach($callback)
    {
        foreach ($this->data as $k => $v) {
            if($callback($v, $k) === false) break;
        }
        return $this;
    }

    /**
     * 深度设置指定键值
     *
     * @param $key
     * @param $value
     * @return CollectionPrivateMethod
     */
    private function setByDot($key, $value)
    {
        $keys = explode('.', $key);
        return $this->setByArray($keys, $value);
    }

    /**
     * 根据字符串设定键值
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setByKey($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * 根据数组设定键值
     *
     * @param $keys
     * @param $value
     * @return $this|CollectionPrivateMethod
     */
    private function setByArray($keys, $value)
    {
        if(count($keys) == 0) return $this;
        if(count($keys) == 1) return $this->setByKey(current($keys), $value);
        $key = array_shift($keys);
        return $this->setByKey($key, static::make($this->getByKey($key))->setByArray($keys, $value)->toArray());
    }

    /**
     * 依据 $key 获取指定键值对，不存在则返回 $default
     *
     * @param $key
     * @param null $default
     * @return null
     */
    private function getByKey($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 根据数组或得指定键值，不存在则返回 $default|null
     *
     * @param $keys
     * @param null $default
     * @return array|null
     */
    private function getByArray($keys, $default = null)
    {
        if(!is_array($keys) || count($keys) == 0) return $this->data;
        if(count($keys) == 1) return $this->getByKey(current($keys), $default);
        $result = $this->getByKey(array_shift($keys), $default);
        return static::make($result)->getByArray($keys, $default);
    }

    /**
     * 依据 $key 获取指定数据，
     * $key 可以由多个字符串通过 . 拼接而成，
     * 从嵌套数组中获取给定键值对，不存在则返回 $default
     *
     * @param $key
     * @param null $default
     * @return array|null
     */
    private function getByDot($key = null, $default = null)
    {
        if($key == null) return $this->getByKey($key, $default);
        $keys = explode('.', $key);
        return $this->getByArray($keys, $default);
    }

    /**
     * 根据字符串判断键值是否存在
     *
     * @param $key
     * @return bool
     */
    private function hasByKey($key)
    {
        return $this->arrayKeyExists($key);
    }

    /**
     * 根据数组判断键值是否存在
     *
     * @param $keys
     * @return bool
     */
    private function hasByArray($keys)
    {
        if(!is_array($keys) || count($keys) == 0) return false;
        if(count($keys) == 1) return $this->hasByKey(current($keys));
        $key = array_shift($keys);
        return $this->hasByKey($key) &&
            static::make($this->getByKey($key))->hasByArray($keys);

    }

    /**
     * 根据 . 号判断键值是否存在
     *
     * @param $key
     * @return bool
     */
    private function hasByDot($key)
    {
        $keys = explode('.', $key);
        return $this->hasByArray($keys);
    }

    /**
     * 多维数组平铺， . 号代表深度
     *
     * @return CollectionPrivateMethod
     */
    private function dotTo()
    {
        $this->foreach(function ($v, $k) use (&$result) {
            if(is_array($v)) { static::make($v)->dotTo()->foreach(function ($v, $ke) use (&$result, $k) { }); }
            else { $result[$k] = $v; }
        });
        return new static($result);
    }

    /**
     * 返回不在参数内的键值
     *
     * @param $keys
     * @return CollectionPrivateMethod
     */
    private function exceptByArray($keys)
    {
        return $this->arrayFilter(function ($key) use ($keys) {
            return !in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * 返回不在参数内的键值
     *
     * @param $key
     * @return CollectionPrivateMethod
     */
    private function exceptByKey($key)
    {
        return $this->exceptByArray([$key]);
    }

    /**
     * 移除指定键值
     *
     * @param $key
     * @return $this
     */
    private function forgetByKey($key)
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * 根据数组移除指定深度键值
     *
     * @param $keys
     * @return $this|CollectionPrivateMethod
     */
    private function forgetByArray($keys)
    {
        if(count($keys) == 0) return $this;
        if(count($keys) == 1) return $this->forgetByKey(current($keys));
        $key = array_shift($keys);
        return $this->setByKey($key, static::make($this->getByKey($key))->forgetByArray($keys)->toArray());
    }

    /**
     * 根据 . 号分割移除指定键值
     *
     * @param $key
     * @return CollectionPrivateMethod
     */
    private function forgetByDot($key)
    {
        $keys = explode('.', $key);
        return $this->forgetByArray($keys);
    }

    /**
     * 只返回给定键值
     *
     * @param $key
     * @return CollectionPrivateMethod
     */
    private function onlyByKey($key)
    {
        return $this->onlyByArray([$key]);
    }

    /**
     * 仅返回给定数组中的键值对
     *
     * @param $keys
     * @return CollectionPrivateMethod
     */
    private function onlyByArray($keys)
    {
        return $this->arrayFilter(function ($key) use ($keys) {
            return in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * 根据字符串过滤不符合回调函数的元素
     *
     * @param $key
     * @param $compare
     * @param null $value
     * @return CollectionPrivateMethod
     */
    private function whereByKey($key, $compare, $value = null)
    {
        if($value == null) { $value = $compare; $compare = '=='; }
        return $this->arrayFilter(function ($v) use ($key, $compare, $value) {
            return compare($compare, $v[$key], $value);
        });
    }

    /**
     * 根据数组过滤不符合回调函数的元素
     *
     * @param $array
     * @return CollectionPrivateMethod
     */
    private function whereByArray($array)
    {
        $array = static::make($array)->arrayFilter(function ($v) {
            return count($v) == 2 || count($v) == 3;
        })->arrayMap(function ($v) {
            return count($v) == 2 ? [$v[0], '==', $v[1]] : $v;
        })->toArray();
        return $this->arrayFilter(function ($v) use ($array) {
            $bool = true;
            foreach ($array as $val) { $bool = $bool && compare($val[1], $v[$val[0]], $val[2]); }
            return $bool;
        });
    }

    /**
     * 检查是否包含目标键值对
     *
     * @param $key
     * @param $value
     * @return bool
     */
    private function containsByKeyValue($key, $value)
    {

        $result = false;
        $this->foreach(function ($v) use (&$result, $key, $value) {
             if(isset($v[$key]) && $v[$key] == $value) $result = true;
        });
        return $result;
    }

    /**
     * 检查是否包含目标值
     *
     * @param $value
     * @return bool
     */
    private function containsByValue($value)
    {
        return $this->hasByKey($value);
    }

    /**
     * 检查是否包含符合回调函数的数据
     *
     * @param $callback
     * @return bool
     */
    private function containsByCallback($callback)
    {
        return $this->arrayFilter($callback)->count() > 0;
    }

    /**
     * 根据指定键值分组
     *
     * @param $key
     * @param callable|null $callback
     * @param bool $preserveKeys
     * @return Collection
     */
    private function groupByKey($key, callable $callback = null, $preserveKeys = false)
    {
        $this->foreach(function ($v, $k) use (&$result, $preserveKeys, $key) {
            if($preserveKeys) $result[$v[$key]][$k] = $v; else $result[$v[$key]][] = $v;
        });
        return static::make($result)->when($callback != null, function ($c) use ($callback) {
            return $c->arrayWalk(function (&$v, $k) use ($callback) {
                $v = $callback($v, $k);
            });
        });
    }

    /**
     * 根据数组分组
     *
     * @param $keys
     * @param callable|null $callback
     * @param bool $preserveKeys
     * @return CollectionPrivateMethod
     */
    private function groupByArray($keys, callable $callback = null, $preserveKeys = false)
    {
        return count($keys) == 1 ?
            $this->groupByKey(current($keys), $callback, $preserveKeys) :
            $this->groupByKey(array_shift($keys), function ($v) use ($keys, $callback, $preserveKeys) {
                return static::make($v)->groupByArray($keys, $callback, $preserveKeys)->toArray();
            }, $preserveKeys);
    }

    /**
     * 根据回调函数分组
     *
     * @param callable $callable
     * @param callable|null $callback
     * @param bool $preserveKeys
     * @return CollectionPrivateMethod
     */
    private function groupByCallback(callable $callable, callable $callback = null, $preserveKeys = false)
    {
        $this->foreach(function ($v, $k) use (&$result, $preserveKeys, $callable) {
            if($preserveKeys) $result[$callable($v, $k)][$k] = $v; else $result[$callable($v, $k)][] = $v;
        });
        return static::make($result)->when($callback != null, function (CollectionPrivateMethod $c) use ($callback) {
            return $c->arrayWalk(function (&$v, $k) use ($callback) {
                $v = $callback($v, $k);
            });
        });
    }

    /**
     * 根据指定键排序
     *
     * @param $key
     * @return mixed
     */
    private function sortByKey($key)
    {
        return $this->groupByKey($key)->sort()->collapseTo();
    }

    /**
     * 根据指定回调函数排序
     *
     * @param callable $callback
     * @return mixed
     */
    private function sortByCallback(callable $callback)
    {
        return $this->groupByCallback($callback)->sort()->collapseTo();
    }
}