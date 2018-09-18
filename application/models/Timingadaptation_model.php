<?php

class Timingadaptation_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->db = $this->load->database('default', true);
    }

    public function getAdaptTimingInfo($params)
    {
        $logic_junction_id = $params['logic_junction_id'];

        $adapt = $this->getAdaptInfo($logic_junction_id);
        $current = $this->getCurrentInfo($logic_junction_id);

        $tod_movement = Collection::make($current['tod'] ?? [])
            ->column(null, 'tod_id')->map(function ($tod) {
                return Collection::make($tod['movement_timing'] ?? [])
                    ->column(null, 'movement_id')->map(function ($movement) {
                        return Collection::make($movement['timing'] ?? [])
                            ->column('state')->get();
                    })->get();
            });

        return $tod_movement;
//
//        $data['tod'] = array_map(function ($tod) use ($tod_movement) {
//            return [
//                'plan_id' => $tod['plan_id'],
//                'extra_time' => $tod['extra_time'],
//                'movement_timing' => array_map(function ($movement) use ($tod, $tod_movement)  {
//                    return [
//                        'movement_id' => $movement['movement_id'] ?? '',
//                        'channel' => $movement['channel'] ?? '',
//                        'phase_id' => $movement['phase_id'] ?? '',
//                        'phase_seq' => $movement['phase_seq'] ?? '',
//                        'yellow' => $tod_movement[$tod['plan_id']][$movement['movement_id']][2]['duration'] ?? '',
//                        'timing' => [
//                            'start_time' => $tod_movement[$tod['plan_id']][$movement['movement_id']][1]['start_time'] ?? '',
//                            'suggest_start_time' => $movement['timing'][1]['start_time'] ?? '',
//                            'duration' => $tod_movement[$tod['plan_id']][$movement['movement_id']][1]['duration'] ?? '',
//                            'suggest_duration' => $movement['timing'][1]['duration'] ?? '',
//                            'max' => $tod_movement[$tod['plan_id']][$movement['movement_id']][1]['max'] ?? '',
//                            'suggest_max' => $movement['timing'][1]['max'] ?? '',
//                            'min' => $tod_movement[$tod['plan_id']][$movement['movement_id']][1]['min'] ?? '',
//                            'suggest_min' => $movement['timing'][1]['min'] ?? '',
//                        ],
//                        'flow' => $movement['flow'],
//                    ];
//                }, $tod['movement_timing']),
//            ];
//        }, $data['tod'] ?? []);


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
        $address = 'http://100.90.164.31:8006/signal-mis/TimingAdaptation/getCurrentTimingInfo';
        $res = httpGET($address, compact('logic_junction_id'));
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
}