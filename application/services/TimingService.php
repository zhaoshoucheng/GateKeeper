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
}
