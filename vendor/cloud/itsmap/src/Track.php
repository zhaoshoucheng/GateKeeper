<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Track/MtrajService.php';
require_once __DIR__ . '/Thrift/Track/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;

class Track {
	public function __construct() {
        Env::init();
    }

    /**
    * 获取时空图
    */
    public function getSpaceTimeMtraj() {
        $mtrajService = new RoadNet();
        $response = $mtrajService->getScatterMtraj($data);

        return $response;
    }
}