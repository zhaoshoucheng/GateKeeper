<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/24
 * Time: 上午11:35
 */

namespace Services;


use Didi\Cloud\Collection\Collection;

class OverviewService extends BaseService
{
    protected $helperService;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('redis_model');
        $this->load->model('waymap_model');
        $this->load->model('realtime_model');

        $this->helperService = new HelperService();
    }

    /**
     * @param $params
     *
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public function junctionsList($params)
    {
        $cityId = $params['city_id'];
        $date = $params['date'];

        $hour = $this->helperService->getLastestHour($cityId);

        $redisKey = "its_realtime_pretreat_junction_list_{$cityId}_{$date}_{$hour}";

        $junctionList = $this->redis_model->getData($redisKey);

        if ($junctionList) {

            $junctionList = json_decode($junctionList, true);

            $nanchang = $this->config->item('nanchang');

            $username = get_instance()->username;

            if (array_key_exists($username, $nanchang)) {
                $junctionList['dataList'] = Collection::make($junctionList['dataList'])
                    ->whereIn('jid', $nanchang[$username])
                    ->values();

                $junctionList['center']['lng'] = $junctionList['dataList']->avg('lng');
                $junctionList['center']['lat'] = $junctionList['dataList']->avg('lat');
            }

            return $junctionList;
        }

        return [];
    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function junctionSurvey($params)
    {
        $data = $this->junctionsList($params);

        $data = $data['dataList'] ?? [];

        $result = [];

        $result['junction_total']   = count($data);
        $result['alarm_total']      = 0;
        $result['congestion_total'] = 0;

        foreach ($data as $datum) {
            $result['alarm_total']      += $datum['alarm']['is'] ?? 0;
            $result['congestion_total'] += (int)(($datum['status']['key'] ?? 0) == 3);
        }

        return $result;
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function operationCondition($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $redisKey = 'its_realtime_avg_stop_delay_' . $cityId . '_' . $date;

        $res = $this->redis_model->getData($redisKey);

        $result = $res
            ? json_decode($res, true)
            : $this->realtime_model->getAvgQuotaByCityId($cityId, $date, 'hour, avg(stop_delay) as avg_stop_delay');

        $realTimeQuota = $this->config->item('real_time_quota');

        $formatQuotaRoundHour = function ($v) use ($realTimeQuota) {
            return [
                $realTimeQuota['stop_delay']['round']($v['avg_stop_delay']),
                substr($v['hour'], 0, 5),
            ];
        };

        $ext = [];

        $findSkip =  function ($carry, $item) use (&$ext) {
            $now = strtotime($item[1] ?? '00:00');
            if ($now - $carry >= 30 * 60) {
                $ext = array_merge($ext, range($carry + 5 * 60, $now - 5 * 60, 5 * 60));
            }
            return $now;
        };

        $resultCollection = Collection::make($result)->map($formatQuotaRoundHour);

        $info = [
            'value' =>  $resultCollection->avg(0, $realTimeQuota['stop_delay']['round']),
            'unit' => $realTimeQuota['stop_delay']['unit'],
        ];

        $resultCollection->reduce($findSkip, strtotime('00:00'));

        $result = $resultCollection->merge(array_map(function ($v) {
            return [null, date('H:i', $v)];
        }, $ext))->sortBy(1);

        return [
            'dataList' => $result,
            'info' => $info,
        ];
    }


}