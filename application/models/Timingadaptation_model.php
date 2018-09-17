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

        $res = $this->db->select('*')
            ->from('adapt_timing_mirror')
            ->where('logic_junction_id', $logic_junction_id)
            ->get()->first_row('array');

        $data = json_decode($res['timing_info'], true);

        $data['tod'] = array_map(function ($v) {
             return [
                 'plan_id' => $v['plan_id'] ?? '',
                 'extra_time' => $v['extra_time'] ?? '',
                 'movement_timing' => array_map(function ($movement) {
                     $flow = $movement['flow'] ?? [];
                     $timing = $movement['timing'] ?? [];
                     return [
                         'flow' => $flow,
                         'movement_id' => $movement['movement_id'],
                         'channel' => $movement['channel'],
                         'phase_id' => $movement['phase_id'],
                         'phase_seq' => $movement['phase_seq'],
                         'yellow' => '',
                         'timing' => $timing,
                     ];
                 }, $v['movement_timing'])
             ];
        }, $data['tod']);

        return $data;
    }
}