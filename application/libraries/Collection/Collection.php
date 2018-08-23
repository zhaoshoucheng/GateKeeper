<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/23
 * Time: 下午4:16
 */

class Collection
{
    private $data = [];

    private $self = [

    ];

    private $other = [

    ];

    public function __construct($data)
    {
        $this->setData($data);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function groupBy($column, callable $callable = null)
    {
        if(is_string($column)) {
            return $this->groupByString($column, $callable);
        }
        throw new Exception('Your type of column is wrong!');
    }

    public function orderBy($column, $order = SORT_ASC)
    {
        if(is_string($column)) {
            return $this->orderByString($column, $order);
        }
        throw new Exception('Your type of column is wrong!');
    }

    private function groupByString($column, callable $callable = null)
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

    private function orderByString($column, $order = SORT_ASC)
    {
        $data = array_column($this->toArray(), null, $column);
        switch ($order) {
            case SORT_ASC: ksort($data); break;
            case SORT_DESC: krsort($data); break;
            default: break;
        }
        return $this->setData(array_values($data));
    }

    private function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function __call($name, $arguments)
    {
        $method = implode('_', preg_split("/(?=[A-Z])/", $name));

        if(in_array($this->selfFirst))
            return $this->setData(call_user_func_array($method, array_unshift($arguments, $this->toArray())));
        elseif(in_array($this->other))
            return call_user_func_array($method, array_unshift($arguments, $this->toArray()));

        throw new Exception('Method don\'t exist!');
    }
}