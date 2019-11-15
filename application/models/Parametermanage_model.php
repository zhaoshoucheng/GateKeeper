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
        $res = $this->db->select('*')
                    ->from($this->parameterLimitTB)
                    ->where('city_id', $cityId)
                    ->get()->result_array();
        return $res;
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
        if (empty($res)) {
            return $this->db->insert($this->tb, $data);
        }
        return $this->db->where('id', $res[0]['id'])->update($this->tb, $data);
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
        // print_r($res);exit;
        if(empty($res)){
            // print_r($data);exit;
            return $this->db->insert($this->parameterLimitTB, $data);
        }
        return $this->db->where('id', $res[0]['id'])->update($this->parameterLimitTB, $data);
    }
}
