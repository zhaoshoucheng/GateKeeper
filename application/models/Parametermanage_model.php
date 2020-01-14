<?php
/********************************************
# desc:    优化参数管理数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-11-19
********************************************/

class Parametermanage_model extends CI_Model
{
    private $tb = 'optimized_offline_alarm_config';
    private $parameterLimitTB = 'optimized_parameter_config_limits';
    private $db = '';
    private $parameterMap = [];

    public function __construct()
    {
        parent::__construct();
        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

    }

    /**
     * 根据城市ID以及区域ID等参数获取优化配置的参数
     *
     * @param $cityId
     * @param $areaId
     * @param $isDefault
     * @return array
     */
    public function getParameterByArea($cityID, $areaID="")
    {
        $builder = $this->db->select('*')->from($this->tb)->where('city_id', $cityID);
        if(!empty($areaID)){
            $builder->where('area_id', $areaID);
        }else{
            $builder->where('area_id', "-1");
        }
        $res = $builder->order_by('hour asc')->get()->result_array();
        return $res;
    }

    /**
     * 根据城市ID以及区域ID等参数获取优化配置阀值的参数
     *
     * @param $cityId
     * @return array
     */
    public function getParameterLimit($cityId)
    {
        $this->load->model('redis_model');
        $redis_key = 'getParameterLimit_' . $cityId;
        $result = $this->redis_model->getData($redis_key);
        if(isset($this->parameterMap[$cityId])){
            return $this->parameterMap[$cityId];
        }
        if (empty($result)) {
            $res = $this->db->select('*')
                        ->from($this->parameterLimitTB)
                        ->where('city_id', $cityId)
                        ->get()->result_array();
            $this->redis_model->setEx($redis_key, json_encode($res), 60);
            $this->parameterMap[$cityId] = $res;
            return $res;
        }
        return json_decode($result, true);
    }

    /**
     * 更新优化配置的参数
     *
     * @param $data
     * @return bool 更新的结果
     */
    public function updateParameter($cityID, $areaID, $data)
    {
        unset($data['create_at']);
        unset($data['update_at']);
        unset($data['id']);
        $hour = $data['hour'];
        $data['city_id']=$cityID;
        $data['area_id']=$areaID;
        $data['update_at']=date("Y-m-d H:i:s");
        $res = $this->db->select('*')
                    ->from($this->tb)
                    ->where('city_id', $cityID)
                    ->where('area_id', $areaID)
                    ->where('hour', $hour)
                    ->get()->result_array();
        $changed = false;
        if(empty($res)){
            $changed = true;
        }
        if (empty($res)) {
            return [$this->db->insert($this->tb, $data),$changed];
        }
        if(!compareArrayKeysEqual($res[0],$data,["over_saturation_traj_num","over_stop_delay_up","over_saturation_multi_stop_ratio_up","over_saturation_none_stop_ratio_up","over_saturation_queue_length_up","over_saturation_queue_rate_up","spillover_traj_num","spillover_rate_down","spillover_queue_rate_down","spillover_avg_speed_down","unbalance_traj_num","unbalance_free_multi_stop_ratio_up","unbalance_free_none_stop_ratio_up","unbalance_free_queue_length_up","unbalance_over_saturation_multi_stop_ratio_up","unbalance_over_saturation_none_stop_ratio_up","unbalance_over_saturation_queue_length_up",])){
            $changed = true;
        }
        return [$this->db->where('id', $res[0]['id'])->update($this->tb, $data),$changed];
    }

    /**
     * 更新优化配置的参数阀值
     *
     * @param $data
     * @return bool 更新的结果
     */
    public function updateParameterLimit($cityID, $data)
    {
        unset($data['create_at']);
        unset($data['update_at']);
        unset($data['id']);
        $data['update_at'] = date("Y-m-d H:i:s");
        $data['city_id'] = $cityID;
        $res = $this->db->select('*')
                    ->from($this->parameterLimitTB)
                    ->where('city_id', $cityID)
                    ->get()->result_array();

        $changed = false;
        if(empty($res)){
            $changed = true;
        }
        if($data['city_id']!=$res[0]['city_id'] || $data['cycle_optimization_limit']!=$res[0]['cycle_optimization_limit'] || $data['cycle_optimization_lower_limit']!=$res[0]['cycle_optimization_lower_limit'] || $data['congestion_level_lower_limit']!=$res[0]['congestion_level_lower_limit'] || $data['slow_down_level_lower_limit']!=$res[0]['slow_down_level_lower_limit']){
            $changed = true;
        }
        if(empty($res)){
            return [$this->db->insert($this->parameterLimitTB, $data),$changed];
        }
        return [$this->db->where('id', $res[0]['id'])->update($this->parameterLimitTB, $data),$changed];
    }
}
