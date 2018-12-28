<?php
/**
 * 信控管理接口数据处理
 * user:ningxiangbing@didichuxing.com
 * date:2018-11-19
 */

namespace Services;

class ParametermanageService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->helperService = new HelperService();
        $this->load->model('parametermanage_model');
    }

    /**
     * 获取参数优化配置
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function paramList($params)
    {
        try {
            $cityId = $params['city_id'];
            $areaId = $params['area_id'];
            $isDefault = $params['is_default'];
            $data = $this->parametermanage_model->getParameterByArea($cityId, $areaId, $isDefault);
            if (empty($data)) {
                return [];
            }
            $res = [];
            foreach ($data as $k => $v) {
                $hour = $v['hour'];
                $status = $v['status'];
                switch ($status) {
                    case 1:
                        $res[$hour]['over_saturation_traj_num'] = $v['over_saturation_traj_num'];
                        $res[$hour]['over_saturation_multi_stop_ratio_up'] = $v['multi_stop_ratio_up'];
                        $res[$hour]['over_saturation_none_stop_ratio_up'] = 1 - $v['multi_stop_ratio_up'] - $v['one_stop_ratio_up'];
                        $res[$hour]['over_saturation_queue_length_up'] = $v['queue_length_up'];
                        $res[$hour]['over_saturation_queue_rate_up'] = $v['queue_rate_up'];
                        break;
                    case 2:
                        $res[$hour]['spillover_traj_num'] = $v['spillover_traj_num'];
                        $res[$hour]['spillover_rate_down'] = $v['spillover_rate_down'];
                        $res[$hour]['spillover_queue_rate_down'] = $v['queue_rate_down'];
                        $res[$hour]['spillover_avg_speed_down'] = $v['avg_speed_down'];
                        break;
                    case 3:
                        $res[$hour]['unbalance_traj_num'] = $v['unbalance_traj_num'];
                        $res[$hour]['unbalance_free_multi_stop_ratio_up'] = $v['multi_stop_ratio_up'];
                        $res[$hour]['unbalance_free_none_stop_ratio_up'] = 1 - $v['multi_stop_ratio_up'] - $v['one_stop_ratio_up'];
                        $res[$hour]['unbalance_free_queue_length_up'] = $v['queue_length_up'];
                        break;
                    case 4:
                        $res[$hour]['unbalance_traj_num'] = $v['unbalance_traj_num'];
                        $res[$hour]['unbalance_over_saturation_multi_stop_ratio_up'] = $v['multi_stop_ratio_up'];
                        $res[$hour]['unbalance_over_saturation_none_stop_ratio_up'] = 1 - $v['multi_stop_ratio_up'] - $v['one_stop_ratio_up'];
                        $res[$hour]['unbalance_over_saturation_queue_length_up'] = $v['queue_length_up'];
                        break;
                    default:
                        return [];
                }
            }
            $temp['params'] = $res;
            $temp['keys'] = $this->getKeys();
            return $temp;
        } catch (Exception $e) {
            throw $e;
        }
        return [];
    }

    /**
     * 获取参数优化配置的展示指标
     *
     * @return array
     * @throws \Exception
     */
    public function getKeys()
    {
        $res = [
            'over_saturation_traj_num'=>[
                'name'=>'过饱和轨迹量',
                'key'=>'over_saturation_traj_num',
            ],
            'over_saturation_multi_stop_ratio_up'=>[
                'name'=>'过饱和上游二次停车比例',
                'key'=>'over_saturation_multi_stop_ratio_up',
            ],
            'over_saturation_none_stop_ratio_up'=>[
                'name'=>'过饱和上游无停车比例',
                'key'=>'over_saturation_none_stop_ratio_up',
            ],
            'over_saturation_queue_length_up'=>[
                'name'=>'过饱和上游排队长度',
                'key'=>'over_saturation_queue_length_up',
            ],
            'over_saturation_queue_rate_up'=>[
                'name'=>'过饱和上游排队占比',
                'key'=>'over_saturation_queue_rate_up',
            ],
            'spillover_traj_num'=>[
                'name'=>'溢流轨迹量',
                'key'=>'spillover_traj_num',
            ],
            'spillover_rate_down'=>[
                'name'=>'溢流下游溢流比率',
                'key'=>'spillover_rate_down',
            ],
            'spillover_queue_rate_down'=>[
                'name'=>'溢流下游排队占比',
                'key'=>'spillover_queue_rate_down',
            ],
            'spillover_avg_speed_down'=>[
                'name'=>'溢流下游平均速度',
                'key'=>'spillover_avg_speed_down',
            ],
            'unbalance_traj_num'=>[
                'name'=>'失衡轨迹量',
                'key'=>'unbalance_traj_num',
            ],
            'unbalance_over_saturation_multi_stop_ratio_up'=>[
                'name'=>'失衡过饱和上游二次停车比例',
                'key'=>'unbalance_over_saturation_multi_stop_ratio_up',
            ],
            'unbalance_over_saturation_none_stop_ratio_up'=>[
                'name'=>'失衡过饱和上游无停车比例',
                'key'=>'unbalance_over_saturation_none_stop_ratio_up',
            ],
            'unbalance_over_saturation_queue_length_up'=>[
                'name'=>'失衡过饱和上游排队长度',
                'key'=>'unbalance_over_saturation_queue_length_up',
            ],
            'unbalance_free_multi_stop_ratio_up'=>[
                'name'=>'失衡空放上游二次停车比例',
                'key'=>'unbalance_free_multi_stop_ratio_up',
            ],
            'unbalance_free_none_stop_ratio_up'=>[
                'name'=>'失衡空放上游无停车比例',
                'key'=>'unbalance_free_none_stop_ratio_up',
            ],
            'unbalance_free_queue_length_up'=>[
                'name'=>'失衡空放上游排队长度',
                'key'=>'unbalance_free_queue_length_up',
            ],
        ];
        return $res;
    }

    /**
     * 获取参数优化配置阀值
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function paramLimit($params)
    {
        $cityId = $params['city_id'];
        $res = $this->parametermanage_model->getParameterLimit($cityId);
        return $res;
    }

    /**
     * 更新参数优化配置
     *
     * @param $params
     *
     * @return bool
     * @throws \Exception
     */
    public function updateParam($param)
    {
        try {
            $cityID = $param['city_id'];
            $areaID = $param['area_id'];
            if (isset($param['param_limits'])) {
                if (!$this->parametermanage_model->updateParameterLimit($cityID, $param['param_limits'])) {
                    return false;
                }
            }
            if (isset($param['params'])) {
                $data = $param['params'];
                if (empty($data)) {
                    return true;
                }
                foreach ($data as $now) {
                    $status = 1;
                    $temp = [
                        'hour' => $now['hour'],
                        'over_saturation_traj_num' => $now['over_saturation_traj_num'],
                        'multi_stop_ratio_up' => $now['over_saturation_multi_stop_ratio_up'],
                        'one_stop_ratio_up' => 1 - $now['over_saturation_none_stop_ratio_up'] - $now['over_saturation_multi_stop_ratio_up'],
                        'queue_length_up' => $now['over_saturation_queue_length_up'],
                        'queue_rate_up' => $now['over_saturation_queue_rate_up'],
                    ];
                    if (!$this->parametermanage_model->updateParameter($cityID, $areaID, $status, $temp)) {
                        return false;
                    }
                    $status = 2;
                    $temp = [
                        'hour' => $now['hour'],
                        'spillover_traj_num' => $now['spillover_traj_num'],
                        'spillover_rate_down' => $now['spillover_rate_down'],
                        'queue_rate_down' => $now['spillover_queue_rate_down'],
                        'avg_speed_down' => $now['spillover_avg_speed_down'],
                    ];
                    if (!$this->parametermanage_model->updateParameter($cityID, $areaID, $status, $temp)) {
                        return false;
                    }
                    $status = 3;
                    $temp = [
                        'hour' => $now['hour'],
                        'unbalance_traj_num' => $now['unbalance_traj_num'],
                        'multi_stop_ratio_up' => $now['unbalance_free_multi_stop_ratio_up'],
                        'one_stop_ratio_up' => 1 - $now['unbalance_free_none_stop_ratio_up'] - $now['unbalance_free_multi_stop_ratio_up'],
                        'queue_length_up' => $now['unbalance_free_queue_length_up'],
                    ];
                    if (!$this->parametermanage_model->updateParameter($cityID, $areaID, $status, $temp)) {
                        return false;
                    }
                    $status = 4;
                    $temp = [
                        'hour' => $now['hour'],
                        'unbalance_traj_num' => $now['unbalance_traj_num'],
                        'multi_stop_ratio_up' => $now['unbalance_over_saturation_multi_stop_ratio_up'],
                        'one_stop_ratio_up' => 1 - $now['unbalance_over_saturation_none_stop_ratio_up'] - $now['unbalance_over_saturation_multi_stop_ratio_up'],
                        'queue_length_up' => $now['unbalance_over_saturation_queue_length_up'],
                    ];
                    if (!$this->parametermanage_model->updateParameter($cityID, $areaID, $status, $temp)) {
                        return false;
                    }
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
        return false;
    }
}
