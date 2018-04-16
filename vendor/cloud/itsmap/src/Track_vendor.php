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
    public function getScatterMtraj($data) {
        $mtrajService = new RoadNet();
        $response = $mtrajService->getMtrajData($data, 'getScatterMtraj');

        return $response;
    }

    /**
    * 获取时空图
    */
    public function getSpaceTimeMtraj($data) {
        $mtrajService = new RoadNet();
        $response = $mtrajService->getMtrajData($data, 'getSpaceTimeMtraj');

        return $response;
    }
}