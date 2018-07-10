<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Track/MtrajService.php';
require_once __DIR__ . '/Thrift/Track/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

use Track\Request as mtraj_request;
use Track\Rtime;
use Track\TypeData;
use Track\FilterData;

class Track_vendor {
    public function __construct() {
        Env::init();
    }

    /**
    * 获取散点图
    */
    public function getScatterMtraj($data) {
        $mtraj_request = new mtraj_request();

        $mtraj_request->junctionId = $data['junctionId'];
        $mtraj_request->flowId = $data['flowId'];
        foreach($data['rtimeVec'] as $v){
            $mtraj_request->rtimeVec[] = new Rtime($v);
        }
        foreach($data['filterData'] as $k=>&$v){
            $v['xData'] = new TypeData($v['xData']);
            $v['yData'] = new TypeData($v['yData']);
            $mtraj_request->filterDataVec[] = new FilterData($v);
        }

        $mtrajService = new RoadNet();
        $response = $mtrajService->getMtrajData($mtraj_request, 'getScatterMtraj');

        return $response;
    }

    /**
    * 获取时空图
    */
    public function getSpaceTimeMtraj($data) {
        $mtraj_request = new mtraj_request();

        $mtraj_request->junctionId = $data['junctionId'];
        $mtraj_request->flowId = $data['flowId'];
        foreach($data['rtimeVec'] as $v){
            $mtraj_request->rtimeVec[] = new Rtime($v);
        }
        foreach($data['filterData'] as $k=>&$v){
            $v['xData'] = new TypeData($v['xData']);
            $v['yData'] = new TypeData($v['yData']);
            $mtraj_request->filterDataVec[] = new FilterData($v);
        }

        $mtrajService = new RoadNet();
        $response = $mtrajService->getMtrajData($mtraj_request, 'getSpaceTimeMtraj');

        return $response;
    }
}