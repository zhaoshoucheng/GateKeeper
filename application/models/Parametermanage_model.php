<?php
/********************************************
# desc:    优化参数管理数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-11-19
********************************************/

class Parametermanage_model extends CI_Model
{
    private $tb = 'optimized_parameter_config';
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
    public function getParameterByArea($cityId, $areaId, $isDefault)
    {
        $res = $this->db->select('*')
                    ->from($this->tb)
                    ->where('city_id', $cityId)
                    ->where('area_id', $areaId)
                    ->where('is_default', $isDefault)
                    ->order_by('status', 'hour')
                    ->get()->result_array();
        if (empty($res)) {
            $isDefault = 1;
            $res = $this->db->select('*')
                        ->from($this->tb)
                        ->where('city_id', $cityId)
                        ->where('area_id', $areaId)
                        ->where('is_default', $isDefault)
                        ->order_by('status', 'hour')
                        ->get()->result_array();
        }

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
    public function updateParameter($data)
    {
        unset($data['create_at']);
        unset($data['update_at']);
        unset($data['id']);
        $data['is_default'] = 0;

        $cityId = $data['city_id'];
        $areaId = $data['area_id'];
        $hour = $data['hour'];
        $status = $data['status'];

        $res = $this->db->select('*')
                    ->from($this->tb)
                    ->where('city_id', $cityId)
                    ->where('area_id', $areaId)
                    ->where('hour', $hour)
                    ->where('status', $status)
                    ->where('is_default', 0)
                    ->get()->result_array();

        if (empty($res)) {
            $res = $this->db->select('*')
                        ->from($this->tb)
                        ->where('city_id', $cityId)
                        ->where('area_id', $areaId)
                        ->where('hour', $hour)
                        ->where('status', $status)
                        ->where('is_default', 1)
                        ->get()->result_array();
            $res = $res[0];
            unset($res['id']);
            unset($res['create_at']);
            unset($res['update_at']);
            foreach ($data as $k=>$v) {
                $res[$k] = $v;
            }
            return $this->db->insert($this->tb, $res);
        }
        return $this->db->where('id', $res[0]['id'])
                    ->update($this->tb, $data);
    }

    /**
     * 更新优化配置的参数阀值
     *
     * @param $data
     * @return bool 更新的结果
     */
    public function updateParameterLimit($data)
    {
        unset($data['create_at']);
        unset($data['update_at']);
        unset($data['id']);
        $data['is_default'] = 0;

        $cityId = $data['city_id'];

        $res = $this->db->select('*')
                    ->from($this->parameterLimitTB)
                    ->where('city_id', $cityId)
                    ->where('is_default', 0)
                    ->get()->result_array();

        if (empty($res)) {
            $res = $this->db->select('*')
                        ->from($this->parameterLimitTB)
                        ->where('city_id', $cityId)
                        ->where('is_default', 1)
                        ->get()->result_array();
            $res = $res[0];
            unset($res['id']);
            unset($res['create_at']);
            unset($res['update_at']);
            foreach ($data as $k=>$v) {
                $res[$k] = $v;
            }
            return $this->db->insert($this->parameterLimitTB, $res);
        }
        return $this->db->where('id', $res[0]['id'])
                    ->update($this->parameterLimitTB, $data);
    }
}
