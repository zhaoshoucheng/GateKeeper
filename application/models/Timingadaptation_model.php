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
            json_decode($adapt['timing_info'], true)['data'] ?? '', $current, $params['city_id']);
    }

    public function getCurrentTimingInfo($params)
    {
        $data = $this->getCurrentInfo($params['logic_junction_id']);

        return $this->formatCurrentTimingInfo($data);
    }

    public function updateCurrentTiming($params)
    {
        echo httpPOST($this->config->item('url') . '/TimingAdaptation/uploadSignalTiming', $params);
        exit();
    }

    public function getAdapteStatus($params)
    {
        $res = $this->db->select('*')
            ->from('adapt_timing_mirror')
            ->where('logic_junction_id', $params['logic_junction_id'])
            ->limit(1)
            ->get()->first_row('array');

        if(!$res || !$res['error_code'])
            throw new Exception('该路口数据有误');

        $status = null;
        $tmp = null;

        if(!$params['is_open'])
        {
            // 路口下发按钮未开启
            $status = 1;
            $tmp = 'a';
        }
        else
        {
            // 按钮开启
            // Question : 无法判断没有下发基准配时
            // Question : 无法判断下发基准配时失败

            if(strtotime($res['timing_update_time']) > strtotime($res['down_time']))
            {
                // 基准配时下发中
                $status = 3;
                $tmp = 'e';
            }
            else
            {
                // 基准配时下发成功
                $tmp = 'd';


            }

        }

        $result = [
            'get_current_plan_time' => date('H:i:s'),
            'last_upload_time' => $res['down_time'],
            'adapte_time' => $res['timing_update_time'],
            'next_upload_time' => date('H:i:s', strtotime('+5 minutes', strtotime($res['down_time']))),
//            'status' =>
        ];

    }

    private function formatCurrentTimingInfo($current)
    {
        foreach ($current['tod'] as &$tod)
        {
            foreach ($tod['stage'] as &$stage) {
                $stage['suggest_green_max'] = $stage['green_max'];
                $stage['suggest_green_min'] = $stage['green_min'];
            }
        }
        return $current;
    }

    private function formatAdaptionTimingInfo($adapt, $current, $cityId)
    {
        if(empty($adapt) || empty($current))
            return [];

        foreach ($adapt['tod'] as $tk => &$tod) {
            $flowIds = array_column(array_column($tod, 'flow'), 'logic_flow_id');
            $flows = $this->getTwiceStopRate($flowIds, $cityId);
            foreach ($tod['movement_timing'] as $mk => &$movement) {
                $movement['flow']['twice_stop_rate'] = $flows[$movement['flow']['logic_flow_id']] ?? '';
                $adaptTimings = Collection::make($movement['timing'] ?? []);
                $currentTimings = Collection::make($current['tod'][$tk]['movement_timing'][$mk]['timing'] ?? []);
                $movement['yellow'] = $adaptTimings->first(function ($timing) { return $timing['state'] == 2; })['duration'] ?? '';
                $greenAdapts = $adaptTimings->where('state', 1)->get();
                $greenCurrents = $currentTimings->where('state', 1)->get();
                $greens = $this->arrayMergeRecursive($greenAdapts, $greenCurrents);
                $movement['timing'] = array_map(function ($timing) {
                    return [
                        'start_time' => $timing['start_time'][1] ?? '',
                        'suggest_start_time' => $timing['start_time'][0] ?? '',
                        'duration' => $timing['duration'][1] ?? '',
                        'suggest_duration' => $timing['duration'][1] ?? '',
                        'max' => $timing['max'][1] ?? '',
                        'suggest_max' => $timing['max'][1] ?? '',
                        'min' => $timing['min'][1] ?? '',
                        'suggest_min' => $timing['min'][1] ?? '',
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

    private function getCurrentInfo($logic_junction_id)
    {
        $address = $this->config->item('url') . '/TimingAdaptation/getCurrentTimingInfo';
        $res = httpGET($address, compact('logic_junction_id'));

        if(!$res) return [];

        return json_decode($res, true)['data'] ?? [];
    }

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

    private function getTwiceStopRate($flowIds, $cityId)
    {
        if(empty($flowIds)) return [];

        $hour = $this->getLastestHour($cityId);

        $flows = $this->db->select('logic_flow_id, twice_stop_rate')
            ->from('flow_duration_v6_' . $cityId)
            ->where('hour', $hour)
            ->where_in('logic_flow_id', $flowIds)
            ->get()->result_array();

        return array_column($flows, 'twice_stop_rate', 'logic_flow_id');
    }

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
