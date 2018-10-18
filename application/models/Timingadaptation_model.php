<?php

use Didi\Cloud\Collection\Collection;

class Timingadaptation_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $this->load->helper('http_helper');
        $this->load->model('redis_model');
        $this->load->model('common_model');
        $this->load->config('adaption_conf');
    }

    /**
     * 获取自适应配时详情
     *
     * @param $params
     * @return array
     */
    public function getAdaptTimingInfo($params)
    {
        $logic_junction_id = $params['logic_junction_id'];

        $adapt = $this->getAdaptInfo($logic_junction_id);
        $current = $this->getCurrentInfo($logic_junction_id);

        $timingInfo = json_decode($adapt['timing_info'], true)['data'] ?? '';
        return $this->formatAdaptionTimingInfo($timingInfo, $current, $params);
    }

    /**
     * 获取基准配时详情
     *
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function getCurrentTimingInfo($params)
    {
        $data = $this->getCurrentInfo($params['logic_junction_id']);

        if($data == null) {
            return [];
        }

        return $this->formatCurrentTimingInfo($data);
    }

    /**
     * 基准配时下发
     *
     * @param $params
     * @return array
     * @throws Exception
     */
    public function updateCurrentTiming($params)
    {
        // 通过路网接口获取数据
        $res = httpPOST($this->config->item('url') . '/TimingAdaptation/uploadSignalTiming', $params);

        if(!$res) {
            throw new Exception('配时下发失败！');
        }

        $res = json_decode($res, true);

        if($res['errorCode'] ?? true) {
            throw new Exception($res['errorMsg'] ?? '未知错误');
        }

        // 获取基准配时数据
        $current = $this->getCurrentInfo($params['logic_junction_id']);
        $offset = ($current['tod'][0]['extra_time']['offset'] ?? null);

        $data = [
            'update_time' => time(),
            'changed' => $offset == null ? 0 : ($offset == $params['offset']),
        ];

        $this->db->set('current_info', json_encode($data))
            ->where('logic_junction_id', $params['logic_junction_id'])
            ->update('adapt_timing_mirror');

        return $res['data'] ?? [];
    }

    /**
     * 获取指定路口的最近一次基准配时的下发状态
     *
     * @param $params
     * @return array
     * @throws Exception
     */
    public function getCurrentStatus($params)
    {
        // 获取数据源
        $res = $this->db->select('*')
            ->from('adapt_timing_mirror')
            ->where('logic_junction_id', $params['logic_junction_id'])
            ->limit(1)
            ->get()->first_row('array');

        // 没有数据
        if(!$res) {
            throw new Exception('该路口无配时');
        }

        // 配时错误
        if($res['error_code']) {
            throw new Exception('该路口配时错误');
        }

        $current_info = json_decode($res['current_info'], true);

        if(!$current_info || empty($current_info)) {
            throw new Exception('该路口尚未下发过基准配时方案');
        }

        // 获取基准配时数据
        $current_result = $this->getCurrentUpdateResult($params['logic_junction_id']);

        $result = [];

        if($current_result['timestamp'] <= $current_info['update_time']) {
            if(time() - $current_info['update_time'] >= 10 * 60) {
                $result = [
                    'status' => 1,
                    'msg' => '基准配时下发中',
                ];
            } else {
                $result = [
                    'status' => 4,
                    'msg' => '基准配时下发超时',
                ];
            }
        } else {
            if($current_result['status'] == 1) {
                $result = [
                    'status' => 2,
                    'msg' => '基准配时下发成功',
                ];
            } else {
                $result = [
                    'status' => 3,
                    'msg' => '基准配时下发失败',
                ];
            }
        }

        return $result;
    }

    /**
     * 获取自适应配时状态
     *
     * @param $params
     * @return array
     * @throws Exception
     */
    public function getAdapteStatus($params)
    {
        // 获取数据源
        $res = $this->db->select('*')
            ->from('adapt_timing_mirror')
            ->where('logic_junction_id', $params['logic_junction_id'])
            ->limit(1)
            ->get()->first_row('array');

        // 没有数据
        if(!$res) {
            throw new Exception('该路口无配时', 0);
        }

        // 配时错误
        if($res['error_code']) {
            throw new Exception('该路口配时错误', ERR_PARAMETERS);
        }

        $current_info = json_decode($res['current_info'], true);

        // 获取基准配时数据
        $current_result = $this->getCurrentUpdateResult($params['logic_junction_id']);

        list($status, $tmp) = [null, null];

        if(!$params['is_open'])
            // 路口下发按钮未开启
            list($status, $tmp) = [1, 'a'];
        elseif (!$current_info || empty($current_info))
            // 按钮开启 没有下发基准配时
            list($status, $tmp) = [2, 'b'];
        elseif ($current_result == null || $current_info['update_time'] > $current_result['timestamp'])
            // 按钮开启 下发基准配时过程中
            list($status, $tmp) = [3, 'e'];
        elseif (!$current_result['status'])
            // 按钮开启 下发基准配时失败
            list($status, $tmp) = [2, 'c'];
        elseif (!$current_info['changed'])
            // 按钮开启 下发基准配时成功 方案未修改
            list($status, $tmp) = [2, 'd3'];
        elseif (time() - $current_info['update_time'] <= 10 * 60)
            // 按钮开启 下发成功 相位改变 10分钟内
            list($status, $tmp) = [3, 'd1'];
        else
            // 按钮开启 下发成功 相位改变 10分钟外
            list($status, $tmp) = [2, 'd2'];

        $messages = [
            '1' => '正在优化',
            '2' => '正在进行自适应控制',
            '3' => '正在切换基本方案',
        ];

        // 上次下发时间
        $lastUploadTime = strtotime($res['down_time']) > 0
            ? $res['down_time']
            : 'N/A';

        // 预计下次下发时间
        $nextUploadTime = $lastUploadTime == 'N/A'
            ? time()
            : ((strtotime($res['down_time']) > (time() - 5 * 60)
                ? strtotime($res['down_time'])
                : time()) + 5 * 60);

        return [
            'get_current_plan_time' => date('H:i:s'),
            'last_upload_time' => $lastUploadTime == 'N/A' ? 'N/A' : date('H:i:s', strtotime($lastUploadTime)),
            'adapte_time' => isset($res['timing_update_time']) ? date('H:i:s', strtotime($res['timing_update_time'])) : 'N/A',
            'next_upload_time' => date('H:i:s', $nextUploadTime),
            'status' => $status,
            'tmp' => $tmp,
            'message' => $messages[$status],
        ];

    }

    /**
     * 获取配时下发结果
     * @param $logic_junction_id
     * @return array|null
     */
    private function getCurrentUpdateResult($logic_junction_id)
    {
        $address = $this->config->item('url') . '/TimingAdaptation/getUploadStatus';
        $res = httpGET($address, compact('logic_junction_id'));

        $res = json_decode($res, true);

        if(!$res || $res['errorCode'] != 0) {
            return null;
        }

        return $res['data'][0] ?? [];
    }

    /**
     * 格式化基准配时方案
     * @param $current
     * @return mixed
     */
    private function formatCurrentTimingInfo($current)
    {
        foreach ($current['tod'] ?? [] as $k => $tod)
        {
            foreach ($tod['stage'] ?? [] as $key => $stage)
            {
                $current['tod'][$k]['stage'][$key]['suggest_green_max'] = $stage['green_max'];
                $current['tod'][$k]['stage'][$key]['suggest_green_min'] = $stage['green_min'];

                $current['tod'][$k]['stage'][$key]['movements'] = array_values(array_filter($stage['movements'], function ($item) {
                    return $item['flow']['logic_flow_id'] != "";
                }));
            }
        }
        return $current;
    }

    /**
     * 格式化自适应配时方案
     * @param $adapt
     * @param $current
     * @param $params
     * @return array
     */
    private function formatAdaptionTimingInfo($adapt, $current, $params)
    {
        // 从 movement_timing 数据中抽出 flow id
        $getFlowIdsCallback = function ($movementTiming) {
              return array_filter(
                  array_column(array_column($movementTiming, 'flow'), 'logic_flow_id')
              );
        };

        // 自适应和基准数据合并之后的格式化处理
        $formatTimingCallback = function ($timing) {
            return [
                'start_time'            => $timing['start_time'][1] ?? '',
                'suggest_start_time'    => $timing['start_time'][0] ?? '',
                'duration'              => $timing['duration'][1] ?? '',
                'suggest_duration'      => $timing['duration'][0] ?? '',
                'max'                   => $timing['max'][1] ?? '',
                'suggest_max'           => $timing['max'][0] ?? '',
                'min'                   => $timing['min'][1] ?? '',
                'suggest_min'           => $timing['min'][0] ?? '',
                'start_time_change'     => (int)($timing['duration'][0] ?? 0) - (int)($timing['duration'][1] ?? 0),
                'duration_change'       => (int)($timing['duration'][0] ?? 0) - (int)($timing['duration'][1] ?? 0),
            ];
        };

        // 移除 flow id 为空的元素
        $removeEmptyFlowIdItemCallback = function ($movement_timing) {
            return array_values(
                array_filter($movement_timing, function ($item) {
                    return $item['flow']['logic_flow_id'] != '';
                })
            );
        };

        $mergeSameFlowIdTimingCallback = function ($data) {
            $result = array_shift($data);
            unset($result['logic_flow_id']);
            return array_reduce($data, function ($carry, $item) {
                $carry['timing'] = array_merge($carry['timing'], $item['timing']);
                return $carry;
            }, $result);
        };

        if(empty($adapt) || empty($current)) {
            return [];
        }

        foreach ($adapt['tod'] as $tk => &$tod) {

            $tod['extra_time']['tod_end_time'] = date('H:i', strtotime($tod['extra_time']['tod_end_time']));
            $tod['extra_time']['tod_start_time'] = date('H:i', strtotime($tod['extra_time']['tod_start_time']));

            // 获取 该方向 flow Ids
            $flowIds = call_user_func($getFlowIdsCallback, $tod['movement_timing']);

            // 根据 flow id 获取 二次停车比率
            $flows = $this->getTwiceStopRate($flowIds, $params['logic_junction_id'], $params['city_id']);

            // 数据处理
            foreach ($tod['movement_timing'] as $mk => &$movement) {

                $movement['flow']['twice_stop_rate'] = $flows[$movement['flow']['logic_flow_id']] ?? '/';

                // 获取并过滤出 基准配时中的绿灯
                $currentTiming = $current['tod'][$tk]['movement_timing'][$mk]['timing'] ?? [];
                $greenCurrents = Collection::make($currentTiming)->where('state', 1)->get();

                // 自适应和基准配时数据递归合并
                $greens = arrayMergeRecursive($movement['timing'], $greenCurrents);

                // 数据处理
                $movement['timing'] = array_map($formatTimingCallback, $greens);

                //提取 flow id
                $movement['logic_flow_id'] = ($movement['flow']['logic_flow_id'] ?? '') . ($movement['flow']['comment'] ?? '');
            }

            unset($movement);

            // 移除 flow id 为空的元素
            $tod['movement_timing'] = call_user_func($removeEmptyFlowIdItemCallback, $tod['movement_timing']);

            $tod['movement_timing'] = Collection::make($tod['movement_timing'])
                ->groupBy('logic_flow_id', $mergeSameFlowIdTimingCallback)
                ->values()->get();

            unset($tod['stage']);
        }

        return $adapt;
    }

    /**
     * 获取算法组给定的配时信息方案
     * @param $logic_junction_id
     * @return mixed
     */
    private function getAdaptInfo($logic_junction_id)
    {
        return $this->db->select('*')
            ->from('adapt_timing_mirror')
            ->where('logic_junction_id', $logic_junction_id)
            ->get()->first_row('array');
    }

    /**
     * 获取当前的基准配时方案
     * @param $logic_junction_id
     * @return array
     */
    private function getCurrentInfo($logic_junction_id)
    {
        $address = $this->config->item('url') . '/TimingAdaptation/getCurrentTimingInfo';
        $res = httpGET($address, compact('logic_junction_id'));

        $res = json_decode($res, true);

        if(!$res || $res['errorCode'] != 0) {
            return null;
        }

        return $res['data'] ?? [];
    }

    /**
     * 获取指定 flowIds 集合的对应 二次停车比率
     * @param $flowIds
     * @param $cityId
     * @return array
     */
    private function getTwiceStopRate($flowIds, $logicJunctionId, $cityId)
    {
        if(empty($flowIds)) {
            return [];
        }

        $hour = $this->common_model->getLastestHour($cityId);

        $flows = $this->db->select('logic_flow_id, twice_stop_rate')
            ->from('real_time_' . $cityId)
            ->where('hour', $hour)
            ->where('logic_junction_id', $logicJunctionId)
            ->where('updated_at > ', date('Y-m-d', strtotime('-10  minutes')))
            ->where_in('logic_flow_id', $flowIds)
            ->get()->result_array();

        return array_column($flows, 'twice_stop_rate', 'logic_flow_id');
    }
}
