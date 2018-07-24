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
    * @param $data['junctions']  array Y 路口信息集合 如下：
    *   $data['junctions'] = [
    *       [
    *           'junction_id'          => '2017030116_5138189', // 路口ID
    *           'forward_flow_id'      => '2017030116_i_73821231_2017030116_o_877944131', // 正向flow
    *           'forward_in_links'     => [111,222,333], // 正向inlinks
    *           'forward_out_links'    => [111,222,333], // 正向outlinks
    *           'reverse_flow_id'      => '2017030116_i_877944150_2017030116_o_877944100', // 反向flow
    *           'reverse_in_links'     => [111,222,333], // 反向inlinks
    *           'reverse_out_links'    => [111,222,333], // 反向outlinks
    *           'junction_inner_links' => [111,222,333] // inner_links (正、反向是一样的)
    *           'tod_start_time'       => '16:00:00', // 配时方案开始时间
    *           'tod_end_time'         => '19:30:00', // 配时方案结束时间
    *           'cycle'                => 220         // 配时周期
    *       ],
    *   ]
    * @param $data['version']    array  Y 版本信息 如下：
    *   $data['version'] = [
    *       [
    *           'date' => '20180604',
    *           'map_version' => '5cbc047bb7cb48efc732dc48307533a8'
    *       ],
    *   ]
    * @param $data['method']     interger Y 0=>正向 1=>反向 2=>双向
    * @param $data['token']      string   Y 此次请求唯一标识，用于前端轮询
    * @return array
    */
    public function getSpaceTimeDiagram($data) {
        // 实例version
        foreach ($data['version'] as $v) {
            $version[] = new sysVersion($v);
        }

        // 实例RouteJunction
        foreach ($data['junctions'] as &$v) {
            $v = new RouteJunction($v);
        }

        $method = $data['method'];

        $service = new RoadNet();
        $response = $service->getSpaceTimeDiagram($data['junctions'], $method, $version);

        return $response;
    }
}