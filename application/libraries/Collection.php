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
     * 支持的 PHP 原生数组函数
     * @var array
     */
    private static $methods = [
        1 => [ // 返回 函数本身的结果，如果是数组 则返回 Collection 对象，且数组参数位于第一位
            'array_chunk', 'array_filter', 'array_column', 'count', 'max', 'array_keys',
            '',
        ],
        2 => [ // 返回 $this 且数组参数为引用 且位于第一位
            'sort', 'rsort', 'ksort', 'krsort', 'asort', 'arsort',
            'array_shift', 'array_unshift', 'array_pop', 'array_push',
        ],
        3 => [ // 返回 函数本身的结果，如果是数组 则返回 Collection 对象，且数组参数位于最后一位
            'implode', 'array_key_exists'
        ]
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

    /**
     * 获取数组指定键的值
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     */
    public function get($key = null, $default = null)
    {
        return $key != null ?
            $this->data[$key] ?? $default :
            $this->data;
    }

    /**
     * 设置数组的指定键的值
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * 获取数组中最大值所对应的键的集合
     * @return Collection
     */
    public function getKeysOfMaxValue()
    {
        return $this->arrayKeys($this->max());
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
     * 指定键自增
     * @param $key
     * @param int $value
     * @return Collection
     */
    public function increment($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) + $value);
    }

    /**
     * 指定键自减
     * @param $key
     * @param int $value
     * @return Collection
     */
    public function decrement($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) - $value);
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
        return $this;
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
    public function arrayWalk(callable $callable)
    {
        return array_walk($this->data, function ($v, $k) use ($callable) {
            $callable(is_array($v) ? Collection::make($v) : $v, $k);
        });
    }

    /**
     * @param $callable
     * @return Collection
     */
    public function arrayMap(callable $callable)
    {
        $this->data = array_map(function ($v) use ($callable) {
            return $callable(is_array($v) ? Collection::make($v) : $v);
        }, $this->toArray());
        return $this;
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
        $this->foreach(function ($c) use (&$data, $column) {
            if($c->arrayKeyExists($column))
                $data[$c->$column][] = $c instanceof Collection ? $c->toArray() : $c;
        });

        $collection = Collection::make($data);

        if(!is_null($callable) && is_callable($callable))
            $collection->arrayMap(function ($c) use ($callable) {
                return $callable($c);
            });
        return $collection;
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
        return $this->groupByString($first, $callable);
    }

    /**
     * 按照指定字符串排序
     * @param $column
     * @param int $order
     * @return Collection
     */
    private function orderByString($column, $order = SORT_ASC)
    {
        $data = $this->arrayColumn(null, $column);
        switch ($order) {
            case SORT_ASC: $data->ksort(); break;
            case SORT_DESC: $data->krsort(); break;
            default: break;
        }
        return $data->arrayValues();
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

    /**
     * 适配 未定义的 函数
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

        if(in_array($method, self::$methods[1])) {
            array_unshift($arguments, $this->toArray());
            $result = call_user_func_array($method, $arguments);
            return is_array($result) ? new static($result) : $result;
        } elseif(in_array($method, self::$methods[2])) {
            array_unshift($arguments, $this->data);
            call_user_func_array($method, $arguments);
            return $this;
        } elseif(in_array($method, self::$methods[3])) {
            array_push($arguments, $this->toArray());
            $result = call_user_func_array($method, $arguments);
            return is_array($result) ? new static($result) : $result;
        } else {
            switch ($method) {
            }
        }

        throw new Exception('Method ' . $method . ' don\'t exist or method isn\'t allowed!');
    }

    /**
     * @param $name
     * @return array|mixed|null
     */
    public function __get($name)
    {
        return $this->get($name, null);
    }

    /**
     * @param $name
     * @param $value
     * @return Collection
     */
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }
}