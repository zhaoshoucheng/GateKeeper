<?php
/********************************************
# desc:    优化参数管理数据模型
# author:  ningxiangbing@didichuxing.com
# date:    2018-11-19
********************************************/

class Parametermanage_model extends CI_Model
{
    private $tb = 'optimized_parameter_config';
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
     * @param $status
     * @param $hour
     * @return array
     */
    public function getParameterByArea($cityId, $areaId, $isDefault, $status, $hour)
    {
        $res = $this->db->select('*')
                    ->where('city_id', $cityId),
                    ->where('area_id', $areaId),
                    ->where('is_default', $isDefault),
                    ->where('status', $status),
                    ->where('hour', $hour),
                    ->get()->result_array();
        if (empty($res)) {
            $res = $this->getParameter($isDefault, $status, $hour);
            if (empty($res)) {
                $res = $this->getParameter(1, $status, $hour);
            }
        }

        return $res;
    }

    /**
     * 获取优化配置的参数
     *
     * @param $isDefault
     * @param $status
     * @param $hour
     * @return array
     */
    public function getParameter($isDefault, $status, $hour)
    {
        $res = $this->db->select('*')
                    ->where('is_default', $isDefault),
                    ->where('status', $status),
                    ->where('hour', $hour),
                    ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }
}
