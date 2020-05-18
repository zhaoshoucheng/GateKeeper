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

        $this->test_db = $this->load->database('default', true);
        $this->db = $this->load->database('default', true);
        
        // $isExisted = $this->test_db->table_exists($this->tb);
        // if (!$isExisted) {
            // throw new \Exception('数据表不存在', ERR_DATABASE);
        // }
    }

    /**
     * 获取指定路口的自适应配时信息
     *
     * @return array
     */
    public function getTransfor()
    {
        $res = $this->test_db
            ->from($this->tb)
            ->order_by("id", "desc")
            ->get();
        $list = $res instanceof CI_DB_result ? $res->result_array() : $res;
        $this->db->trans_begin();
        foreach ($list as $item) {
            $coordinates = json_decode($item["area_geometry"], true);
            $points = [];
            // print_r($item);
            $insertSql =  "INSERT INTO `area` (`id`, `area_name`, `city_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '" . $item["area_name"] . "', '12', '0', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."', '1970-01-01 00:00:00');";
            $this->db->query($insertSql);
            $areaID=$this->db->insert_id();
            $unionJuncIds = [];
            if ($coordinates["type"] == "MultiPolygon") {
                foreach ($coordinates["coordinates"] as $partCoordinate) {
                    foreach ($partCoordinate[0] as $point) {
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
                    $polygonResponse = json_decode($ret, true);
                    $filterJuncs=$polygonResponse["data"]["filter_juncs"];
                    if(!empty($filterJuncs)){
                        $juncIds=array_column($filterJuncs,"logic_junction_id");
                        $unionJuncIds = array_unique(array_merge($unionJuncIds,$juncIds));
                    }
                }
            }else{ 
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
                // print_r($points);
                $polygonResponse = json_decode($ret, true);
                // print_r(json_decode($ret, true));
                $filterJuncs=$polygonResponse["data"]["filter_juncs"];
                if(!empty($filterJuncs)){ 
                    $unionJuncIds=array_unique(array_column($filterJuncs,"logic_junction_id"));
                }
            }
            // print_r($unionJuncIds);
            foreach ($unionJuncIds as $juncId) {
                $insertSql = "INSERT INTO `area_junction_relation` (`id`, `area_id`, `junction_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '".$areaID."', '" . $juncId . "', '0', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."', '1970-01-01 00:00:00');";
                // echo $insertSql."\n";
                $this->db->query($insertSql);
            }
            // $this->db->trans_commit();
            // exit;
        }
        $this->db->trans_commit();
        echo "all done\n";
        // print_r($list);
    }
}
