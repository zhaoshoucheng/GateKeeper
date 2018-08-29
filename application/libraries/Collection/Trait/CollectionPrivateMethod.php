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
     * 二维数组合并为一维数组
     *
     * @return CollectionPrivateMethod
     */
    private function collapseTo()
    {
        return $this->arrayReduce(function ($carry, $item) {
            return $carry->arrayMerge($item);
        }, static::make([]));
    }

    /**
     * 多维数组平铺， . 号代表深度
     *
     * @return CollectionPrivateMethod
     */
    private function dotTo()
    {
        $result = [];
        $this->foreach(function ($v, $k) use (&$result) {
            if(is_array($v)) {
                static::make($v)->dotTo()->foreach(function ($v, $ke) use (&$result, $k) {
                    $result[$k . '.' . $ke] = $v;
                });
            } else {
                $result[$k] = $v;
            }
        });
        return new static($result);
    }

    private function exceptBy($key)
    {
        $key = $key instanceof static ? $key->toArray() : $key;
        if(is_array($key)) return $this->exceptByArray($key);
        return $this->exceptByKey($key);
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

    private function exceptByKey($key)
    {
        return $this->exceptByArray([$key]);
    }

    /**
     * 获取第一个回调函数返回 true 的元素
     *
     * @param null $callback
     * @param null $default
     * @return mixed|null
     */
    private function firstOn(callable $callback = null, $default = null)
    {
        if($callback == null) return $this->empty() ? $default : $this->reset();
        $this->foreach(function ($v, $k) use (&$res, $callback) {
            if($callback($v, $k)) {$res = $v; return false;}
        });
        return $res ?? $default;
    }

    /**
     * 将数组摊开
     *
     * @return CollectionPrivateMethod
     */
    private function flattenTo()
    {
        return $this->dotTo()->arrayValues();
    }

    private function forgetBy($key)
    {
        $key = $key instanceof static ? $key->toArray() : $key;
        if(is_array($key)) return $this->forgetByArray($key);
        return $this->forgetByDot($key);
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
     * 获取符合回调函数条件的最后一个元素
     *
     * @param null $callback
     * @param null $default
     * @return mixed|null
     */
    private function lastOn($callback = null, $default = null)
    {
        if($callback == null) return $this->empty() ? $default : $this->end();
        return $this->arrayReverse()->firstOn($callback);
    }

    private function onlyBy($key)
    {
        return is_array($key) ? $this->onlyByArray($key) : $this->onlyByKey($key);
    }

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
     * 获取数组中二层深度指定键值
     *
     * @param $key
     * @return CollectionPrivateMethod
     */
    private function pluckBy($key)
    {
        return $this->arrayMap(function ($v) use ($key) {
            return $v[$key] ?? null;
        })->arrayFilter()->arrayValues();
    }

    /**
     * 在数组最前方插入
     *
     * @param $value
     * @param null $key
     * @return $this|CollectionPrivateMethod
     */
    private function prependOn($value, $key = null)
    {
        if($key == null) return $this->arrayUnshift($value);
        return static::make([$key => $value])->arrayMerge($this->toArray());
    }

    /**
     * 移除指定元素
     *
     * @param null $key
     * @return null
     */
    private function pullOn($key = null)
    {
        $result = $this->getByKey($key);
        $this->forgetByKey($key);
        return $result;
    }

    /**
     * 过滤数据
     *
     * @param $key
     * @param null $compare
     * @param null $value
     * @return CollectionPrivateMethod
     */
    private function whereBy($key, $compare = null, $value = null)
    {
        if(is_array($key)) return $this->whereByArray($key);
        return $this->whereByKey($key, $compare, $value);
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
     * 求平均值
     *
     * @param null $key
     * @return float|int
     */
    private function avgBy($key = null)
    {
        $array = $key == null ? $this : $this->arrayColumn($key);
        return $array->arraySum() / $array->count();
    }

    private function containsBy($param, $value = null)
    {
        if($value !== null) return $this->containsByKeyValue($param, $value);
        if(is_callable($param)) return $this->containsByCallback($param);
        return $this->containsByValue($param);
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

    public function groupBy($param, $callback = null, $preserveKeys = false)
    {
        if(is_callable($param)) return $this->groupByCallback($param, $callback, $preserveKeys);
        if(is_array($param)) return $this->groupByArray($param, $callback, $preserveKeys);
        return $this->groupByKey($param, $callback, $preserveKeys);
    }

    /**
     * 根据指定键值分组
     *
     * @param $key
     * @param callable|null $callback
     * @param bool $preserveKeys
     * @return CollectionPrivateMethod
     */
    private function groupByKey($key, callable $callback = null, $preserveKeys = false)
    {
        $result = [];
        $this->foreach(function ($v, $k) use (&$result, $preserveKeys, $key) {
            if(isset($v[$key]))
                if($preserveKeys) $result[$v[$key]][$k] = $v;
                else $result[$v[$key]][] = $v;
        });

        if($callback != null) {
            foreach ($result as $k => $v) {
                $result[$k] = $callback($v, $k);
            }
        }
        return new static($result);
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
        if(count($keys) == 1) return $this->groupByKey(current($keys), $callback, $preserveKeys);
        $key = array_shift($keys);
        return $this->groupByKey($key, function ($v, $k) use ($keys, $callback, $preserveKeys) {
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
        $result = [];
        $this->foreach(function ($v, $k) use (&$result, $preserveKeys, $callable) {
                if($preserveKeys) $result[$callable($v, $k)][$k] = $v;
                else $result[$callable($v, $k)][] = $v;
        });

        if($callback != null) {
            foreach ($result as $k => $v) {
                $result[$k] = $callback($v, $k);
            }
        }
        return new static($result);
    }

    private function sortByKey($key)
    {
        return $this->arrayColumn(null, $key)->sort()->arrayValues();
    }

    private function sortByCallback(callable $callback)
    {
        return $this->groupByCallback($callback)->sort()->arrayValues();
    }
}