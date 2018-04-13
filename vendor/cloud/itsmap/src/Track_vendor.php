<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Track/MtrajService.php';
require_once __DIR__ . '/Thrift/Track/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

class Track_vendor {
	public function __construct() {
        Env::init();
    }

    /**
    * 获取散点图
    */
    public function getScatterMtraj() {
        $vals = [
            'junctionId' => '123213123123',
            'flowId'     => 'klasdjflkdajflka',
            'rtimeVec'   => [
                [
                    'mapVersion' => '2039403242',
                    'startTS'    => '2321312312312',
                    'endTS'      => '2342343242432'
                ],
                [
                    'mapVersion' => '2039403242',
                    'startTS'    => '2321312312312',
                    'endTS'      => '2342343242432'
                ],
                [
                    'mapVersion' => '2039403242',
                    'startTS'    => '2321312312312',
                    'endTS'      => '2342343242432'
                ]
            ],
            'x'   => 200 * -1,
            'y'   => 230,
            'num' => 100
        ];
        $mtrajService = new RoadNet();
        $response = $mtrajService->getScatterMtraj($vals);

        return $response;
    }
}