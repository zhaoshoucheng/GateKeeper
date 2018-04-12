<?php

namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/RoadNet/ShmDataService.php';
require_once __DIR__ . '/Thrift/RoadNet/InheritService.php';
require_once __DIR__ . '/Thrift/RoadNet/Types.php';

require_once __DIR__ . '/Thrift/StsData/CalculatorService.php';
require_once __DIR__ . '/Thrift/StsData/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;

use Didi\Cloud\ItsMap\Node;
use Didi\Cloud\ItsMap\Junction;
use Didi\Cloud\ItsMap\Flow;
use Didi\Cloud\ItsMap\MapVersion;
use Didi\Cloud\ItsMap\Services\RoadNet;

use DidiRoadNet\AreaFlowRequest;
use DidiRoadNet\AreaFlowVersionReq;
use DidiRoadNet\LogicJunctionReq;
use DidiRoadNet\LogicFlowReq;
use DidiRoadNet\AreaFlowResponse;

use StsData\RoadVersionRuntime;


class Task
{
    public function __construct() {
        Env::init();
        MapManager::bootEloquent();
    }

    public function areaFlowProcess($city_id, $task_id, $trace_id, $hdfs_dir, $versions) {
        $roadNetService = new RoadNet();
        $response = $roadNetService->areaFlowProcess($city_id, $task_id, $trace_id, $hdfs_dir, $versions);
        // print_r($response);
    }

    public function calculate($city_id, $task_id, $trace_id, $hdfs_dir, $start_time, $end_time, $dateVersion) {
        $roadNetService = new RoadNet();
        $response = $roadNetService->calculate($city_id, $task_id, $trace_id, $hdfs_dir, $start_time, $end_time, $dateVersion);
        print_r($response);
    }
}