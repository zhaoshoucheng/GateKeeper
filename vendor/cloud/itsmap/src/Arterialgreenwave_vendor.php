<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Arterialgreenwave/.php';
require_once __DIR__ . '/Thrift/Arterialgreenwave/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

class Arterialgreenwave_vendor
{
    public function __construct() {
        Env::init();
    }

    /**
    * 获取干线绿波优化方案
    * @param junctions      array  Y 路口集合 如下示例：
    * [
    *  [
    *   "junction_id"=>"xx432423423", // 路口ID
    *   "cycle"=>60,                  // 配时周期
    *   "offset"=>3,                  // 偏移量
    *   "forward_green_start"=>0,     // 正向绿灯开始时间 如只取反向传-1
    *   "forward_green_duration"=>30, // 正向绿灯持续时间 如只取反向传-1
    *   "reverse_green_start"=>30,    // 反向绿灯开始时间 如只取正向传-1
    *   "reverse_green_duration"=>20, // 反向绿灯持续时间 如只取正向传-1
    *   "lock_cycle"=>1,              // 周期是否锁定 1是 0否
    *   "lock_offset"=>0              // 偏移量是否锁定 1是 0否
    *   ],
    * ]
    * @param forward_length array  N 正向路段长度  格式：[100, 200, 300] 只取反向时可不传
    * @param forward_speed  array  N 正向速度     格式：[100, 200, 300] 只取反向时可不传
    * @param reverse_length array  N 反向路段长度 格式：[100, 200, 300]  只取正向时可不传
    * @param reverse_speed  array  N 返向速度     格式：[100, 200, 300] 只取正向时可不传
    * @param token          string Y 此次请求唯一标识，用于前端轮询
    * @return array
    */
    public function getGreenWaveOptPlan($data) {
        $service = new RoadNet();
        $response = $service->getGreenWaveOptPlan($data);

        return $response;
    }
}