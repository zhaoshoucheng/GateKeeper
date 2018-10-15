<?php
/**
 * 服务模块 - 基类
 *
 * User: lichaoxi_i@didiglobal.com
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