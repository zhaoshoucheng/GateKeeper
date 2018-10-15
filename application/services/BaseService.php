<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/11
 * Time: 下午3:31
 */

namespace Services;

/**
 * Class BaseService
 * @package Services
 *
 * @property \CI_Config $config
 * @property \CI_Loader $load
 */
class BaseService
{
    public function __construct()
    {
    }

    public function __get($key)
    {
        return get_instance()->$key;
    }
}