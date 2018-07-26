<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Optimize/signal_opt_service.php';
require_once __DIR__ . '/Thrift/Optimize/Types.php';
require_once __DIR__ . '/Thrift/Optimize/Greenwave/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

use Optimize\Greenwave\JunctionOfRoute;
use Optimize\Greenwave\RouteMes;
use Optimize\Greenwave\Green;

class Arterialgreenwave_vendor
{
    public function __construct() {
        Env::init();
    }

    /**
    * 获取干线绿波优化方案
    * @param $data['junctions']      array    Y 路口集合 如下示例：
    * [
    *  [
    *   "junction_id"=>"xx432423423",   // 路口ID
    *   "cycle"=>60,                    // 配时周期
    *   "offset"=>3,                    // 偏移量
    *   "forward_green"=>[              // 正向绿灯信息 如只取反向时传-1:forward_green['green_start'=>-1, green_duration=>-1]
    *       [
    *           'green_start' => 0,     // 绿灯开始时间
    *           'green_duration' => 10  // 绿灯持续时间
    *       ],
    *       ......
    *   ],
    *   "reverse_green"=>[              // 反向绿灯信息 如只取正向时传-1:reverse_green['green_start'=>-1, green_duration=>-1]
    *       [
    *           'green_start' => 0,     // 绿灯开始时间
    *           'green_duration' => 10  // 绿灯持续时间
    *       ],
    *       ......
    *   ],
    *   "lock_cycle"=>1,                // 周期是否锁定 0：否 1：是
    *   "lock_offset"=>0                // 偏移量是否锁定 0：否 1：是
    *   ],
    * ]
    * @param $data['forward_length'] array    N 正向路段长度  格式：[100, 200, 300] 只取反向时可不传
    * @param $data['forward_speed']  array    N 正向速度     格式：[100, 200, 300] 只取反向时可不传
    * @param $data['reverse_length'] array    N 反向路段长度 格式：[100, 200, 300]  只取正向时可不传
    * @param $data['reverse_speed']  array    N 返向速度     格式：[100, 200, 300] 只取正向时可不传
    * @param $data['method']         interger Y 0=>正向 1=>反向 2=>双向
    * @param $data['token']          string   Y 此次请求唯一标识，用于前端轮询
    * @return array
    */
    public function getGreenWaveOptPlan($data) {
        $vals = new RouteMes();
        foreach ($data['junctions'] as $k=>$v) {
            if (!empty($v['forward_green']) && is_array($v['forward_green'])) {
                foreach ($v['forward_green'] as $kk=>$vv) {
                    $data['junctions'][$k]['forward_green'][$kk] = new Green($vv);
                }
            }

            if (!empty($v['reverse_green']) && is_array($v['reverse_green'])) {
                foreach ($v['reverse_green'] as $kk=>$vv) {
                    $data['junctions'][$k]['reverse_green'][$kk] = new Green($vv);
                }
            }

            $vals->junction_list[] = new JunctionOfRoute($v);
        }

        $method = intval($data['method']); // 0：正向 1：反向 2：双向

        if (!empty($data['forward_length']) && empty($data['reverse_length'])){
            foreach ($data['forward_length'] as $v) {
                $vals->forward_length[] = $v;
                $vals->reverse_length[] = $v;
            }
        } else if (empty($data['forward_length']) && !empty($data['reverse_length'])) {
            foreach ($data['reverse_length'] as $v) {
                $vals->forward_length[] = $v;
                $vals->reverse_length[] = $v;
            }
        } else if (!empty($data['forward_length']) && !empty($data['reverse_length'])) {
            foreach ($data['reverse_length'] as $v) {
                $vals->reverse_length[] = $v;
            }
            foreach ($data['forward_length'] as $v) {
                $vals->forward_length[] = $v;
            }
        } else {
            return [];
        }

        if (!empty($data['forward_speed']) && empty($data['reverse_speed'])){
            foreach ($data['forward_speed'] as $v) {
                $vals->forward_speed[] = $v;
                $vals->reverse_speed[] = $v;
            }
        } else if (empty($data['forward_speed']) && !empty($data['reverse_speed'])) {
            foreach ($data['reverse_speed'] as $v) {
                $vals->forward_speed[] = $v;
                $vals->reverse_speed[] = $v;
            }
        } else if (!empty($data['forward_speed']) && !empty($data['reverse_speed'])) {
            foreach ($data['reverse_speed'] as $v) {
                $vals->reverse_speed[] = $v;
            }
            foreach ($data['forward_speed'] as $v) {
                $vals->forward_speed[] = $v;
            }
        } else {
            return [];
        }

        $token = $data['token'];

        $service = new RoadNet();
        $response = $service->getGreenWaveOptPlan($vals, $method, $token);

        return $response;
    }
}