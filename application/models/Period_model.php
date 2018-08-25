<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/21
 * Time: 上午10:06
 */

class Period_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

        if (empty($this->db)) {
            $this->db = $this->load->database('default', true);
        }

    }

    public function getDistrictMonthData($cityId,$districtList,$year,$month)
    {
        $ret = $this->db->where(
            array(
                'city_id'=>$cityId,
                'year'=>$year,
                'month'=>intval($month)
            )
        )->where_in('district_id',$districtList)->get('district_month_report')->result_array();

        return $ret;
    }

    public function getDistrictWeekData($cityId,$districtList,$dateList)
    {
        $ret = $this->db->where(
            array(
                'city_id'=>$cityId,
            )
        )->where_in('district_id',$districtList)->where_in('date',$dateList)->get('district_week_report')->result_array();
        return $ret;
    }

    public function getDistrictHourData($cityId,$districtList,$dateList,$hourList = array())
    {
        $this->db->where(
            array(
                'city_id'=>$cityId,
            )
        )->where_in(
            'date',$dateList
        )->where_in(
            'district_id',$districtList
        );
        if(!empty($hourList)){
            $this->db->where_in('hour',$hourList);
        }

        $ret = $this->db->get('district_hour_report')->result_array();
        return $ret;
    }

    public function getCityMonthData($cityId,$year,$month)
    {
        $ret = $this->db->where(
            array(
                'city_id'=>$cityId,
                'year'=>$year,
                'month'=>$month
            )
        )->get('city_month_report')->result_array();
        return $ret;
    }

    public function getCityWeekData($cityId,$date)
    {
        $ret = $this->db->where(
            array(
                'city_id'=>$cityId,
                'date'=>$date,
            )
        )->get('city_week_report')->result_array();
        return $ret;
    }

    public function getCityHourData($cityId,$dateList,$hourList = array())
    {
        $this->db->where(
            array(
                'city_id'=>$cityId,
            )
        )->where_in(
            'date',$dateList
        );
        if(!empty($hourList)){
            $this->db->where_in('hour',$hourList);
        }

        $ret = $this->db->get('city_hour_report')->result_array();
        return $ret;
    }

    public function getJunctionWeekData($cityId,$logicJunctionId=null,$dateList,$orderBy)
    {
        $where = array(
            'city_id'=>$cityId,
        );
        if(!empty($logicJunctionId)){
            $where['logic_junction_id'] = $logicJunctionId;
        }
        $ret = $this->db->where(
            $where
        )->where_in(
            'date',$dateList
        )->get('junction_week_report')->order_by($orderBy)->limit(100)->result_array();

        return $ret;
    }

    public function getJunctionDayData($cityId,$dateList)
    {

    }

    public function getJunctionHourData($cityId,$dateList,$hour,$orderBy)
    {
        $where = array(
            'city_id'=>$cityId,
        );

        $ret = $this->db->where(
            $where
        )->where_in(
            'date',$dateList
        )->where_in(
            'hour',$hour
        )->get('junction_hour_report')->order_by($orderBy)->limit(100)->result_array();

        return $ret;
    }

    public function getJunctionMonthData($cityId,$logicJunctionId=null,$year,$month,$orderBy)
    {
        $where = array(
            'city_id'=>$cityId,
            'year'=>$year,
            'month'=>$month
        );
        if(!empty($logicJunctionId)){
            $where['logic_junction_id'] = $logicJunctionId;
        }
        $ret = $this->db->where(
            $where
        )->get('junction_month_report')->order_by($orderBy)->limit(100)->result_array();

        return $ret;
    }


}