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

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function groupBy($column, $key = null, callable $callable = null)
    {
        if(is_string($column)) {
            return $this->groupByString($column, $callable);
        } elseif(is_array($column)) {
            return $this->groupByArray($column, $callable);
        }
    }

    public function groupByString($column, $key = null, callable $callable = null)
    {

    }
}