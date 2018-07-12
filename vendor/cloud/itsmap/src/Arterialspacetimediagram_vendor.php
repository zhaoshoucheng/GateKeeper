<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Optimize/signal_opt_service.php';
require_once __DIR__ . '/Thrift/Optimize/Types.php';
require_once __DIR__ . '/Thrift/Optimize/Spacetimediagram/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

use Optimize\Spacetimediagram\RouteJunction;
use Optimize\Version as sysVersion;

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
                'junction_id'     => '2017030116_5138189', // 路口ID
                'forward_flow_id' => '2017030116_i_73821231_2017030116_o_877944131', // 正向flow
                'reverse_flow_id' => '2017030116_i_877944150_2017030116_o_877944100', // 反向flow
                'tod_start_time'  => '16:00:00', // 配时方案开始时间
                'tod_end_time'    => '19:30:00', // 配时方案结束时间
                'cycle'           => 220      // 配时周期
            ],
        ];

        $versionArr = [
            [
                'date' => '20180604',
                'map_version' => '5cbc047bb7cb48efc732dc48307533a8'
            ],
            [
                'date' => '20180601',
                'map_version' => '5cbc047bb7cb48efc732dc48307533a8'
            ],
            [
                'date' => '20180531',
                'map_version' => '5cbc047bb7cb48efc732dc48307533a8'
            ],
            [
                'date' => '20180530',
                'map_version' => '5cbc047bb7cb48efc732dc48307533a8'
            ],
            [
                'date' => '20180529',
                'map_version' => '2db3490106a19de01f184f3b20756c04'
            ],
        ];

        foreach ($versionArr as $v) {
            $version[] = new sysVersion($v);
        }

        foreach ($arr as &$v) {
            $v = new RouteJunction($v);
        }

        $method = 0;

        $service = new RoadNet();
        $response = $service->getSpaceTimeDiagram($arr, $method, $version);

        echo "<pre>response = ";print_r($response);
        echo "<hr><pre>arr = ";print_r($arr);
        echo "<hr><pre>version = ";print_r($version);
    }
}