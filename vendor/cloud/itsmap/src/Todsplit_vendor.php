<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Todsplit/signal_opt_service.php';
require_once __DIR__ . '/Thrift/Todsplit/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

use Todsplit\Version as todsplit_version;
use Todsplit\JunctionMovements;

class Todsplit_vendor {
    public function __construct() {
        Env::init();
    }

    /**
    * 获取时段划分方案
    * @param $data['dates']              string   Y 日期 用逗号隔开
    * @param $data['junction_movements'] array    Y 路口相位集合 以路口ID为key
    * @param $data['tod_cnt']            interger Y 划分数量
    * @param $data['version']            array    Y 版本、日期集合
    */
    public function getTodPlan($data) {
        foreach ($data['junction_movements'] as $v) {
            $ndata['junction_movements'][] = new JunctionMovements($v);
        }

        foreach ($data['version'] as $v) {
            $ndata['version'][] = new todsplit_version($v);
        }
        $ndata['dates'] = $data['dates'];
        $ndata['tod_cnt'] = $data['tod_cnt'];

        $service = new RoadNet();
        $response = $service->getTodPlan($ndata);

        return $response;
    }

    /**
    * 获取绿信比优化方案
    */
    public function getSplitPlan($data) {
        $service = new RoadNet();
        $response = $service->getSplitPlan($data);

        return $response;
    }
}