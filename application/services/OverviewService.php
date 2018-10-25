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

    /**
     * OverviewService constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('redis_model');
        $this->load->model('realtime_model');

        $this->config->load('permission/nanchang_overview_conf');
        $this->config->load('realtime_conf');

        $this->helperService = new HelperService();
    }

    /**
     * 路口概况
     *
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
     * 路口列表
     *
     * @param $params
     *
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public function junctionsList($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

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
     * 运行情况
     *
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

        $findSkip = function ($carry, $item) use (&$ext) {
            $now = strtotime($item[1] ?? '00:00');
            if ($now - $carry >= 30 * 60) {
                $ext = array_merge($ext, range($carry + 5 * 60, $now - 5 * 60, 5 * 60));
            }
            return $now;
        };

        $resultCollection = Collection::make($result)->map($formatQuotaRoundHour);

        $info = [
            'value' => $resultCollection->avg(0, $realTimeQuota['stop_delay']['round']),
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

    /**
     * 拥堵概览
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function getCongestionInfo($params)
    {
        $cityId = $params['city_id'];
        $date   = $params['date'];

        $hour = $this->helperService->getLastestHour($cityId);

        $select = 'SUM(`stop_delay` * `traj_count`) / SUM(`traj_count`) as stop_delay, logic_junction_id, hour, updated_at';

        $res = $this->realtime_model->getAvgQuotaByJunction($cityId, $hour, $date, $select);

        if (!$res) {
            return [];
        }

        $nanchang = $this->config->item('nanchang');
        $username = get_instance()->username;

        if (array_key_exists($username, $nanchang)) {
            $res = Collection::make($res)
                ->whereIn('logic_junction_id', $nanchang[$username])
                ->values()->get();
        }

        $result = [];

        // 拥堵数量
        $congestionNum = [];

        // 路口总数
        $junctionTotal = count($res);

        // 路口状态配置
        $junctionStatusConf = $this->config->item('junction_status');
        // 路口状态计算规则
        $junctinStatusFormula = $this->config->item('junction_status_formula');

        foreach ($res as $k => $v) {
            $congestionNum[$junctinStatusFormula($v['stop_delay'])][$k] = 1;
        }

        $result['count'] = [];
        $result['ratio'] = [];
        foreach ($junctionStatusConf as $k => $v) {
            $result['count'][$k] = [
                'cate' => $v['name'],
                'num' => isset($congestionNum[$k]) ? count($congestionNum[$k]) : 0,
            ];

            $result['ratio'][$k] = [
                'cate' => $v['name'],
                'ratio' => isset($congestionNum[$k])
                    ? round((count($congestionNum[$k]) / $junctionTotal) * 100) . '%'
                    : '0%',
            ];
        }

        $result['count'] = array_values($result['count']);
        $result['ratio'] = array_values($result['ratio']);

        return $result;
    }

    /**
     * 获取 token
     *
     * @return array
     */
    public function getToken()
    {
        $token = md5(time() . rand(1, 10000) * rand(1, 10000));

        $this->redis_model->setData('Token_' . $token, $token);
        $this->redis_model->setExpire('Token_' . $token, 60 * 30);

        return [
            $token,
        ];
    }

    /**
     * 验证 token
     *
     * @param $params
     *
     * @return array
     */
    public function verifyToken($params)
    {
        $tokenval = 'Token_' . $params['tokenval'];

        $data = [];

        if (!$this->redis_model->getData($tokenval)) {
            $data['verify'] = false;
        } else {
            $this->redis_model->deleteData($tokenval);
            $data['verify'] = true;
        }

        return $data;
    }

    /**
     * 获取当前服务器时间
     *
     * @return array
     */
    public function getNowDate()
    {
        $weekArray = [
            '日', '一', '二', '三', '四', '五', '六',
        ];

        $time = time();

        return [
            'date' => date('Y-m-d', $time),
            'time' => date('H:i:s', $time),
            'week' => '星期' . $weekArray[date('w', $time)],
        ];
    }
}