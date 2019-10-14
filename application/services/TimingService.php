<?php
namespace Services;

use Didi\Cloud\Collection\Collection;

class TimingService extends BaseService {
    protected $helperService;

    public function __construct() {
        parent::__construct();
        date_default_timezone_set('Asia/Shanghai');

        $this->load->model('timing_model');

        $this->load->config('nconf');
        $this->load->helper('http_helper');
        $this->load->helper('http');

        $this->helperService = new HelperService();
    }

    public function getOptimizeTiming($data) {
        $time_range = array_filter(explode('-', trim($data['time_range'])));
        $date = $data['dates'][0];
        $reqdate = substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2);
        $req = [
            'logic_junction_id' => $data['junction_id'],
            'source'            => '2,1',
            'start_time'        => trim($time_range[0]).":00",
            'end_time'          => trim($time_range[1]).":00",
            'date'              => $reqdate,
            'format'            => 0,
        ];

        $timing = $this->timing_model->getNewTiming($req);
        if (empty($timing) or !isset($timing['schedule'][0]['tod'])) {
            return [];
        }
        $timing_tods = $timing['schedule'][0]['tod'];

        $ret = [];
        foreach ($timing_tods as $key => $value) {
            if ($value['end_time'] == '00:00:00') {
                $value['end_time'] = '24:00:00';
            }
            if ($value['start_time'] <= $value['end_time']) {
                $ret[] = [
                    'start'=>$value['start_time'],
                    'end'=>$value['end_time'],
                ];
            } else {
                $ret[] = [
                    'start'=>$value['start_time'],
                    'end'=>'24:00:00',
                ];
                $ret[] = [
                    'start'=>'00:00:00',
                    'end'=>$value['end_time'],
                ];
            }
        }
        usort($ret, function($a, $b) {
            if ($a['start'] != $b['end']) {
                return ($a['start'] < $b['end']) ? -1 : 1;
            }
            return -1;
        });
        foreach ($ret as $key => $value) {
            $ret[$key]['comment'] = (string)($key+1);
        }
        return $ret;
    }

    public function queryArterialTimingInfo($data) {
        $junction_flow = [];
        foreach ($data['junction_infos'] as $value) {
            $logic_junction_id = $value['logic_junction_id'];
            $flows =$value['flows'];
            if (empty($flows)) {
                continue;
            }
            foreach ($flows as $flow) {
                $junction_flow[$logic_junction_id][] = $flow;
            }
        }
        $logic_junction_ids = array_keys($junction_flow);

        $date = $data['dates'][0];
        $reqdate = substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2);
        $time_point = $data['time_point'];
        $source = $data['source'];
        if ($source == 0) {
            $source = '2,1';
        }
        $req = [
            'logic_junction_ids' => implode(',', $logic_junction_ids),
            'source'            => $source,
            'start_time'        => $time_point.":00",
            'end_time'          => $time_point.":01",
            'date'              => $reqdate,
            'format'            => 2,
        ];

        $timing = $this->timing_model->getNewTiming($req);
        if (empty($timing)) {
            return [];
        }
        $tmp = [];
        foreach ($timing as $value) {
            if ($value['errno'] == 0) {
                $tmp[$value['junction_id']] = $value;
            }
        }
        $timing = $tmp;

        $ret = [];
        foreach ($junction_flow as $logic_junction_id => $flows) {
            $one = [
                'id' => 0,
                'date' => $date,
                'junction_id' => $logic_junction_id,
                'logic_junction_id' => $logic_junction_id,
                'timing_info' => [
                    'extra_timing' => [
                        'cycle' => 0,
                        'offset' => 0,
                    ],
                    'tod_start_time' => '00:00:00',
                    'tod_end_time' => '00:00:00',
                    'movement_timing' => [],
                ],
            ];
            if (isset($timing[$logic_junction_id]) and !empty($timing[$logic_junction_id]['tod'])) {
                $tod = $timing[$logic_junction_id]['tod'];
                $one['id'] = $timing[$logic_junction_id]['signal_id'];
                $one['timing_info']['tod_start_time'] =$tod['start_time'];
                $one['timing_info']['tod_end_time'] =$tod['end_time'];

                if ($tod['plan']['id'] != 251 and $tod['plan']['id'] != 252 and $tod['plan']['id'] != 255) {
                    $one['timing_info']['extra_timing']['cycle'] = $tod['plan']['cycle'];
                    $one['timing_info']['extra_timing']['offset'] = $tod['plan']['offset'];
                    foreach ($tod['plan']['movements'] as $movement) {
                        if (in_array($movement['id'], $flows)) {
                            foreach ($movement['sub_phases'] as $phase) {
                                $one['timing_info']['movement_timing'][] = [
                                    "comment"=> "",
                                    "logic_flow_id"=> $movement['id'],
                                    "start_time"=> $phase['start_time'],
                                    "duration"=> $phase['green'],
                                ];
                            }
                        }
                    }
                } else {
                    $one['timing_info']['extra_timing']['cycle'] = 120;
                    foreach ($flows as $flow) {
                        $one['timing_info']['movement_timing'][] = [
                            "comment"=> "",
                            "logic_flow_id"=> $flow,
                            "start_time"=> 0,
                            "duration"=> 120
                        ];
                    }
                }
            }
            $ret[$logic_junction_id][] = $one;
        }

        return $ret;
    }
}
