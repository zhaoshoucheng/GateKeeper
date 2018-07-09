<?php
namespace Didi\Cloud\ItsMap;

require_once __DIR__ . '/Thrift/Todsplit/signal_opt_service.php';
require_once __DIR__ . '/Thrift/Todsplit/Types.php';

use Didi\Cloud\ItsMap\Configs\Env;
use Didi\Cloud\ItsMap\Services\RoadNet;

use Todsplit\MovementSignal;
use Todsplit\Version as todsplit_version;
use Todsplit\JunctionMovements;
use Todsplit\TodInfo;

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
        $vals = new TodInfo();
        $vals->dates = $data['dates'];
        foreach ($data['junction_movements'] as $v) {
            $vals->junction_movements[] = new JunctionMovements($v);
        }

        foreach ($data['version'] as $v) {
            $vals->version[] = new todsplit_version($v);
        }
        $vals->tod_cnt = $data['tod_cnt'];

        $service = new RoadNet();
        $response = $service->getTodPlan($vals);

        return $response;
    }

    /**
    * 获取绿信比优化方案
    */
    public function getSplitPlan($data) {
        foreach ($data['signal'] as $v) {
            $ndata['signal'][] = new MovementSignal($v);
        }

        foreach ($data['version'] as $v) {
            $ndata['version'][] = new todsplit_version($v);
        }

        $ndata['dates'] = $data['dates'];
        $ndata['logic_junction_id'] = $data['logic_junction_id'];
        $ndata['start_time'] = $data['start_time'];
        $ndata['end_time'] = $data['end_time'];
        $ndata['cycle'] = $data['cycle'];
        $ndata['offset'] = $data['offset'];
        $ndata['clock_shift'] = $data['clock_shift'];

        $service = new RoadNet();
        $response = $service->getSplitPlan($ndata);

        return $response;
    }
}