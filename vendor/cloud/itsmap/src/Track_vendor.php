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
            'junctionId' => '2017030116_4875814',
            'flowId'     => '2017030116_i_490122360_2017030116_o_64019800',
            'rtimeVec'   => [
                [
                    'mapVersion' => 'c25101a793840cc6abf3819813823d82',
                    'startTS'    => '1522252800l',
                    'endTS'      => '1522339200l'
                ]
            ],
            'x'   => 1,
            'y'   => -1,
            'num' => 10
        ];
        $mtrajService = new RoadNet();
        $response = $mtrajService->getScatterMtraj($vals);

        return $response;
    }
}