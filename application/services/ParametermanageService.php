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
        $this->load->model('realtimealarmconfig_model');
        $this->load->config('alarmanalysis_conf');
    }

    /**
     * 获取参数优化配置
     *
     * @param $params
     * @param $withoutDefault 不使用默认值
     *
     * @return array
     * @throws \Exception
     */
    public function realtimeAlarmParamList($params,$withoutDefault=0)
    {
        $dParams = json_decode($this->config->item('alarm_param_realtime_default'),true);
        try {
            $cityId = $params['city_id'];
            $areaId = $params['area_id']??"";
            $isDefault = $params['is_default'];
            $data = $this->realtimealarmconfig_model->getParameterLimit($cityId, $areaId);
            if($withoutDefault && empty($data)){
                return [];
            }
            if($isDefault || empty($data)){
                for($i=0;$i<24;$i++){
                    $dParams['hour'] = $i;
                    $data[$i] = $dParams;
                }
            }
            foreach ($data as $key => $value) {
                unset($data[$key]["id"]);
                unset($data[$key]["create_at"]);
                unset($data[$key]["update_at"]);
                unset($data[$key]["city_id"]);
                // unset($data[$key]["hour"]);
                unset($data[$key]["area_id"]);
            }
            $temp['params'] = $data;
            $temp['keys'] = $this->getRealtimeKeys();
            return $temp;
        } catch (Exception $e) {
            throw $e;
        }
        return [];
    }


    /**
     * 获取参数优化配置
     *
     * @param $params
     *
     * @return array
     * @throws \Exception
     */
    public function paramList($params,$withoutDefault=0)
    { 
        $dParams = json_decode($this->config->item('alarm_param_offline_default'),true);
        try {
            $cityId = $params['city_id'];
            $areaId = $params['area_id']??"";
            $isDefault = $params['is_default'];
            $data = $this->parametermanage_model->getParameterByArea($cityId, $areaId);
            if($withoutDefault && empty($data)){
                return [];
            }
            if($isDefault || empty($data)){
                for($i=0;$i<24;$i++){
                    $dParams['hour'] = $i;
                    $data[$i] = $dParams;
                }
            }
            foreach ($data as $key => $value) {
                unset($data[$key]["id"]);
                unset($data[$key]["create_at"]);
                unset($data[$key]["update_at"]);
                unset($data[$key]["city_id"]);
                // unset($data[$key]["hour"]);
                unset($data[$key]["area_id"]);
            }
            $temp['params'] = $data;
            $temp['keys'] = $this->getKeys();
            return $temp;
        } catch (Exception $e) {
            throw $e;
        }
        return [];
    }

    /**
     * 获取实时报警优化配置的展示指标
     *
     * @return array
     * @throws \Exception
     */
    public function getRealtimeKeys()
    {
        $res = [
            'overSatuTrailNumPara'=>[
                'name'=>'过饱和轨迹数',
                'key'=>'overSatuTrailNumPara',
                'union'=>'条',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'stopDelayPara'=>[
                'name'=>'过饱和停车延误',
                'key'=>'stopDelayPara',
                'union'=>'s',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'multiStopUpperBound'=>[
                'name'=>'多次停车比例上限',
                'key'=>'multiStopUpperBound',
                'union'=>'-',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'noneStopLowerBound'=>[
                'name'=>'不停车比例下限',
                'key'=>'noneStopLowerBound',
                'union'=>'-',
                'group'=>'过饱和',
                'operator'=>'小于',
            ],
            'queueLengthUpperBound'=>[
                'name'=>'排队长度上限',
                'key'=>'queueLengthUpperBound',
                'union'=>'m',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'queueRatioLowBound'=>[
                'name'=>'排队占比下限',
                'key'=>'queueRatioLowBound',
                'union'=>'-',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'spilloverTrailNumPara'=>[
                'name'=>'溢流轨迹数',
                'key'=>'spilloverTrailNumPara',
                'union'=>'条',
                'group'=>'溢流',
                'operator'=>'大于',
            ],
            'spilloverAlarmTrailNumPara'=>[
                'name'=>'溢流报警轨迹数',
                'key'=>'spilloverAlarmTrailNumPara',
                'union'=>'-',
                'group'=>'溢流',
                'operator'=>'大于',
            ],
            'spilloverStopDelayPara'=>[
                'name'=>'溢流停车延误',
                'key'=>'spilloverStopDelayPara',
                'union'=>'s',
                'group'=>'溢流',
                'operator'=>'大于',
            ],
            'queueRatioPara'=>[
                'name'=>'排队占比',
                'key'=>'queueRatioPara',
                'union'=>'-',
                'group'=>'溢流',
                'operator'=>'大于',
            ],
            'greenSlackTrailNumPara'=>[
                'name'=>'绿损轨迹数',
                'key'=>'greenSlackTrailNumPara',
                'union'=>'条',
                'group'=>'空放',
                'operator'=>'大于',
            ],
            'multiStopLowerBound'=>[
                'name'=>'多次停车比例下限',
                'key'=>'multiStopLowerBound',
                'union'=>'-',
                'group'=>'空放',
                'operator'=>'小于',
            ],
            'noneStopUpperBound'=>[
                'name'=>'不停车比例上限',
                'key'=>'noneStopUpperBound',
                'union'=>'-',
                'group'=>'空放',
                'operator'=>'大于',
            ],
            'queueLengthLowerBound'=>[
                'name'=>'排队长度下限',
                'key'=>'queueLengthLowerBound',
                'union'=>'-',
                'group'=>'空放',
                'operator'=>'小于',
            ],
        ];
        return $res;
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
                'union'=>'条',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'over_stop_delay_up'=>[
                'name'=>'上游停车延误',
                'key'=>'over_stop_delay_up',
                'union'=>'s',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'over_saturation_multi_stop_ratio_up'=>[
                'name'=>'过饱和上游二次停车比例',
                'key'=>'over_saturation_multi_stop_ratio_up',
                'union'=>'-',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'over_saturation_none_stop_ratio_up'=>[
                'name'=>'过饱和上游无停车比例',
                'key'=>'over_saturation_none_stop_ratio_up',
                'union'=>'-',
                'group'=>'过饱和',
                'operator'=>'小于',
            ],
            'over_saturation_queue_length_up'=>[
                'name'=>'过饱和上游排队长度',
                'key'=>'over_saturation_queue_length_up',
                'union'=>'m',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'over_saturation_queue_rate_up'=>[
                'name'=>'过饱和上游排队占比',
                'key'=>'over_saturation_queue_rate_up',
                'union'=>'-',
                'group'=>'过饱和',
                'operator'=>'大于',
            ],
            'spillover_traj_num'=>[
                'name'=>'溢流轨迹量',
                'key'=>'spillover_traj_num',
                'union'=>'条',
                'group'=>'溢流',
                'operator'=>'大于',
            ],
            'spillover_rate_down'=>[
                'name'=>'溢流下游溢流比率',
                'key'=>'spillover_rate_down',
                'union'=>'-',
                'group'=>'溢流',
                'operator'=>'大于',
            ],
            'spillover_queue_rate_down'=>[
                'name'=>'溢流下游排队占比',
                'key'=>'spillover_queue_rate_down',
                'union'=>'-',
                'group'=>'溢流',
                'operator'=>'大于',
            ],
            'spillover_avg_speed_down'=>[
                'name'=>'溢流下游平均速度',
                'key'=>'spillover_avg_speed_down',
                'union'=>'km/h',
                'group'=>'溢流',
                'operator'=>'小于',
            ],
            'unbalance_traj_num'=>[
                'name'=>'失衡轨迹量',
                'key'=>'unbalance_traj_num',
                'union'=>'条',
                'group'=>'空放',
                'operator'=>'大于',
            ],
            /*'unbalance_over_saturation_multi_stop_ratio_up'=>[
                'name'=>'失衡过饱和上游二次停车比例',
                'key'=>'unbalance_over_saturation_multi_stop_ratio_up',
                'union'=>'-',
                'group'=>'空放',
                'operator'=>'小于',
            ],
            'unbalance_over_saturation_none_stop_ratio_up'=>[
                'name'=>'失衡过饱和上游无停车比例',
                'key'=>'unbalance_over_saturation_none_stop_ratio_up',
                'union'=>'-',
                'group'=>'空放',
                'operator'=>'小于',
            ],
            'unbalance_over_saturation_queue_length_up'=>[
                'name'=>'失衡过饱和上游排队长度',
                'key'=>'unbalance_over_saturation_queue_length_up',
                'union'=>'-',
                'group'=>'空放',
                'operator'=>'小于',
            ],
            */
            'unbalance_free_multi_stop_ratio_up'=>[
                'name'=>'失衡空放上游二次停车比例',
                'key'=>'unbalance_free_multi_stop_ratio_up',
                'union'=>'-',
                'group'=>'空放',
                'operator'=>'小于',
            ],
            'unbalance_free_none_stop_ratio_up'=>[
                'name'=>'失衡空放上游无停车比例',
                'key'=>'unbalance_free_none_stop_ratio_up',
                'union'=>'-',
                'group'=>'空放',
                'operator'=>'大于',
            ],
            'unbalance_free_queue_length_up'=>[
                'name'=>'失衡空放上游排队长度',
                'key'=>'unbalance_free_queue_length_up',
                'union'=>'-',
                'group'=>'空放', 
                'operator'=>'小于',
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
        $cityID = $params['city_id'];
        $res = $this->parametermanage_model->getParameterLimit($cityID);
        $dParams = json_decode($this->config->item('tool_param_default'),true);
        if(empty($res)){
            $dParams["city_id"] = $cityID;
            $res[0] = $dParams;
        }
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
                foreach ($data as $temp) {
                    $this->parametermanage_model->updateParameter($cityID, $areaID, $temp);
                }
            }
            if (isset($param['realtime_alarm_params'])) {
                $data = $param['realtime_alarm_params'];
                if (empty($data)) {
                    return true;
                }
                foreach ($data as $temp) {
                    $this->realtimealarmconfig_model->updateParameter($cityID, $areaID, $temp);
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
        return false;
    }
}
