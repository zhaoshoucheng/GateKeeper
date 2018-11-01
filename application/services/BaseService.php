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
 * @property \CI_Config            $config
 * @property \CI_Loader            $load
 * @property \CI_Benchmark         $benchmark
 * @property \Redis_model          $redis_model
 * @property \Waymap_model         $waymap_model
 */
class BaseService
{
    public function __construct()
    {
        $this->benchmark->mark('service_start');
    }

    public function __get($key)
    {
        return get_instance()->$key;
    }
}