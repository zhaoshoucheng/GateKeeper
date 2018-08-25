<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/23
 * Time: 下午4:16
 */

require 'Collection/tools.php';

class Collection
{
    /**
     * 数据源
     * @var array
     */
    private $data = [];

    /**
     * 支持链式调用的 PHP 原生数组函数
     * @var array
     */
    private static $self = [
        'array_chunk', 'array_filter'
    ];

    /**
     * 不支持链式调用的 PHP 原生函数
     * @var array
     */
    private static $other = [
        'array_column', 'count', 'max', 'array_keys'
    ];

    /**
     * 集合类构造函数
     * @param $data
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * 构建 Collection 对象
     * @param array $data
     * @return Collection
     */
    public static function make($data = [])
    {
        return new static($data);
    }

    /**
     * 获取集合数组
     * @return array
     */
    public function toArray()
    {
        return $this->get();
    }

    public function get($key = null, $default = null)
    {
        return $key == null ?
            $this->data[$key] ?? $default :
            $this->data;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function getKeysOfMaxValue()
    {
        return new static($this->arrayKeys($this->max()));
    }

    /**
     * 求集合平均值
     * @param null $column
     * @return float|int
     */
    public function avg($column = null)
    {
        if($column == null)
            return array_sum($this->toArray()) / count($this->toArray());

        $data = array_column($this->toArray(), $column);
        return array_sum($data) / count($data);
    }

    /**
     * 将集合按照指定字段分组
     * @param $column
     * @param callable|null $callable
     * @return Collection
     * @throws Exception
     */
    public function groupBy($column, callable $callable = null)
    {
        if(is_string($column)) {
            return $this->groupByString($column, $callable);
        } elseif (is_array($column)) {
            return $this->groupByArray($column, $callable);
        }
        throw new Exception('Your type of column is wrong!');
    }

    /**
     * 将集合按照指定字段排序
     * @param $column
     * @param int $order
     * @return Collection
     * @throws Exception
     */
    public function orderBy($column, $order = SORT_ASC)
    {
        if(is_string($column)) {
            return $this->orderByString($column, $order);
        }
        throw new Exception('Your type of column is wrong!');
    }

    /**
     * 过滤不符合条件的数据
     *
     * @param $column
     * @param null $compare
     * @param null $value
     * @return mixed
     * @throws Exception
     */
    public function where($column, $compare = null, $value = null)
    {
        if(is_array($column)) {
            return $this->whereByArray($column);
        }

        if($compare == null)
            throw new Exception('paramater 2 is null, should be string');

        if($value == null) {
            $value = $compare;
            $compare = '==';
        }

        return $this->whereByString($column, $compare, $value);
    }

    /**
     * 适配 $self 和 $other 中定义的 PHP 自带的数组函数
     * @param $name
     * @param $arguments
     * @return Collection|mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        $method = implode('_', array_map(function ($v) {
                return lcfirst($v);
            }, preg_split("/(?=[A-Z])/", $name)));

        if(in_array($method, self::$self)) {
            array_unshift($arguments, $this->toArray());
            return new static(call_user_func_array($method, $arguments));
        } elseif(in_array($method, self::$other)) {
            array_unshift($arguments, $this->toArray());
            return call_user_func_array($method, $arguments);
        } else {
            switch ($method) {
            }
        }

        throw new Exception('Method ' . $method . ' don\'t exist or method isn\'t allowed!');
    }

    /**
     * @param callable $callable
     * @return $this
     */
    public function foreach(callable $callable)
    {
        $this->arrayWalk($callable);
        return $this;
    }

    /**
     * @param $callable
     * @return bool
     */
    public function arrayWalk($callable)
    {
        return array_walk($this->data, function ($v, $k) use ($callable) {
            return $callable(is_array($v) ? Collection::make($v) : $v, $k);
        });
    }

    public function arrayMap($callable)
    {
        return new static(array_map(function ($v) use ($callable) {
            return $callable(is_array($v) ? Collection::make($v) : $v);
        }, $this->toArray()));
    }

    /**
     * 按照字符串字段分组
     * @param $column
     * @param callable|null $callable
     * @return Collection
     */
    private function groupByString($column, callable $callable = null)
    {
        $data = [];
        foreach ($this->toArray() as $item) {
            if(array_key_exists($column, $item))
                $data[$item[$column]][] = $item;
        }

        if(!is_null($callable) && is_callable($callable))
            foreach ($data as $key => $datum) { $data[$key] = $callable(new static($datum)); }

        return new static($data);
    }

    /**
     * 按照字符串数组分组
     * @param $columns
     * @param callable|null $callable
     * @return Collection
     * @throws Exception
     */
    private function groupByArray($columns, callable $callable = null)
    {
        $first = array_shift($columns);
        while (!empty($columns)) {
            $column = array_pop($columns);
            $callable = function ($collection) use ($column, $callable) {
                return $collection->groupBy($column, $callable)->toArray();
            };
        }

        return $this->groupBy($first, $callable);
    }

    /**
     * 按照指定字符串排序
     * @param $column
     * @param int $order
     * @return Collection
     */
    private function orderByString($column, $order = SORT_ASC)
    {
        $data = array_column($this->toArray(), null, $column);
        switch ($order) {
            case SORT_ASC: ksort($data); break;
            case SORT_DESC: krsort($data); break;
            default: break;
        }
        return new static(array_values($data));
    }

    /**
     * 根据字符串字段过滤
     * @param $column
     * @param $compare
     * @param $value
     * @return mixed
     */
    private function whereByString($column, $compare, $value)
    {
        return $this->arrayFilter(function ($v) use ($column, $compare, $value) {
            return compare($compare, $v[$column], $value);
        });
    }

    /**
     * 依据数组过滤
     * @param $array
     * @return mixed
     */
    private function whereByArray($array)
    {
        $array = (new static($array))->arrayFilter(function ($v) {
            return is_array($v) && (count($v) == 2 || count($v) == 3);
        })->arrayMap(function ($v) {
            return count($v) == 3 ? $v : [$v[0], '==', $v[1]];
        })->toArray();

        return $this->arrayFilter(function ($v) use ($array) {
            foreach ($array as $item) {
                if(!compare( $item[1],$v[$item[0]] ?? null, $item[2]))
                    return false;
            }
            return true;
        });
    }
}