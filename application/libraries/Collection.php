<?php
/**
 * Created by PhpStorm.
 * User: LiCxi
 * Date: 2018/8/23
 * Time: 下午4:16
 */

if(!defined('COLLECTION_DIR'))
    define('COLLECTION_DIR', __DIR__ . '/Collection/');

require COLLECTION_DIR . 'tools.php';
require COLLECTION_DIR . 'Trait/ArrayRawMethod.php';
require COLLECTION_DIR . 'Interface/CollectionInterface.php';

class Collection implements CollectionInterface
{
    use ArrayRawMethod;

    /**
     * 求数组的平均值，并对结果进行回调处理（保留两位小数...）
     *
     * @param string|int|null $key 二维数组元素的键名，求一位数组则设为 null
     * @param callable|null $callback 处理结果的回调函数，可用来处理小数位数
     * @return float|int|null
     */
    public function avg($key = null, $callback = null)
    {
        /*
         * 获取求平均值的目标数组
         */
        $array = $key == null ?
            $this :
            $this->column($key);

        /*
         * 如果数组为空则返回 null
         */
        if($array->isEmpty())
            return null;

        /*
         * 如果传入函数参数不为 null 则回调处理结果
         */
        return $callback == null ?
            $array->sum() / $array->count() :
            $callback($array->sum() / $array->count());
    }

    /**
     * 遍历数组，回调函数返回 false 则跳出循环
     *
     * @param Callable $callback function($k, $v) { ... }
     * @return $this
     */
    public function each($callback)
    {
        foreach ($this->data as $k => $v) {
            /*
             * 使用回调函数对元素键值对进行处理，如果返回 false 则跳出循环
             */
            if(call_user_func($callback, $v, $k) === false) break;
        }
        return $this;
    }

    /**
     * 移除指定键值，支持 . 表示深度，可关闭对 . 的支持
     *
     * @param string|int $key 想要移除的键名，可包含 .
     * @param bool $dotKey 是否关闭对 . 的支持
     * @return Collection
     */
    public function forget($key, $dotKey = true)
    {
        $keys = $dotKey && is_string($key)
            ? explode('.', $key)
            : [$key];

        $array = &$this->data;

        while (count($keys) > 1) {
            $part = array_shift($keys);

            if (isset($array[$part]) && is_array($array[$part])) {
                $array = &$array[$part];
            } else {
                return $this;
            }
        }

        unset($array[array_shift($keys)]);

        return $this;
    }

