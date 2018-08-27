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

    private function setBy($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * 依据 $key 获取指定键值对，不存在则返回 $default
     *
     * @param $key
     * @param null $default
     * @return null
     */
    private function getBy($key, $default = null)
    {
        return $this->data[$key] ?? $default;
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
    private function getByDot($key, $default = null)
    {
        $keys = explode('.', $key);
        $result = $this->data;
        foreach ($keys as $key) {
            if(isset($result[$key])) {
                $result = $result[$key];
            } else {
                $result = $default;
                break;
            }
        }
        return $result;
    }

    private function hasBy($key)
    {
        return $this->inArray($key);
    }

    /**
     * 仅返回给定数组中的键值对
     *
     * @param $keys
     * @return CollectionPrivateMethod
     */
    private function onlyBy($keys)
    {
        return $this->arrayFilter(function ($key) use ($keys) {
            return in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function collapseTo()
    {
        $result = [];
        $this->foreach(function($v) use (&$result) {
            if(is_array($v))
                static::make($v)->foreach(function ($v) use (&$result) {
                    $result[] = $v;
                });
            else
                $result[] = $v;
        });
        return new static($result);
    }

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

    private function exceptBy($keys)
    {
        return $this->arrayFilter(function ($key) use ($keys) {
            return !in_array($key, $keys);
        }, ARRAY_FILTER_USE_KEY);
    }

    private function firstOn($callback = null, $default = null)
    {
        if($callback == null) return $this->empty() ? $default : $this->reset();
        $result = $this->arrayFilter($callback);
        return $result->empty() ? $default : $result->reset();
    }

    private function flattenTo()
    {
        return $this->dotTo()->arrayValues();
    }

    private function forgetByString($key)
    {
        unset($this->data[$key]);
        return $this;
    }

    private function forgetByArray($keys)
    {
        if(count($keys) == 0) return $this;
        if(count($keys) == 1) return $this->forgetByString(current($keys));
        $key = array_shift($keys);
        return $this->setBy($key, static::make($this->getBy($key))->forgetByArray($keys)->toArray());
    }

    private function forgetByDot($key)
    {
        $keys = explode('.', $key);
        return $this->forgetByArray($keys);
    }
}