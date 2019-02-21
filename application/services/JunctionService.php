<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/26
 * Time: 下午1:10
 */

namespace Services;

use Didi\Cloud\Collection\Collection;

/**
 * Class JunctionService
 * @package Services
 * @property \FlowDurationV6_model $flowDurationV6_model
 */
class JunctionService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('waymap_model');
        $this->load->model('flowDurationV6_model');

        $this->load->config('report_conf');

    }

    /**
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function queryQuotaInfo($params)
    {
        $type              = $params['type'];
        $cityId            = $params['city_id'];
        $quotaKey          = $params['quota_key'];
        $logicJunctionId   = $params['logic_junction_id'];
        $evaluateStartDate = $params['evaluate_start_date'];
        $evaluateEndDate   = $params['evaluate_end_date'];
        $scheduleStart     = $params['schedule_start'];
        $scheduleEnd       = $params['schedule_end'];

        $quotas = $this->config->item('quotas');

        $dates = dateRange($evaluateStartDate, $evaluateEndDate);

        if (empty($dates)) {
            throw new \Exception('日期范围不能为空', ERR_PARAMETERS);
        }

        $hours = hourRange($scheduleStart, $scheduleEnd);

        if (empty($hours)) {
            throw new \Exception('时间范围不能为空', ERR_PARAMETERS);
        }

        $select = 'hour, logic_flow_id';

        $result = $this->flowDurationV6_model->getQuotaByJunction($cityId, $logicJunctionId, $dates, $hours, $quotaKey, $select);

        if (empty($result)) {
            return [];
        }

        $junctionInfo = $this->waymap_model->getJunctionInfo($logicJunctionId);
        $junctionInfo = array_column($junctionInfo, null, 'logic_junction_id');

        $flowsInfo = $this->waymap_model->getFlowsInfo($logicJunctionId);

        $junctionInfo = [
            'junction' => $junctionInfo[$logicJunctionId] ?? [],
            'flows' => $flowsInfo[$logicJunctionId] ?? [],
        ];

        $flowsName = $junctionInfo['flows'];

        //构建二维数据表以映射折线图，同时创建以时间为依据分组的数据
        $firstQuotaKey = function ($item) use ($quotaKey) {
            return current($item)[$quotaKey] ?? '';
        };

        $resultCollection = Collection::make($result);
        $dataByFlow = $resultCollection->groupBy(['logic_flow_id', 'hour'], $firstQuotaKey);
        $dataByHour = $resultCollection->groupBy(['hour', 'logic_flow_id'], $firstQuotaKey);
        //求出每个方向的全天均值中最大的方向 ID //如果有多个最大值，则取平均求最大
        $maxValueReduce = function (Collection $carry, $item) {
            $incrementItem = function (Collection $ca, $it) {
                return $ca->increment($it);
            };
            return Collection::make($item)->keysOfMaxValue()->reduce($incrementItem, $carry);
        };
        $setValueReduce = function (Collection $carry, $item) use ($dataByHour) {
            return $carry->set($item, $dataByHour->avg($item));
        };

        /**
         * @var Collection $maxFlowIds
         */
        $maxFlowIds = $dataByHour->reduce($maxValueReduce, Collection::make([]))
            ->keysOfMaxValue()
            ->reduce($setValueReduce, Collection::make([]))
            ->keysOfMaxValue();

        //找出均值最大的方向的最大值最长持续时间区域
        $base_time_box = $maxFlowIds->reduce(function (Collection $carry, $id) use ($dataByFlow, $dataByHour) {
            $maxFlow         = Collection::make($dataByFlow->get($id));
            $maxFlowFirstKey = $maxFlow->keys()->first(null, '');
            $maxArray        = $nowArray = [
                'start_time' => $maxFlowFirstKey,
                'end_time' => $maxFlowFirstKey,
                'length' => 0,
            ];
            $maxFlow->each(function ($quota, $hour) use ($dataByHour, &$nowArray, &$maxArray) {
                $max = max($dataByHour->get($hour));
                if ($quota >= $max && $quota > 0) {
                    $nowArray['end_time'] = $hour;
                    if ($nowArray['start_time'] == '') {
                        $nowArray['start_time'] = $hour;
                    }
                    $nowArray['length']++;
                } else {
                    if ($nowArray['length'] > $maxArray['length']) {
                        $maxArray = $nowArray;
                    }
                    $nowArray = ['start_time' => '', 'end_time' => '', 'length' => 0,];
                }
            });
            if ($nowArray['length'] < $maxArray['length']) {
                $nowArray = $maxArray;
            }
            if ($carry->isEmpty() || $carry->get('0.length', 0) == $nowArray['length']) {
                return $carry->set($id, $nowArray);
            } elseif ($carry->get('0.length', 0) < $nowArray['length']) {
                return Collection::make([$id => $nowArray]);
            } else {
                return $carry;
            }
        }, Collection::make([]));

        //如果某个时间点某个方向没有数据，则设为 null
        $hours      = Collection::make($hours);
        $dataByFlow = $dataByFlow->map(function ($flow) use ($hours) {
            return $hours->reduce(function (Collection $carry, $item) {
                return $carry->add($item, null);
            }, Collection::make($flow))->ksort()->get();
        });

        $dataByFlow->each(function ($value, $ke) use (&$base, &$flow_info, &$maxFlowIds, $flowsName, $quotaKey, $quotas) {
            $base[$ke] = [];
            foreach ($value as $k => $v) {
                $base[$ke][] = [$v === null ? null : $quotas[$quotaKey]['round']($v), $k];
            }
            $flow_info[$ke] = ['name' => $flowsName[$ke] ?? '', 'highlight' => (int)($maxFlowIds->inArray($ke))];
        });

        $base_time_box->each(function ($v, $k) use (&$describes, &$summarys, $quotaKey, $junctionInfo, $quotas) {
            $describes[] = $quotas[$quotaKey]['describe']([
                $junctionInfo['junction']['name'] ?? '',
                $junctionInfo['flows'][$k] ?? '',
                $v['start_time'],
                $v['end_time']]);
            $summarys[]  = $quotas[$quotaKey]['summary']([
                $v['start_time'],
                $v['end_time'],
                $junctionInfo['flows'][$k] ?? '']);
        });

        $base_time_box = $base_time_box->all();
        $describe_info = implode("\n", $describes);
        $summary_info  = implode("\n", $summarys);

        $pretreatResultData = compact('base', 'flow_info', 'base_time_box', 'describe_info', 'summary_info');

        return [
            'info' => [
                'junction_name' => $junctionInfo['junction']['name'] ?? '',
                'junction_lng' => $junctionInfo['junction']['lng'] ?? '',
                'junction_lat' => $junctionInfo['junction']['lat'] ?? '',
                'quota_name' => $quotas[$quotaKey]['name'],
                'quota_unit' => $quotas[$quotaKey]['unit'],
                'quota_desc' => $quotas[$quotaKey]['desc'][$type],
                'summary_info' => $pretreatResultData['summary_info'],
                'describe_info' => $pretreatResultData['describe_info'],
                'flow_info' => $pretreatResultData['flow_info'],
                'base_time_box' => $pretreatResultData['base_time_box'],
            ],
            'base' => $pretreatResultData['base'],
        ];
    }
}