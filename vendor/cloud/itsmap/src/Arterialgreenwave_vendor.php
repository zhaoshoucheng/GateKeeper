<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Optimize/signal_opt_service.php';
require_once __DIR__ . '/Thrift/Optimize/Types.php';
require_once __DIR__ . '/Thrift/Optimize/Greenwave/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

use Optimize\Greenwave\JunctionOfRoute;
use Optimize\Greenwave\RouteMes;

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
    *   "junction_id"=>"xx432423423",   // 路口ID
    *   "cycle"=>60,                    // 配时周期
    *   "offset"=>3,                    // 偏移量
    *   "forward_green_start"=>[0],     // 正向绿灯开始时间 如只取反向传-1
    *   "forward_green_duration"=>[30], // 正向绿灯持续时间 如只取反向传-1
    *   "reverse_green_start"=>[30[,    // 反向绿灯开始时间 如只取正向传-1
    *   "reverse_green_duration"=>[20], // 反向绿灯持续时间 如只取正向传-1
    *   "lock_cycle"=>1,                // 周期是否锁定 1是 0否
    *   "lock_offset"=>0                // 偏移量是否锁定 1是 0否
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
        $array = [
            [
                'junction_id' => 'minjunshapaozi01',
                'cycle'       => 120,
                'offset'      => 2,
                'forward_green_start' => [0,10],
                'forward_green_duration' => [20, 50],
                'reverse_green_start' => [-1],
                'reverse_green_duration' => [-1],
                'lock_cycle' => false,
                'lock_offset'=> true,
            ],
            [
                'junction_id' => 'minjunshapaozi02',
                'cycle'       => 1000,
                'offset'      => 2,
                'forward_green_start' => [200],
                'forward_green_duration' => [50],
                'reverse_green_start' => [-1],
                'reverse_green_duration' => [-1],
                'lock_cycle' => true,
                'lock_offset'=> false,
            ],
            [
                'junction_id' => 'minjunshapaozi03',
                'cycle'       => 10000,
                'offset'      => 2,
                'forward_green_start' => [10],
                'forward_green_duration' => [20],
                'reverse_green_start' => [-1],
                'reverse_green_duration' => [-1],
                'lock_cycle' => true,
                'lock_offset'=> true,
            ],
        ];

        $vals = new RouteMes();
        $forward_length = [100,200,500,1000];
        $forward_speed = [20000,300,200,10000];
        foreach ($forward_length as $v) {
            $vals->forward_length[] = new RouteMes($v);
        }
        foreach ($forward_speed as $v) {
            $vals->forward_speed[] = new RouteMes($v);
        }
        $vals->reverse_length[] = [];
        $vals->reverse_speed[] = [];
        foreach ($array as $v) {
            $vals->junction_list[] = new JunctionOfRoute($v);
        }

        $direction_method = 0; // 0：正向 1：反向 2：双向
        $token = 'xxxx';

        $service = new RoadNet();
        $response = $service->getGreenWaveOptPlan($vals, $direction_method, $token);
        var_dump($response);
        return $response;
    }
}