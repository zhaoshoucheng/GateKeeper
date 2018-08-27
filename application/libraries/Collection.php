<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/23
 * Time: 下午4:16
 */

if(!defined('COLLECTION_DIR'))
    define('COLLECTION_DIR', __DIR__ . '/Collection/');

require COLLECTION_DIR .'tools.php';
require COLLECTION_DIR .'Trait/CollectionPrivateMethod.php';

class Collection
{
    use CollectionPrivateMethod;

    /**
     * 获取指定键值
     *
     * @param null $key
     * @return null
     */
    public function get($key = null)
    {
        return $this->getBy($key);
    }

    /**
     * 设置键值
     *
     * @param $key
     * @param $value
     * @return Collection
     */
    public function set($key, $value)
    {
        return $this->setBy($key, $value);
    }

    /**
     * 判断数组中是否存在指定键
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return $this->hasBy($key);
    }

    /**
     * 遍历数组
     *
     * @param Callable $callback function($k, $v) { ... }
     * @return $this
     */
    public function foreach($callback)
    {
        return $this->arrayWalk($callback);
    }

    public function add($key, $value)
    {
        if(!$this->has($key))
            $this->set($key, $value);
        return $this;
    }

    public function collapse()
    {
        return $this->collapseTo();
    }

    public function divide()
    {
        return [$this->arrayKeys(), $this->arrayValues()];
    }

    public function dot()
    {
        return $this->dotTo();
    }

    public function except($keys)
    {
        return $this->exceptBy($keys);
    }

    public function first($callback = null, $default)
    {
        return $this->firstOn($callback, $default);
    }

    public function flatten()
    {
        return $this->flattenTo();
    }

    public function forget($key)
    {
        return $this->forgetByDot($key);
    }
}