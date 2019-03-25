<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:09
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class TimingAdaptionService
 * @package Services
 * @property \Adapt_model $adapt_model
 * @property \Realtime_model $realtime_model
 */
class TimingAdaptionService extends BaseService
{
    protected $helperService;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('redis_model');
        $this->load->model('adapt_model');
        $this->load->model('realtime_model');

        $this->load->config('nconf');

        $this->load->helper('http_helper');

        $this->helperService = new HelperService();
    }

    /**
     * 获取基准配时详情
     * @param $params['logic_junction_id'] string Y 路口ID
     * @param $params['city_id']           int    Y 城市ID
     * @return array
     * @throws \Exception
     */
    public function getAdaptTimingInfo($params)
    {
        $cityId          = $params['city_id'];
        $logicJunctionId = $params['logic_junction_id'];

        $adapt   = $this->getAdaptInfo($logicJunctionId);   //本地快照

        $current = $this->getCurrentInfo($logicJunctionId); //信号机当前

        // 自适应和基准数据合并之后的格式化处理
        $formatTiming = function ($timing) {
            return [
                'start_time' => $timing['start_time'][1] ?? '',
                'suggest_start_time' => $timing['start_time'][0] ?? '',
                'duration' => $timing['duration'][1] ?? '',
                'suggest_duration' => $timing['duration'][0] ?? '',
                'max' => $timing['max'][1] ?? '',
                'suggest_max' => $timing['max'][0] ?? '',
                'min' => $timing['min'][1] ?? '',
                'suggest_min' => $timing['min'][0] ?? '',
                'start_time_change' => intval($timing['start_time'][0] ?? 0) - intval($timing['start_time'][1] ?? 0),
                'duration_change' => intval($timing['duration'][0] ?? 0) - intval($timing['duration'][1] ?? 0),
            ];
        };

        /**
         * @param Collection $movementTimingCollection
         *
         * @return array
         */
        $removeEmptyFlowId = function ($movementTimingCollection) {
            return $movementTimingCollection->filter(function ($item) {
                return $item['flow']['logic_flow_id'] != '';
            })->values();
        };

        $mergeSameFlowIdTiming = function ($data) {
            $timings          = array_column($data, 'timing');
            $result           = array_shift($data);
            $result['timing'] = call_user_func_array('array_merge', $timings);
            return $result;
        };

        if (empty($adapt) || empty($current)) {
            return [];
        }


        foreach ($adapt['tod'] as $tk => &$tod) {
            //因为优化后算法会将结果顺序打算,所以需要重新对数据排序
            //$adapt['tod'][]['stage'] 基于start_time排序
            $startTimeSorter = [];
            foreach ($adapt['tod'][$tk]['stage'] as $key => $row) {
                $startTimeSorter[$key]  = $row['start_time'];
            }
            array_multisort($startTimeSorter, SORT_ASC, $adapt['tod'][$tk]['stage']);

            $startTimeSorter = [];
            foreach ($current['tod'][$tk]['stage'] as $key => $row) {
                $startTimeSorter[$key]  = $row['start_time'];
            }
            array_multisort($startTimeSorter, SORT_ASC, $current['tod'][$tk]['stage']);
            //$adapt['tod'][]['movement_timing'] 基于flow.logic_flow_id排序
            $flowIdSorter = [];
            $timingStartSorter = [];
            foreach ($adapt['tod'][$tk]['movement_timing'] as $key => $row) {
                $flowIdSorter[$key]  = $row['flow']['logic_flow_id'] ?? "";
                $timingStartSorter[$key]  = $row['timing'][0]['start_time'] ?? "";
            }
            array_multisort($flowIdSorter, SORT_ASC, $timingStartSorter, SORT_ASC, $adapt['tod'][$tk]['movement_timing']);
            $flowIdSorter = [];
            $timingStartSorter = [];
            foreach ($current['tod'][$tk]['movement_timing'] as $key => $row) {
                $flowIdSorter[$key]  = $row['flow']['logic_flow_id'] ?? "";
                $timingStartSorter[$key]  = $row['timing'][0]['start_time'] ?? "";
            }
            array_multisort($flowIdSorter, SORT_ASC, $timingStartSorter, SORT_ASC, $current['tod'][$tk]['movement_timing']);
            $tod = $adapt['tod'][$tk];



            $tod['extra_time']['tod_end_time']   = date('H:i', strtotime($tod['extra_time']['tod_end_time']));
            $tod['extra_time']['tod_start_time'] = date('H:i', strtotime($tod['extra_time']['tod_start_time']));

            // 获取 该方向 flow_Ids
            $logicFlowIds = Collection::make($tod['movement_timing'])->column('flow')->column('logic_flow_id')->filter()->get();

            // 根据 flow id 获取 二次停车比率
            $flows = $this->getTwiceStopRate($logicFlowIds, $logicJunctionId, $cityId);

            // 数据处理
            foreach ($tod['movement_timing'] as $mk => &$movement) {
                //过滤空相位
                if(empty($movement['flow']['logic_flow_id'])){
                    unset($tod['movement_timing'][$mk]);
                    continue;
                }

                $movement['flow']['twice_stop_rate'] = $flows[$movement['flow']['logic_flow_id']] ?? '/';

                // 获取并过滤出 基准配时中的绿灯
                $currentTiming = $current['tod'][$tk]['movement_timing'][$mk]['timing'] ?? [];
                $greenCurrents = Collection::make($currentTiming)->where('state', 1)->get();

                // 同相位有两个配时的排序
                // $adapt['tod']['movement_timing'][]['timing']基于start_time排序,取得排序后的列表
                $sortArr = [];
                foreach ($movement['timing'] as $key => $row) {
                    $sortArr[$key] = $row['start_time'];
                }
                array_multisort($sortArr, SORT_DESC, $movement['timing']);
                $sortArr = [];
                foreach ($greenCurrents as $key => $row) {
                    $sortArr[$key] = $row['start_time'];
                }
                array_multisort($sortArr, SORT_DESC, $greenCurrents);

                $greens = arrayMergeRecursive($movement['timing'], $greenCurrents);

                // 数据处理
                $movement['timing'] = array_map($formatTiming, $greens);

                //提取 flow id
                $movement['logic_flow_id'] = ($movement['flow']['logic_flow_id'] ?? '') . ($movement['flow']['comment'] ?? '');
            }

            unset($movement);

            // 移除 flow id 为空的元素，并对数据进行合并处理
            $tod['movement_timing'] = Collection::make($tod['movement_timing'])
                ->when(true, $removeEmptyFlowId)
                ->groupBy('logic_flow_id', $mergeSameFlowIdTiming)
                ->values()->get();

            unset($tod['stage']);
        }

        unset($tod);

        return $adapt;
    }

    /**
     * 获取自适应配时信息
     *
     * @param $logicJunctionId
     *
     * @return array
     * @throws \Exception
     */
    public function getAdaptInfo($logicJunctionId)
    {
        $res = $this->adapt_model->getAdaptByJunctionId($logicJunctionId);

        if (!$res) {
            throw new \Exception('此路口无自适应配时信息', ERR_DATABASE);
        }

        if (empty($res['timing_info'])) {
            throw new \Exception('该路口无优化数据', ERR_DEFAULT);
        }

        $result = json_decode($res['timing_info'], true);

        if (!$result) {
            throw new \Exception('数据格式错误', ERR_DATABASE);
        }

        return $result['data'] ?? [];
    }

    /**
     * 获取基准配时信息
     *
     * @param $logic_junction_id
     *
     * @return array
     * @throws \Exception
     */
    private function getCurrentInfo($logic_junction_id)
    {
        //优先从db中获取数据
        $res = $this->adapt_model->getAdaptByJunctionId($logic_junction_id);
        if (!empty($res['current_info']) && $result = json_decode($res['current_info'], true)) {
            if(!empty($result['data']) && is_array($result['data'])){
                return $result['data'];
            }
        }

        $address = $this->config->item('signal_mis_interface') . '/TimingAdaptation/getCurrentTimingInfo';
        $res = httpGET($address, compact('logic_junction_id'));

        if (!$res) {
            throw new \Exception('获取当前生效配时失败', ERR_REQUEST_WAYMAP_API);
        }

        $result = json_decode($res, true);

        if (!$result) {
            throw new \Exception('生效配时错误', ERR_REQUEST_WAYMAP_API);
        }

        if (isset($result['errorCode']) && $result['errorCode'] != 0) {
            throw new \Exception($result['errorMsg'] ?? '未知错误', $result['errorCode']);
        }

        return $result['data'] ?? [];
    }

    /**
     * 获取指定 flow id 集合的二次停车比率
     * @param $logicFlowIds     相位ID
     * @param $logicJunctionId  路口ID
     * @param $cityId           城市ID
     * @return array
     * @throws \Exception
     */
    private function getTwiceStopRate($logicFlowIds, $logicJunctionId, $cityId)
    {
        if (empty($logicFlowIds)) {
            return [];
        }

        $hour = $this->helperService->getLastestHour($cityId);
        $res = $this->realtime_model->getFlowsInFlowIds($cityId, $hour, $logicJunctionId, $logicFlowIds);
        if (!$res) {
            com_log_warning('getTwiceStopRate_getFlowsInFlowIds_error', 0, "数据获取失败", array($cityId, $hour, $logicJunctionId, $logicFlowIds));
            return [];
        }

        return array_column($res, 'multiStopRatioUp', 'movementId');
    }

    /**
     * 获取基准配时详情
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getCurrentTimingInfo($params)
    {
        $logicJunctionId = $params['logic_junction_id'];

        $current = $this->getCurrentInfo($logicJunctionId);

        foreach ($current['tod'] ?? [] as $k => $tod) {
            foreach ($tod['stage'] ?? [] as $key => $stage) {
                $current['tod'][$k]['stage'][$key]['suggest_green_max'] = $stage['green_max'];
                $current['tod'][$k]['stage'][$key]['suggest_green_min'] = $stage['green_min'];
                $current['tod'][$k]['stage'][$key]['movements']         = array_values(array_filter($stage['movements'], function ($item) {
                    return $item['flow']['logic_flow_id'] != "";
                }));
            }
        }

        return $current;
    }

    /**
     * 下发基准配时
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function updateCurrentTiming($params)
    {
        $logicJunctionId = $params['logic_junction_id'];

        $url = $this->config->item('signal_mis_interface') . '/TimingAdaptation/uploadSignalTiming';

        // 通过路网接口获取数据
        $res = httpPOST($url, $params);

        if (!$res) {
            throw new \Exception('配时下发失败', ERR_DEFAULT);
        }

        $res = json_decode($res, true);

        if (!$res) {
            throw new \Exception('未知错误', ERR_DEFAULT);
        }

        if ($res['errorCode'] != 0) {
            throw new \Exception($res['errorMsg'], $res['errorCode']);
        }

        // 获取基准配时数据
        $current = $this->getCurrentInfo($logicJunctionId);
        $offset  = ($current['tod'][0]['extra_time']['offset'] ?? null);

        $data = [
            'update_time' => time(),
            'changed' => $offset == null ? 0 : ($offset == $params['offset']),
        ];

        $data = [
            'base_info' => json_encode($data),
        ];

        $result = $this->adapt_model->updateAdapt($logicJunctionId, $data);

        if (!$result) {
            throw new \Exception('记录下发基准配时信息失败', ERR_DATABASE);
        }

        return $res['data'] ?? [];
    }

    /**
     * 获取基准配时状态
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getCurrentStatus($params)
    {
        $logicJunctionId = $params['logic_junction_id'];

        // 获取数据源
        $res = $this->adapt_model->getAdaptByJunctionId($logicJunctionId);

        // 没有数据
        if (!$res) {
            throw new \Exception('该路口无配时', ERR_DATABASE);
        }

        // 配时错误
        if ($res['error_code']) {
            throw new \Exception('该路口配时错误', ERR_DEFAULT);
        }

        $currentInfo = json_decode($res['base_info'], true);

        if (!$currentInfo || empty($currentInfo)) {
            throw new \Exception('该路口尚未下发过基准配时方案', ERR_DEFAULT);
        }

        // 获取基准配时数据
        $currentResult = $this->getCurrentUpdateResult($logicJunctionId);

        if ($currentResult['timestamp'] <= $currentInfo['update_time']) {
            if (time() - $currentInfo['update_time'] >= 10 * 60) {
                return ['status' => 1, 'msg' => '基准配时下发中'];
            } else {
                return ['status' => 4, 'msg' => '基准配时下发超时'];
            }
        } else {
            if ($currentResult['status'] == 1) {
                return ['status' => 2, 'msg' => '基准配时下发成功'];
            } else {
                return ['status' => 3, 'msg' => '基准配时下发失败'];
            }
        }
    }

    /**
     * 获取路口配时成功下发时间
     *
     * @param $logic_junction_id
     *
     * @return array
     * @throws \Exception
     */
    private function getCurrentUpdateResult($logic_junction_id)
    {
        $url = $this->config->item('signal_mis_interface') . '/TimingAdaptation/getUploadStatus';

        $res = httpGET($url, compact('logic_junction_id'));

        $res = json_decode($res, true);
//        var_dump($res);exit;
        if (!$res || $res['errorCode'] != 0) {
            throw new \Exception('无法获取配时下发状态', ERR_DEFAULT);
        }

        return $res['data'][0] ?? [];
    }

    /**
     * 获取自适应配时状态
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getAdapteStatus($params)
    {
        $logicJunctionId = $params['logic_junction_id'];

        // 获取上次优化配时结果
        $res = $this->adapt_model->getAdaptByJunctionId($logicJunctionId);

        // 没有数据
        if (!$res) {
            throw new \Exception('该路口无配时', 0);
        }

        // 配时错误
        if ($res['error_code']) {
            throw new \Exception('该路口配时错误', ERR_DEFAULT);
        }

        //$baseInfo = []; //基准配时
        $baseInfo = json_decode($res['base_info'], true);
        $optStatus = json_decode($res['opt_status'], true);
        
        // 获取路口配时成功下发时间
        $currentResult = $this->getCurrentUpdateResult($logicJunctionId);

        // 获取信号机生效配时
        $currentTimingInfo = $this->getCurrentTimingInfo($params);

        // 获取方案时间
        $planTime = !empty($currentTimingInfo["start_time"])
            ? getTodayTimeOrFullTime($currentTimingInfo["start_time"])
            : "N/A";

        if (!$params['is_open']) {
            // 路口下发按钮未开启
            list($status, $tmp) = [1, 'a'];
        } elseif (!$baseInfo || empty($baseInfo)) {
            // 按钮开启 没有下发基准配时
            list($status, $tmp) = [2, 'b'];
        } elseif ($currentResult == null || $baseInfo['update_time'] > $currentResult['timestamp']) {
            // 按钮开启 下发基准配时过程中
            list($status, $tmp) = [3, 'e'];
        } elseif (!$currentResult['status']) {
            // 按钮开启 下发基准配时失败
            list($status, $tmp) = [2, 'c'];
        } elseif (!$baseInfo['changed']) {
            // 按钮开启 下发基准配时成功 方案未修改
            list($status, $tmp) = [2, 'd3'];
        } elseif (time() - $baseInfo['update_time'] <= 10 * 60) {
            // 按钮开启 下发成功 相位改变 10分钟内
            list($status, $tmp) = [3, 'd1'];
        } else {
            // 按钮开启 下发成功 相位改变 10分钟外
            list($status, $tmp) = [2, 'd2'];
        }
        $messages = [
            '1' => '正在优化',
            '2' => '正在进行自适应控制',
            '3' => '正在切换基本方案',
        ];
        
        // 下发频率获取
        $uploadInterval = 2;

        // 路口方案上次下发时间
        $lastUploadTime = !empty($res["down_time"]) && $res["down_time"]!="1970-01-01 00:00:00" && $res["down_time"]!="0000-00-00 00:00:00"
            ? getTodayTimeOrFullTime(strtotime($res["down_time"]))
            : "-";

        // 预计下次下发时间
        // 上次无下发时,显示当前时间
        // 否则,显示上次下发+2分钟
        $nextUploadTime = !isset($res['timing_update_time'])
            ? getTodayTimeOrFullTime(time())
            : getTodayTimeOrFullTime(strtotime($res['timing_update_time']) + $uploadInterval * 60);

        $adapteTime = isset($res['timing_update_time'])
            ? getTodayTimeOrFullTime(strtotime($res['timing_update_time']))
            : 'N/A';

        return [
            //方案获取时间
            'get_current_plan_time' => $planTime,
            //上次方案下发时间(更新上次调度程序的下发时间为上次方案下发时间) 数据来源是:调度程序
            'last_upload_time' => $lastUploadTime,
            //优化方案保存生成时间 == 优化方案下发时间 (是否下发取决于开关)
            'adapte_time' => $adapteTime,
            //预计下次方案下发时间
            'next_upload_time' => $nextUploadTime,
            'status' => $status,
            'opt_status' => $optStatus,
            'tmp' => $tmp,
            'message' => $messages[$status],
        ];
    }
}