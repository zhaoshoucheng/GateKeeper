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
 * @property \Area_model           $area_model
 * @property \Road_model           $road_model
 * @property \Realtime_model       $realtime_model
 * @property \Feedback_model       $feedback_model
 * @property \Adapt_model          $adapt_model
 * @property \FlowDurationV6_model $flowDurationV6_model
 * @property \RealtimeAlarm_model  $realtimeAlarm_model
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