<?php
/**
 * Created by PhpStorm.
 * User: LiCxi
 * Date: 2018/8/23
 * Time: 下午4:16
 */

namespace Licxisky\Collection;

use Licxisky\Collection\Collection\CollectionInterface;
use Licxisky\Collection\Collection\ArrayRawMethod;
use ArrayAccess;
use Iterator;
use Countable;
use JsonSerializable;


class Collection implements CollectionInterface, ArrayAccess, Iterator, Countable, JsonSerializable
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
     */
    public function each($callback)
    {
        foreach ($this->data as $k => $v) {
            /*
             * 使用回调函数对元素键值对进行处理，如果返回 false 则跳出循环
             */
            if(call_user_func($callback, $v, $k) === false) break;
        }
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
        if(! is_array($param)) {
            $param = [$param];
        }

        $results = [];

        $segment = array_shift($param);

        if(is_string($segment) || is_numeric($segment)) {
            $segment = function ($v) use ($segment) {
                return $v[$segment];
            };
        }

        foreach ($this->data as $key => $value) {
            if($preserveKey) {
                $results[call_user_func($segment, $value, $key)][$key] = $value;
            } else {
                $results[call_user_func($segment, $value, $key)][] = $value;
            }
        }

        if(count($param) == 0) {
            if($callback != null && is_callable($callback)) {
                $results = array_map(function ($result) use ($callback) {
                    return call_user_func($callback, $result);
                }, $results);
            }

            return new static($results);
        } else {
            return static::make($results)->map(function ($collect) use ($param, $callback, $preserveKey) {
                return static::make($collect)->groupBy($param, $callback, $preserveKey)->get();
            });
        }
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
        if(!$dotKey) {
            if($value == null) {
                return isset($this->data[$key]);
            } else {
                return isset($this->data[$key])
                    && $this->data[$key] == $value;
            }
        }

        if(is_string($key)) {
            $keys = explode('.', $key);
        } elseif (is_numeric($key)) {
            $keys = [$key];
        } else {
            return false;
        }

        $array = &$this->data;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if(isset($array[$key])) {
                $array = &$array[$key];
            } else {
                return false;
            }
        }

        $key = array_shift($keys);

        if(!isset($array[$key]))
            return false;

        if($value != null) {
            return $array[$key] == $value;
        }

        return true;
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
        if(!$dotKey) {
            $this->data[$key] = $value;
            return $this;
        }

        if(is_string($key)) {
            $keys = explode('.', $key);
        } elseif (is_numeric($key)) {
            $keys = [$key];
        } else {
            return $this;
        }

        $array = &$this->data;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if(isset($array[$key]) && is_array($array[$key])) {
                $array = &$array[$key];
            } else {
                $array[$key] = [];
                $array = &$array[$key];
            }
        }

        $array[array_shift($keys)] = $value;

        return $this;
    }

    /**
     * 将二维数组按照指定字段排序
     *
     * @param string|int|callable $param 排序字段
     * @param int $arraySortOrder 排序方式
     * @param int $arraySortFlag 排序格式
     * @return $this|Collection|mixed
     */
    public function sortBy($param, $arraySortOrder = SORT_ASC, $arraySortFlag = SORT_REGULAR)
    {
        if(is_string($param) || is_numeric($param)) {
            $param = function ($v) use ($param) {
                return $v[$param] ?? null;
            };
        }

        foreach ($this->data as $key => $datum) {
            $column[] = call_user_func($param, $datum, $key);
        }

        array_multisort($column, $arraySortOrder, $arraySortFlag, $this->data);

        return $this;
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
        if(! isset($this->data[$key])) {
            $this->data[$key] = $value;
        }

        return $this;
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
     */
    public function eachSpread($callback)
    {
        foreach ($this->data as $datum) {
            if(call_user_func_array($callback, $datum) === false) {
                break;
            }
        }
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
     * @return $this
     */
    public function decrement($key, $value = 1)
    {
        $this->data[$key] = ($this->data[$key] ?? 0) - $value;

        return $this;
    }

    /**
     * 指定键值递增
     * 不支持 . 表示深度
     *
     * @param $key
     * @param int $value
     * @return $this
     */
    public function increment($key, $value = 1)
    {
        $this->data[$key] = ($this->data[$key] ?? 0) + $value;

        return $this;
    }

    public function implode($key, $gule = null)
    {
        $array = $gule == null ? $this->data : array_column($this->data, $key);

        return implode($gule == null ? $key : $gule, $array);
    }

    public function isEmpty()
    {
        return empty($this->data);
    }

    public function keysOfMaxValue()
    {
        return new static(array_keys($this->data, max($this->data)));
    }

    public function last(callable $callback = null, $default = null)
    {
        if($callback == null) {
            return empty($this->data) ? $default : end($this->data);
        }

        return $this->reverse()->first($callback, $default);
    }

    public function first(callable $callback = null, $default = null)
    {

        if($callback == null) {
            return empty($this->data) ? $default : reset($this->data);
        }

        foreach ($this->data as $key => $datum)
            if(call_user_func($callback, $datum, $key))
                return $datum;

        return $default;
    }

    public function pull($key, $default = null)
    {
        $res = $this->data[$key] ?? $default;

        unset($this->data[$key]);

        return $res;
    }

    public function toJson()
    {
        return json_encode($this->data);
    }

    public function when($bool, callable $callable)
    {
        if($bool)
            return $callable($this);

        return $this;
    }

    public function where($key, $compare = null, $value = null)
    {
        $compares = ['=', '==', '>', '<', '>=', '<=', '!='];

        if($value == null) {
            list($value, $compare) = [$compare, '=='];
        }

        if(!in_array($compare, $compares)) {
            return $this;
        }

        switch ($compare) {
            case '=':
            case '==':
                $fun = function ($v) use ($key, $value) {
                    return $v[$key] == $value;
                };
                break;
            case '>':
                $fun = function ($v) use ($key, $value) {
                    return $v[$key] > $value;
                };
                break;
            case '>=':
                $fun = function ($v) use ($key, $value) {
                    return $v[$key] >= $value;
                };
                break;
            case '<':
                $fun = function ($v) use ($key, $value) {
                    return $v[$key] < $value;
                };
                break;
            case '<=':
                $fun = function ($v) use ($key, $value) {
                    return $v[$key] <= $value;
                };
                break;
            case '!=':
                $fun = function ($v) use ($key, $value) {
                    return $v[$key] != $value;
                };
                break;
            default:
                return $this;
        }

        return $this->filter($fun);
    }

    public function whereIn($key, $values)
    {
        $fun = function ($v) use ($key, $values) {
            return in_array($v[$key], $values);
        };

        return $this->filter($fun);
    }

    protected function concat($array)
    {
        $this->data = array_merge($this->data, array_values($array));

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

    public function __get($name)
    {
        return $this->data[$name];
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->data);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->data[$this->key()]);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->data);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset)
            ? $this->data[$offset]
            : null;
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if(is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        if($this->offsetExists($offset)) {
            unset($this->data[$offset]);
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}