    /**
     * 获取集合指定键值的元素，支持 . 表示深度，可关闭对 . 的支持
     *
     * @param null $key 想要获取的键名，可包含 . ，为 null 则获取完整的集合元素
     * @param null $default 集合中不存在目标元素则返回 $default
     * @param bool $dotKey 是否关闭对 . 的支持
     * @return array|mixed
     */
    public function get($key = null, $default = null, $dotKey = true)
    {
        if($key === null)
            return $this->data;

        if(!$dotKey || strpos($key, '.') === false) {
            return $this->data[$key] ?? $default;
        }

        $array = $this->data;

        foreach (explode('.', $key) as $segment) {
            if(is_array($array) && isset($array[$segment])) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * 将集合按照指定参数分组
     *
     * @param string|int|array|callable $param 分组依据的字段
     * @param callable|null $callback 分组后每组数据的处理
     * @param bool $preserveKey 分组后是否保留原有的键名
     * @return Collection|mixed 返回分组后的集合对象
     */
    public function groupBy($param, $callback = null, $preserveKey = false)
    {
        /*
         * 第一个参数传入的是函数，则按照函数分组
         */
        if(is_callable($param))
            return $this->groupByCallback($param, $callback, $preserveKey);
        /*
         * 第一个参数传入的是数组，则按照数组递归分组
         */
        if(is_array($param))
            return $this->groupByArray($param, $callback, $preserveKey);

        /*
         * 其他类型的参数则按照键名分组
         */
        return $this->groupByKey($param, $callback, $preserveKey);
    }

    /**
     * 判断集合是否存在指定键名，支持 . 表示深度
     *
     * @param array|string $key 指定键名
     * @param mixed $value 指定键值
     * @param bool $dotKey 是否开启 . 表示深度
     * @return bool|mixed
     */
    public function has($key, $value = null, $dotKey = true)
    {
        /*
         * 如果 $key 为 string 类型 且 开启 . 表示深度
         * 则将字符串按 . 分割为数组
         * 否则将其本身作为数组元素
         */
        if(is_string($key))
            $key = $dotKey
                ? explode('.', $key)
                : [$key];

        /*
         * 如果 $key 既不是 string，也不是 numeric
         * 则返回 null
         */
        elseif(!is_numeric($key))
            return null;

        return $value === null
            ? $this->hasByArrayKey($key)
            : $this->hasByArrayKeyValue($key, $value);
    }

    /**
     * 设置指定键值，支持 . 表示深度
     *
     * @param string $key
     * @param mixed $value
     * @param bool $dotKey
     * @return Collection|mixed|null
     */
    public function set($key, $value, $dotKey = true)
    {
        /*
         * 如果 $key 为 string 类型 且 开启 . 表示深度
         * 则将字符串按 . 分割为数组
         * 否则将其本身作为数组元素
         */
        if(is_string($key))
            $key = $dotKey
                ? explode('.', $key)
                : [$key];

        /*
         * 如果 $key 既不是 string，也不是 numeric
         * 则返回 null
         */
        elseif(!is_numeric($key))
            return null;

        return $this->setByArray($key, $value);
    }

    /**
     * 将二维数组按照指定字段排序
     *
     * @param string|int|callable $param 排序字段
     * @param int $arraySortOrder 排序方式
     * @return Collection|mixed
     */
    public function sortBy($param, $arraySortOrder = SORT_ASC)
    {
        return is_callable($param)

            /*
             * 根据函数参数的返回值进行排序
             * 例：
             * [
             *     ['id' => 1, 'name' => 'Joe'],
             *     ['id' => 2, 'name' => 'John']
             * ]
             * 函数格式：
             * function ($v) {
             *     return $v['id'];
             * }
             */
            ? $this->sortByCallback($param, $arraySortOrder)

            /**
             * 根据集合元素的某一个键值进行排序
             */
            : $this->sortByKey($param, $arraySortOrder);
    }

    public function all()
    {
        return $this->get();
    }

    /**
     * 向数组中添加指定键值对
     * 不支持 . 表示深度
     *
     * @param $key
     * @param $value
     * @return Collection|mixed|null
     */
    public function add($key, $value)
    {
        return $this->has($key, null, false)

            /**
             * 目标键已存在，不进行任何操作
             */
            ? $this

            /**
             * 目标建不存在则创建
             */
            : $this->set($key, $value, false);
    }

    /**
     * 遍历集合的每个元素，
     * 并将每个元素（格式必须为数组）作为传入的函数的参数进行运算
     * 例：
     * [
     *     ['id' => 1, 'name' => 'Joe'],
     *     ['id' => 2, 'name' => 'John']
     * ]
     * 函数格式：
     * function ($id, $name) {
     *     //...
     * }
     *
     * @param $callback
     * @return Collection
     */
    public function eachSpread($callback)
    {
        return $this->each(function ($v) use ($callback) {
            return call_user_func_array($callback, $v);
        });
    }

    /**
     * 将二位数组铺开，
     * 保留原有非数字键，
     * 键名冲突则后者覆盖前者
     *
     * @return Collection
     */
    public function collapse()
    {
        $result = [];

        foreach ($this->data as $datum)
            $result = array_merge($result, $datum);

        return new static($result);
    }

    /**
     * 指定键值递减
     * 不支持 . 表示深度
     *
     * @param $key
     * @param int $value
     */
    public function decrement($key, $value = 1)
    {
        $this->set($key, $this->get($key, 0, false) - $value, false);
    }

    /**
     * 指定键值递增
     * 不支持 . 表示深度
     *
     * @param $key
     * @param int $value
     */
    public function increment($key, $value = 1)
    {
        $this->set($key, $this->get($key, 0, false) + $value, false);
    }

    public function implode($key, $gule = null)
    {
        return $gule == null
            ? implode($key, $this->data)
            : $this->column($key)->implode($gule);
    }

    public function isEmpty()
    {
        return $this->empty();
    }

    public function keysOfMaxValue()
    {
        return $this->keys($this->max());
    }

    public function last(callable $callback = null, $default = null)
    {
        if($callback == null || !is_callable($callback))
            return empty($this->data)
                ? $default
                : end($this->data);

        return $this->reverse()->first($callback);
    }

    public function first(callable $callback = null, $default = null)
    {
        if($callback == null || !is_callable($callback))
            return empty($this->data)
                ? $default
                : reset($this->data);

        foreach ($this->data as $key => $datum)
            if(call_user_func($callback, $datum, $key))
                return $datum;

        return $default;
    }

    public function pull($key, $default = null)
    {
        $res = $this->get($key, $default, false);
        $this->forget($key, false);
        return $res;
    }

    public function toJson()
    {
        return $this->jsonEncode();
    }

    public function when($bool, callable $callable)
    {
        if($bool)
            return $callable($this);

        return $this;
    }

    public function where($key, $compare = null, $value = null)
    {
        return $this->whereByKey($key, $compare, $value);
    }

    public function whereIn($key, $values)
    {
        return $this->filter(function ($v) use ($key, $values) {
            return in_array($v[$key], $values);
        });
    }

    protected function concat($array)
    {
        array_merge($this->data, array_values($array));

        return $this;
    }

    protected function except(...$keys)
    {
        return $this->filter(function ($k) use ($keys) {
            return !in_array($k, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function only(...$keys)
    {
        return $this->filter(function ($k) use ($keys) {
            return in_array($k, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @deprecated 方法已过期（可用 column 代替）
     * @param $key
     * @return Collection
     */
    protected function pluck($key)
    {
        return $this->column($key);
    }

    protected function prepend($value, $key = null)
    {
        if($key == null) {
            $this->unshift($value);
            return $this;
        };

        return static::make(array_merge([$value => $key, $this->data]));
    }

    protected function divide()
    {
        return [$this->keys(), $this->values()];
    }

    private function forgetByKey($key)
    {
        unset($this->data[$key]);
        return $this;
    }

    private function forgetByArray($keys)
    {
        if(empty($keys))
            return $this;

        if(count($keys) == 1)
            return $this->forgetByKey(current($keys));

        $key = array_shift($keys);
        return $this->set($key, static::make($this->get($key))->forgetByArray($keys)->get());
    }

    private function getByKey($key, $default)
    {
        return $this->data[$key] ?? $default;
    }

    private function getByArray($keys, $default)
    {
        if(empty($keys))
            return $this->data;

        if(count($keys) == 1)
            return $this->getByKey(current($keys), $default);

        return static::make($this->getByKey(array_shift($keys), $default))
                ->getByArray($keys, $default);
    }

    private function groupByKey($key, callable $callback = null, $preserveKey = false)
    {
        $this->each(function ($v, $k) use (&$result, $preserveKey, $key) {
            $preserveKey
                ? $result[static::make($v)->get($key)][$k] = $v
                : $result[static::make($v)->get($key)][] = $v;
        });
        return static::make($result)->when($callback != null, function (Collection $c) use ($callback) {
            return $c->arrayWalk(function (&$v, $k) use ($callback) {
                $v = $callback($v, $k);
            });
        });
    }

    private function groupByArray($keys, callable $callback = null, $preserveKey = false)
    {
        if(empty($keys)) return [];
        $key = array_shift($keys);
        return count($keys) == 0
            ? (is_string($key) || is_numeric($key)
                ? $this->groupByKey($key, $callback, $preserveKey)
                : $this->groupByCallback($key, $callback, $preserveKey))
            : (is_string($key) || is_numeric($key)
                ? $this->groupByKey($key, function ($v) use ($keys, $callback, $preserveKey) {
                    return static::make($v)->groupByArray($keys, $callback, $preserveKey)->get();
                }, $preserveKey)
                : $this->groupByCallback($key, function ($v) use ($keys, $callback, $preserveKey) {
                    return static::make($v)->groupByArray($keys, $callback, $preserveKey)->get();
                }, $preserveKey));
    }

    private function groupByCallback(callable $callable, callable $callback = null, $preserveKey = false)
    {
        $this->each(function ($v, $k) use (&$result, $preserveKey, $callable) {
            if($preserveKey) $result[$callable($v, $k)][$k] = $v; else $result[$callable($v, $k)][] = $v;
        });
        return static::make($result)->when($callback != null, function (Collection $c) use ($callback) {
            return $c->arrayWalk(function (&$v, $k) use ($callback) {
                $v = $callback($v, $k);
            });
        });
    }

    private function hasByKey($key)
    {
        if(is_array($key))
            return $this->hasByArrayKey($key);

        return $this->keyExists($key);
    }

    private function hasByKeyValue($key, $value)
    {
        return $this->hasByKey($key)
            && $this->getByKey($key, null) === $value;
    }

    private function hasByArrayKey($keys)
    {
        if(empty($keys))
            return false;

        if(count($keys) == 1)
            return $this->hasByKey(current($keys));

        $key = array_shift($keys);
        return $this->hasByKey($key) &&
            static::make($this->get($key, null, false))->hasByArrayKey($keys);
    }

    private function hasByArrayKeyValue($keys, $value)
    {
        if(empty($keys))
            return false;

        if(count($keys) == 1)
            return $this->hasByKeyValue(current($keys), $value);

        $key = array_shift($keys);
        return $this->hasByKey($key) &&
            static::make($this->get($key, null, false))->hasByArrayKeyValue($keys, $value);
    }

    private function setByKey($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    private function setByArray($keys, $value)
    {
        if(empty($keys))
            return $this;

        if(count($keys) == 1)
            return $this->setByKey(current($keys), $value);

        $key = array_shift($keys);
        return $this->setByKey($key,
            static::make($this->getByKey($key, []))->setByArray($keys, $value)->get());
    }

    private function sortByKey($key, $arraySortOrder = SORT_ASC)
    {
        switch ($arraySortOrder) {
            case SORT_ASC:
                return $this->groupBy($key)->ksort()->collapse();
            case SORT_DESC:
                return $this->groupBy($key)->krsort()->collapse();
            default:
                return $this;
        }
    }

    private function sortByCallback(callable $callback, $arraySortOrder = SORT_ASC)
    {
        return $this->sortByKey($callback, $arraySortOrder);
    }

    private function whereByKey($key, $compare, $value = null)
    {
        if($value == null)
            list($value, $compare) = [$compare, '=='];

        return $this->filter(function ($v) use ($key, $compare, $value) {
            return compare($compare, $v[$key], $value);
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

    public function reverse($preserveKey = false)
    {
        return $this->arrayReverse($preserveKey);
    }

    public function search($needle, $strict = false)
    {
        return $this->arraySearch($needle, $strict);
    }

    public function shift()
    {
        return $this->arrayShift();
    }

    public function slice($offset, $length = null, $preserveKey = false)
    {
        return $this->arraySlice($offset, $length, $preserveKey);
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