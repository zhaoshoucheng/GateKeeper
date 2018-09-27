<?php

class Timingadaptation_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
        $this->load->helper('http_helper');
        $this->load->model('redis_model');
        $this->load->config('adaption_conf');
    }

    public function getAdaptTimingInfo($params)
    {
        $logic_junction_id = $params['logic_junction_id'];

        $adapt = $this->getAdaptInfo($logic_junction_id);
        $current = $this->getCurrentInfo($logic_junction_id);

        return $this->formatAdaptionTimingInfo(
            json_decode($adapt['timing_info'], true)['data'] ?? '', $current, $params);
    }

    public function getCurrentTimingInfo($params)
    {
        $data = $this->getCurrentInfo($params['logic_junction_id']);

        if($data == null)
            throw new Exception('数据获取失败！');

        return $this->formatCurrentTimingInfo($data);
    }

    public function updateCurrentTiming($params)
    {
        $res = httpPOST($this->config->item('url') . '/TimingAdaptation/uploadSignalTiming', $params);

        if(!$res)
            throw new Exception('配时下发失败！');

        $res = json_decode($res, true);

        if($res['errorCode'] ?? true)
            throw new Exception($res['errorMsg'] ?? '未知错误');

        $current = $this->getCurrentInfo($params['logic_junction_id']);
        $offset = ($current['tod'][0]['extra_time']['offset'] ?? null);

        $data = [
            'update_time' => time(),
            'changed' => $offset == null ? 0 : ($offset == $params['offset']),
        ];

        $this->db
            ->set('current_info', json_encode($data))
            ->where('logic_junction_id', $params['logic_junction_id'])
            ->update('adapt_timing_mirror');

        return $res['data'] ?? [];
    }

    public function getAdapteStatus($params)
    {
        $res = $this->db->select('*')
            ->from('adapt_timing_mirror')
            ->where('logic_junction_id', $params['logic_junction_id'])
            ->limit(1)
            ->get()->first_row('array');

        if(!$res || $res['error_code'])
            throw new Exception('该路口数据有误');

        $current_info = json_decode($res['current_info'], true);
        $current_result = $this->getCurrentUpdateResult();

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

        return [
            'get_current_plan_time' => date('Y-m-d H:i:s'),
            'last_upload_time' => date('Y-m-d H:i:s', $current_info['update_time']),
            'adapte_time' => $res['timing_update_time'],
            'next_upload_time' => date('Y-m-d H:i:s', strtotime('+5 minute', $current_info['update_time'])),
            'status' => $status,
            'tmp' => $tmp,
            'message' => $messages[$status],
        ];

    }

    /**
     * 获取配时下发结果
     */
    private function getCurrentUpdateResult()
    {
        $address = $this->config->item('url') . '/TimingAdaptation/getUploadStatus';
        $res = httpGET($address, compact('logic_junction_id'));

        $res = json_decode($res, true);

        if(!$res || $res['errorCode'] != 0)
            return null;

        return $res['data'][0] ?? [];
    }

    /**
     * 格式化基准配时方案
     * @param $current
     * @return mixed
     */
    private function formatCurrentTimingInfo($current)
    {
        foreach ($current['tod'] ?? [] as &$tod)
        {
            foreach ($tod['stage'] ?? [] as &$stage) {
                $stage['suggest_green_max'] = $stage['green_max'];
                $stage['suggest_green_min'] = $stage['green_min'];
            }
        }
        return $current;
    }

    /**
     * 格式化自适应配时方案
     * @param $adapt
     * @param $current
     * @param $cityId
     * @return array
     */
    private function formatAdaptionTimingInfo($adapt, $current, $params)
    {
        if(empty($adapt) || empty($current))
            return [];

        foreach ($adapt['tod'] as $tk => &$tod) {
            $flowIds = array_column(array_column($tod['movement_timing'], 'flow'), 'logic_flow_id');
            $flows = $this->getTwiceStopRate($flowIds, $params);
            foreach ($tod['movement_timing'] as $mk => &$movement) {
                if($movement['flow']['logic_flow_id'] == '') {
                    unset($tod['movement_timing'][$mk]);
                    continue;
                }

                $movement['flow']['twice_stop_rate'] = $flows[$movement['flow']['logic_flow_id']] ?? '';
                $greenCurrents = Collection::make($current['tod'][$tk]['movement_timing'][$mk]['timing'] ?? [])->where('state', 1)->get();
                $greens = $this->arrayMergeRecursive($movement['timing'], $greenCurrents);
                $movement['timing'] = array_map(function ($timing) {
                    return [
                        'start_time' => $timing['start_time'][1] ?? '',
                        'suggest_start_time' => $timing['start_time'][0] ?? '',
                        'duration' => $timing['duration'][1] ?? '',
                        'suggest_duration' => $timing['duration'][0] ?? '',
                        'max' => $timing['max'][1] ?? '',
                        'suggest_max' => $timing['max'][0] ?? '',
                        'min' => $timing['min'][1] ?? '',
                        'suggest_min' => $timing['min'][0] ?? '',
                        'start_time_change' => ($timing['duration'][0] ?? '') - ($timing['duration'][1] ?? ''),
                        'duration_change' => ($timing['duration'][0] ?? '') - ($timing['duration'][1] ?? ''),
                    ];
                }, $greens);
            }
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

        if(!$res || $res['errorCode'] != 0)
            return null;

        return $res['data'] ?? [];
    }

    /**
     * 递归合并数组（支持数字键数组）
     * @param $target
     * @param $source
     * @return mixed
     */
    private function arrayMergeRecursive($target, $source)
    {
        $tkeys = array_keys($target);
        $skeys = array_keys($source);

        $keys = array_unique(array_merge($tkeys, $skeys));

        foreach ($keys as $key) {
            if(array_key_exists($key, $source) && !array_key_exists($key, $target)) {
                $target[$key] = $source[$key];
            } elseif (array_key_exists($key, $source) && array_key_exists($key, $target)) {
                if(is_array($target[$key]) && is_array($source[$key])) {
                    $target[$key] = $this->arrayMergeRecursive($target[$key], $source[$key]);
                } else {
                    $target[$key] = [$target[$key], $source[$key]];
                }
            }
        }
        return $target;
    }

    /**
     * 获取指定 flowIds 集合的对应 二次停车比率
     * @param $flowIds
     * @param $cityId
     * @return array
     */
    private function getTwiceStopRate($flowIds, $params)
    {
        if(empty($flowIds)) return [];

        $hour = $this->getLastestHour($params['city_id']);

        $flows = $this->db->select('logic_flow_id, twice_stop_rate')
            ->from('flow_duration_v6_' . $params['city_id'])
            ->where('hour', $hour)
            ->where('logic_junction_id', $params['logic_junction_id'])
            ->where('date', date('Y-m-d'))
            ->where_in('logic_flow_id', $flowIds)
            ->get()->result_array();

        return array_column($flows, 'twice_stop_rate', 'logic_flow_id');
    }

    /**
     * 获取最新批次的 hour
     * @param $cityId
     * @param null $date
     * @return false|string
     */
    public function getLastestHour($cityId, $date = null)
    {
        if(($hour = $this->redis_model->getData("its_realtime_lasthour_$cityId"))) {
            return $hour;
        }

        if (!$this->isTableExisted('flow_duration_v6_' . $cityId)) {
            return date('H:i:s');
        }

        $date = $date ?? date('Y-m-d');
        /*$result = $this->db->select('hour')
            ->from($this->tb . $cityId)
            ->where('updated_at >=', $date . ' 00:00:00')
            ->where('updated_at <=', $date . ' 23:59:59')
            ->order_by('hour', 'desc')
            ->limit(1)
            ->get()->first_row();*/
        // 查询优化
        $sql = "SELECT `hour` FROM `real_time_{$cityId}`  WHERE 1 ORDER BY updated_at DESC,hour DESC LIMIT 1";
        $result = $this->db->query($sql)->first_row();
        if(!$result)
            return date('H:i:s');

        return $result->hour;
    }
}
