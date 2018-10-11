<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/11
 * Time: 下午3:38
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

class AreaService extends BaseService
{
    protected $area_model;

    public function __construct()
    {
        $this->area_model = new \Area_model();

        $this->load->model([
            'waymap_model', 'redis_model'
        ]);

        $this->load->config('nconf');
        $this->load->config('realtime_conf');
        $this->load->config('evaluate_conf');
    }

    /**
     * @param $params
     * @return mixed
     */
    public function getList($params)
    {
        $cityId = $params['city_id'];

        return $this->area_model->getAreasByCityId($cityId);
    }

    /**
     * @param $params
     * @return array|mixed
     * @throws \Exception
     */
    public function comparison($params)
    {
        $areaId = $params['area_id'];
        $quotaKey = $params['quota_key'];
        $cityId = $params['city_id'];

        // 指标算法映射
        $methods = [
            'speed' => 'round(avg(speed), 2) as speed',
            'stop_delay' => 'round(avg(stop_delay), 2) as stop_delay'
        ];

        $nameMaps = [
            'speed' => '区域平均速度',
            'stop_delay' => '区域平均延误'
        ];

        // 获取指标单位
        $units = array_column($this->config->item('area'), 'unit', 'key');

        // 指标不存在与映射数组中
        if(!isset($methods[$quotaKey])) {
            return [];
        }

        $areaInfo = $this->area_model->getAreaByAreaId($areaId);

        if(!$areaInfo) {
            throw new \Exception('该区域已被删除');
        }

        // 获取该区域全部路口ID
        $junctionList = $this->area_model->getJunctionsByAreaId($areaId, 'junction_id');

        // 数据获取失败 或者 数据为空
        if(!$junctionList || empty($junctionList)) {
            return [];
        }

        $junctionIds = array_column($junctionList, 'junction_id');

        // 基准时间范围
        $baseDates = dateRange($params['base_start_date'], $params['base_end_date']);

        // 评估时间范围
        $evaluateDates = dateRange($params['evaluate_start_date'], $params['evaluate_end_date']);

        // 生成 00:00 - 23:30 间的 粒度为 30 分钟的时间集合数组
        $hours = hourRange('00:00', '23:30');

        // 获取数据
        $select = 'date, hour, ' . $methods[$params['quota_key']];
        $dates = array_merge($baseDates, $evaluateDates);

        $result = $this->area_model->getJunctionByCityId($dates, $junctionIds, $hours, $cityId, $select);

        if(!$result || empty($result))
            return [];

        // 将数据按照 日期（基准 和 评估）进行分组的键名函数
        $baseOrEvaluateCallback = function ($item) use ($baseDates) {
            return in_array($item['date'], $baseDates) ? 'base' : 'evaluate';
        };

        // 数据分组后，将每组数据进行处理的函数
        $groupByItemFormatCallback = function ($item) use ($params, $hours) {
            $hourToNull = array_combine($hours, array_fill(0, 48, null));
            $item = array_column($item, $params['quota_key'], 'hour');
            $hourToValue = array_merge($hourToNull, $item);

            $result = [];

            foreach ($hourToValue as $hour => $value) {
                $result[] = [$hour, $value];
            }

            return $result;
        };

        // 数据处理
        $result = Collection::make($result)
            ->groupBy([$baseOrEvaluateCallback, 'date'], $groupByItemFormatCallback)
            ->get();

        $result['info'] = [
            'area_name' => $areaInfo['area_name'],
            'quota_name' => $nameMaps[$params['quota_key']] ?? '',
            'quota_unit' => $units[$params['quota_key']] ?? '',
            'base_time' => [$params['base_start_date'], $params['base_end_date']],
            'evaluate_time' => [$params['evaluate_start_date'], $params['evaluate_end_date']],
        ];

        $jsonResult = json_encode($result);

        $downloadId = md5($jsonResult);

        $result['info']['download_id'] = $downloadId;

        $redisKey = $this->config->item('quota_evaluate_key_prefix') . $downloadId;

        $this->redis_model->setData($redisKey, $jsonResult);

        $this->redis_model->setExpire($redisKey, 30 * 60);

        return $result;
    }
}