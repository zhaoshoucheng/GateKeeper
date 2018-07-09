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
    */
    public function getGreenWaveOptPlan($data) {
        $service = new RoadNet();
        $response = $service->getGreenWaveOptPlan($data);

        return $response;
    }
}