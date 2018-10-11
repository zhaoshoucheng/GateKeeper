<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/11
 * Time: 下午3:31
 */

namespace Services;


class BaseService
{
    public function __get($key)
    {
        return get_instance()->$key;
    }
}