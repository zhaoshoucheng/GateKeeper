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
use Todsplit\SignalPlan;

class Todsplit_vendor {
    public function __construct() {
        Env::init();
    }

    /**
    * 获取时段划分方案
    * @param $data['dates']              string   Y 日期 用逗号隔开
    * @param $data['junction_movements'] array    Y 路口相位集合
    * @param $data['tod_cnt']            interger Y 划分数量
    * @param $data['version']            array    Y 版本、日期集合
    * @return array
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
    * @param data['dates']             string   Y 任务日期
    * @param data['logic_junction_id'] string   Y 路口ID
    * @param data['start_time']        string   Y 配时方案开始时间
    * @param data['end_time']          string   Y 配时方案结束时间
    * @param data['cycle']             interger Y 配时周期
    * @param data['offset']            interger Y 配时偏移量
    * @param data['clock_shift']       interger Y 配时时钟偏移量，任务结果中的（junction_index）暂时传0
    * @param data['signal']            array    Y 信号灯信息集合
    *     data['signal'] = [
    *           [
    *               'logic_flow_id'  => 'xxx',  flow id
    *               'green_start'    => [0],    绿灯开始时间
    *               'green_duration' => [30],   绿灯持续时间
    *               'yellow'         => [3],    黄灯时长
    *               'red_clean'      => [0],    全红
    *           ],
    *           ......
    *     ]
    * @param data['version']           array   Y 版本信息
    *     data['version'] = [
    *           [
    *               'map_version' => 'xxx',  地图版本
    *               'date'        => [0],    日期
    *           ],
    *           ......
    *     ]
    * @return array
    */
    public function getSplitPlan($data) {
        $vals = new SignalPlan();
        $vals->dates = trim($data['dates']);
        $vals->logic_junction_id = trim($data['logic_junction_id']);
        $vals->start_time = trim($data['start_time']);
        $vals->end_time = trim($data['end_time']);
        $vals->cycle = intval($data['cycle']);
        $vals->offset = intval($data['offset']);
        $vals->clock_shift = intval($data['clock_shift']);

        foreach ($data['signal'] as $v) {
            $vals->signal[] = new MovementSignal($v);
        }

        foreach ($data['version'] as $v) {
            $version[] = new todsplit_version($v);
        }

        $service = new RoadNet();
        $response = $service->getSplitPlan($vals, $version);

        return $response;
    }
}