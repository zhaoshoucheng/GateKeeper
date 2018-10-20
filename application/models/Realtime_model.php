<?php
/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午4:22
 */

class Realtime_model extends CI_Model
{
    private $tb = 'read_time_';

    /**
     * @var \CI_DB_query_builder
     */
    protected $db;

    /**
     * Area_model constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->db = $this->load->database('default', true);
    }

    /**
     * 获得指定城市实时表的最新 hour
     *
     * @param $cityId
     *
     * @return array
     */
    public function getLastestHour($cityId)
    {
        return $this->db->select('hour')
            ->from($this->tb . $cityId)
            ->order_by('updated_at', 'DESC')
            ->order_by('hour', 'DESC')
            ->limit(1)
            ->get()->row_array();
    }
}