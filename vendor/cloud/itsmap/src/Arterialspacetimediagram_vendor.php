<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Optimize/signal_opt_service.php';
require_once __DIR__ . '/Thrift/Optimize/Types.php';
require_once __DIR__ . '/Thrift/Optimize/Spacetimediagram/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

use Optimize\Spacetimediagram\RouteJunction;

class Arterialspacetimediagram_vendor
{
    public function __construct() {
        Env::init();
    }

    /**
    * 获取干线时空图
    * @return array
    */
    public function getSpaceTimeDiagram($data) {
        $arr = [
            [
                'junction_id'     => 'aaaaa', // 路口ID
                'forward_flow_id' => 'aaaaa', // 正向flow
                'reverse_flow_id' => 'aaaaa', // 反向flow
                'tod_start_time'  => 'aaaaa', // 配时方案开始时间
                'tod_end_time'    => 'aaaaa', // 配时方案结束时间
                'cycle'           => 100      // 配时周期
            ],
            [
                'junction_id'     => 'aaaaa', // 路口ID
                'forward_flow_id' => 'aaaaa', // 正向flow
                'reverse_flow_id' => 'aaaaa', // 反向flow
                'tod_start_time'  => 'aaaaa', // 配时方案开始时间
                'tod_end_time'    => 'aaaaa', // 配时方案结束时间
                'cycle'           => 100      // 配时周期
            ]
        ];

        foreach ($arr as &$v) {
            $v = new RouteJunction($v);
        }

        echo "<pre>";print_r($arr);
    }
}