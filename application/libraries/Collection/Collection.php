<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/23
 * Time: 下午4:16
 */

class Collection
{
    /**
     * 数据源
     * @var array
     */
    protected $data = [];

    /**
     * 支持链式调用的 PHP 原生数组函数
     * @var array
     */
    private $self = [
        'array_chunk', 'array_filter'
    ];

    /**
     * 不支持链式调用的 PHP 原生函数
     * @var array
     */
    private $other = [
        'array_column'
    ];

    /**
     * 集合类构造函数
     * @param $data
     */
    public function __construct($data = [])
    {
        $this->setData($data);
    }

    /**
     * 设置数据源
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取集合数组
     * @return array
     */
    public function toArray()
    {
        return $this->data;
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
     * 适配 $self 和 $other 中定义的 PHP 自带的数组函数
     * @param $name
     * @param $arguments
     * @return Collection|mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        $method = 'array_' . implode('_', array_map(function ($v) {
                return lcfirst($v);
            }, preg_split("/(?=[A-Z])/", $name)));

        array_unshift($arguments, $this->toArray());

        if(in_array($method, $this->self))
            return $this->setData(call_user_func_array($method, $arguments));
        elseif(in_array($method, $this->other))
            return call_user_func_array($method, $arguments);

        throw new Exception('Method ' . $method . ' don\'t exist or method isn\'t allowed!');
    }

    protected function groupByString($column, callable $callable = null)
    {
    $data = [];
    foreach ($this->toArray() as $item) {
        if(array_key_exists($column, $item))
            $data[$item[$column]][] = $item;
    }

    if(!is_null($callable) && is_callable($callable))
        $data = array_map($callable, $data);

    return $this->setData($data);
}

    protected function orderByString($column, $order = SORT_ASC)
    {
        $data = array_column($this->toArray(), null, $column);
        switch ($order) {
            case SORT_ASC: ksort($data); break;
            case SORT_DESC: krsort($data); break;
            default: break;
        }
        return $this->setData(array_values($data));
    }
}