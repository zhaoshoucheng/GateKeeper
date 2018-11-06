<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/8/21
 * Time: 上午10:06
 */

class Period_model extends CI_Model
{
    /**
     * @var CI_DB_query_builder
     */
    protected $db;

    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);

    }

    public function getDistrictMonthData($cityId, $districtList, $year, $month)
    {
        $ret = $this->db->where(
            [
                'city_id' => $cityId,
                'year' => $year,
                'month' => intval($month),
            ]
        )->where_in('district_id', $districtList)->get('district_month_report')->result_array();

        return $ret;
    }

    public function getDistrictWeekData($cityId, $districtList, $dateList)
    {
        $ret = $this->db->where(
            [
                'city_id' => $cityId,
            ]
        )->where_in('district_id', $districtList)->where_in('date', $dateList)->get('district_week_report')->result_array();
        return $ret;
    }

    public function getDistrictHourData($cityId, $districtList, $dateList, $hourList = [])
    {
        $this->db->where(
            [
                'city_id' => $cityId,
            ]
        )->where_in(
            'date', $dateList
        )->where_in(
            'district_id', $districtList
        );
        if (!empty($hourList)) {
            $this->db->where_in('hour', $hourList);
        }

        $ret = $this->db->get('district_hour_report')->result_array();
        return $ret;
    }

    public function getCityMonthData($cityId, $year, $month)
    {
        $res = $this->db->select('*')
            ->from('city_month_report')
            ->where('city_id', $cityId)
            ->where('year', $year)
            ->where('month', $month)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    public function getCityWeekData($cityId, $date)
    {
        $res = $this->db->select('*')
            ->from('city_week_report')
            ->where('city_id', $cityId)
            ->where('date', $date)
            ->get();

        return $res instanceof CI_DB_result ? $res->result_array() : $res;
    }

    public function getCityHourData($cityId, $dateList, $hourList = [])
    {
        $this->db->select('*')
            ->from('city_hour_report')
            ->where('city_id', $cityId)
            ->where_in('date', $dateList);

        if (!empty($hourList)) {
            $this->db->where_in('hour', $hourList);
        }

        $ret = $this->db->get('city_hour_report')->result_array();
        return $ret;
    }

    public function getJunctionWeekData($cityId, $logicJunctionId = null, $dateList, $orderBy)
    {
        $where = [
            'city_id' => $cityId,
        ];
        if (!empty($logicJunctionId)) {
            $where['logic_junction_id'] = $logicJunctionId;
        }
        $ret = $this->db->where(
            $where
        )->where_in(
            'date', $dateList
        )->order_by($orderBy)->limit(1000)->get('junction_week_report')->result_array();

        return $ret;
    }

    public function getJunctionDayData($cityId, $logicJunctionId, $dateList, $orderBy)
    {
        $where = [
            'city_id' => $cityId,
        ];
        if (!empty($logicJunctionId)) {
            $where['logic_junction_id'] = $logicJunctionId;
        }
        $ret = $this->db->where(
            $where
        )->where_in(
            'date', $dateList
        )->order_by($orderBy)->limit(1000)->get('junction_week_report')->result_array();

        return $ret;
    }

    public function getJunctionHourData($cityId, $dateList, $hour, $orderBy)
    {
        $where = [
            'city_id' => $cityId,
        ];

        $ret = $this->db->where(
            $where
        )->where_in(
            'date', $dateList
        )->where_in(
            'hour', $hour
        )->order_by($orderBy)->limit(1000)->get('junction_hour_report')->result_array();

        return $ret;
    }

    public function getJunctionMonthData($cityId, $logicJunctionId = null, $year, $month, $orderBy)
    {
        $where = [
            'city_id' => $cityId,
            'year' => $year,
            'month' => $month,
        ];
        if (!empty($logicJunctionId)) {
            $where['logic_junction_id'] = $logicJunctionId;
        }
        $ret = $this->db->where(
            $where
        )->order_by($orderBy)->limit(1000)->get('junction_month_report')->result_array();

        return $ret;
    }


}