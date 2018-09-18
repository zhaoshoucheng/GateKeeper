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

        $data = $this->arrayMergeRecursive(json_decode($adapt['timing_info'], true)['data'] ?? [], $current);

        return $data;
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