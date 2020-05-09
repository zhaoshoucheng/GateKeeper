<?php

/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/10/20
 * Time: 下午7:19
 */

class Sjgt_model extends CI_Model
{
    private $tb = 'datain_SJGT_tp_platform_area_db';

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

        $isExisted = $this->db->table_exists($this->tb);
        if (!$isExisted) {
            throw new \Exception('数据表不存在', ERR_DATABASE);
        }
    }

    /**
     * 获取指定路口的自适应配时信息
     *
     * @return array
     */
    public function getTransfor()
    {
        $res = $this->db
            ->from($this->tb)
            ->order_by("id", "desc")
            ->get();
        $list = $res instanceof CI_DB_result ? $res->result_array() : $res;
        foreach ($list as $item) {
            $coordinates = json_decode($item["area_geometry"], true);
            $points = [];
            foreach ($coordinates["coordinates"][0] as $point) {
                $points[] = implode(",", $point);
            }
            // token=4c3e3b6a3588161128d0604daab528db&user_id=signalPro
            $params = [];
            $params["token"] = "4c3e3b6a3588161128d0604daab528db";
            $params["user_id"] = "signalPro";
            $params["polygon"] = implode(",", $points);
            $Url = "http://100.69.238.11:8000/its/signal-map/mapJunction/polygon";
            $ret = httpPOST($Url, $params);
            print_r($points);
            print_r($ret);
            exit;
            $polygon = [];
            // http://100.90.164.31:8001/signal-map/mapJunction/polygon?city_id=12&districts=370102,370103&polygon=116.930362,36.724026;117.115069,36.719623;117.187854,36.654649;117.086917,36.612772;116.933108,36.664013&version=2018071912
        }
        print_r($list);
    }
}
