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
            $params["city_id"] = "12";
            $params["token"] = "4c3e3b6a3588161128d0604daab528db";
            $params["user_id"] = "signalPro";
            $params["polygon"] = implode(";", $points);
            $Url = "http://100.69.238.11:8000/its/signal-map/mapJunction/polygon";
            $ret = httpPOST($Url, $params);
            print_r($points);
            $polygonResponse = json_decode($ret, true);
            print_r(json_decode($ret, true));
            foreach ($polygonResponse["data"]["filter_juncs"] as $juncItem) {
                echo "INSERT INTO `area_junction_relation` (`id`, `area_id`, `junction_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '175', '" . $juncItem["logic_junction_id"] . "', '0', '2019-12-05 10:28:40', '2019-12-05 10:28:40', '1970-01-01 00:00:00');<br/>";
            }
            exit;
        }
        print_r($list);
    }
}
