<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/27
 * Time: 上午10:44
 */

require_once 'ArrayRawMethod.php';

trait ArrayPrivateMethod
{
    use ArrayRawMethod;

    private function getBy($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    private function getByDot($key, $default = null)
    {
        $keys = explode('.', $key);
        $result = $this->data;
        foreach ($keys as $key) {
            $result = $result[$key] ?? $default;
        }
    }
}