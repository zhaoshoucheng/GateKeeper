<?php

/**
 * Created by PhpStorm.
 * User: didi
 * Date: 2018/11/26
 * Time: 下午5:41
 */

class Demo extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->config('nconf');
        $this->load->helper('phase');
        $this->load->helper('http');
        $this->load->model('timing_model');
        $this->load->model('expressway_model');
    }

    /*http://data.sts.didichuxing.com/signal-map/mapJunction/suggest?keyword=%E6%96%87%E5%8C%96&type=didi&city_id=12&token=0faa6ca90df19d26635391c511d124a1&user_id=roadNet*/
    public function mapJunctionSuggest()
    {
        $params = $this->input->get();
        $params['token'] = "0faa6ca90df19d26635391c511d124a1";
        $params['user_id'] = "roadNet";
        $Url = 'http://100.69.238.11:8000/its/signal-map/mapJunction/suggest';
        $ret = httpGET($Url, $params);
        $finalRet = json_decode($ret, true);
        $this->response($finalRet['data']);
    }

    public function suzhouQuickRoadDelayList()
    {
        $params = [
            ["2020-05-06 07:00:00", "2020-05-06 09:59:59"],
            ["2020-05-07 07:00:00", "2020-05-07 09:59:59"],
            ["2020-05-08 07:00:00", "2020-05-08 09:59:59"],
            ["2020-05-09 07:00:00", "2020-05-09 09:59:59"],
            ["2020-05-06 16:30:00", "2020-05-06 18:59:59"],
            ["2020-05-07 16:30:00", "2020-05-07 18:59:59"],
            ["2020-05-08 16:30:00", "2020-05-08 18:59:59"],
            ["2020-05-09 16:30:00", "2020-05-09 18:59:59"],
        ];
        foreach ($params as $param) {
            $sql = 'SELECT count(*) as cnt,avg(delay) as avg_delay,downstream_ramp from cn_signal_pro_freeway_segment_index_online_* where city_id=23 and day_time_hms>="' . $param[0] . '" and day_time_hms<="' . $param[0] . '" group by downstream_ramp order by cnt desc limit 10000';
            $queryUrl = 'http://2317:W2oTX7qT7nYTKuD@100.90.164.31:8005/_sql';
            $response = httpPOST($queryUrl, $sql, 8000, 'raw');
            if (!$response) {
                return [];
            }
            $junctionIDs = [];
            $responseJson = json_decode($response, true);
            foreach ($responseJson["aggregations"]["downstream_ramp"]["buckets"] as $agg) {
                $junctionIDs[] = $agg["key"];
            }
            $junctionInfos = $this->expressway_model->getQuickRoadSegmentsByJunc(23);
            // print_r($junctionInfos);
            $juncNameMap = [];
            if (empty($junctionInfos) || empty($junctionInfos['junctions'])) {
                return [];
            }
            foreach ($junctionInfos['junctions'] as $j) {
                if ($j['type'] != 2) {
                    continue;
                }
                $juncNameMap[$j['junction_id']] = $j['name'];
            }

            $outputList = [];
            foreach ($responseJson["aggregations"]["downstream_ramp"]["buckets"] as $agg) {
                if (!isset($juncNameMap[$agg["key"]])) {
                    continue;
                }
                $outputList[] = [
                    "cnt" => $agg["doc_count"],
                    "avg_delay" => $agg["avg_delay"]["value"],
                    "downstream_ramp" => $agg["key"],
                    "segment_name" => $juncNameMap[$agg["key"]],
                ];
            }
            print_r($param[0] . " - " . $param[1]);
            echo "\n";
            print_r(json_encode(array_slice($outputList, 0, 30), 256));
            echo "\n";
        }
        exit;
        $resPart = json_decode($response, true);
    }


    public function polygon_sp()
    {
        $str = '{"type":"FeatureCollection","features":[{"type":"Feature","properties":{},"geometry":{"type":"Polygon","coordinates":[[[118.7299346923828,32.10206222548107],[118.70796203613281,32.07995649671902],[118.69285583496094,32.06366464325166],[118.6458206176758,31.974881296156596],[118.64616394042969,31.96760015887822],[118.6526870727539,31.962939927942923],[118.6578369140625,31.959153316146658],[118.66161346435548,31.954201357443576],[118.66573333740234,31.950414385353824],[118.66985321044922,31.947209902421964],[118.67362976074219,31.946918580249633],[118.67774963378906,31.949540446547825],[118.6849594116211,31.953036151889876],[118.69045257568358,31.953910057440805],[118.69628906249999,31.955657843600374],[118.70246887207031,31.957696885419626],[118.70693206787108,31.95798817341275],[118.71002197265624,31.957696885419626],[118.71551513671876,31.953910057440805],[118.72169494628906,31.952162238024975],[118.72856140136717,31.951579624162896],[118.73405456542969,31.952744848192008],[118.73645782470702,31.955949138060305],[118.73989105224611,31.958279460482014],[118.74572753906249,31.9594445995205],[118.74984741210938,31.959735881970452],[118.75362396240233,31.960027163496516],[118.75534057617188,31.96060972377701],[118.7563705444336,31.96993018564287],[118.75808715820311,31.976337454305813],[118.7570571899414,31.982161855862067],[118.75499725341798,31.988859460617025],[118.75602722167969,31.99351837553505],[118.75843048095703,31.998177053804007],[118.76117706298828,32.001088607540446],[118.76461029052733,32.00603803671034],[118.76735687255858,32.00982271401364],[118.77044677734375,32.013898345577914],[118.7728500366211,32.02117580827096],[118.77490997314453,32.02699736224841],[118.77765655517578,32.035437958338264],[118.77765655517578,32.04096758222761],[118.77765655517578,32.04882489427755],[118.77731323242186,32.053771744704605],[118.77456665039061,32.06104603892552],[118.76701354980469,32.06191891536363],[118.76014709472655,32.06250082836163],[118.75362396240233,32.06366464325166],[118.74778747558595,32.06220987232539],[118.74469757080078,32.06220987232539],[118.74195098876953,32.06686505784364],[118.73680114746094,32.07152000641795],[118.73268127441406,32.07355647190666],[118.72684478759766,32.077047450074716],[118.72444152832031,32.07821107984135],[118.7299346923828,32.10206222548107]]]}}]}';
        $p_arr = json_decode($str, true);
        $ps_arr = [];
        foreach ($p_arr["features"][0]["geometry"]["coordinates"][0] as $value) {
            $ps_arr[] = $value[0] . "," . $value[1];
        }
        $Url = 'http://100.90.164.31:8001/signal-map/mapJunction/polygon';
        $data = [];
        $data["city_id"] = "11";
        $data["version"] = "2019120418";
        $data["polygon"] = implode(";", $ps_arr);
        $ret = httpPOST($Url, $data);
        // print_r($data);exit;
        // print_r($ret);exit;
        $jsonData = "";
        $arr = json_decode($ret, true);
        // print_r($arr["data"]["dataList"]);
        // array_column(input, column_key);
        $junctions = array_keys($arr["data"]["filter_juncs"]);
        foreach ($junctions as $junctionid) {
            echo "INSERT INTO `area_junction_relation` (`id`, `area_id`, `junction_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '175', '" . $junctionid . "', '0', '2019-12-05 10:28:40', '2019-12-05 10:28:40', '1970-01-01 00:00:00');<br/>";
        }
        exit;
    }

    public function test2()
    {
        $Url = 'http://100.90.164.31:8001/signal-map/mapJunction/polygon';
        $data = [];
        $data["city_id"] = "5";
        $data["version"] = "2019120418";
        $data["polygon"] = "120.19832611083984,30.260030976266417;120.16802787780762,30.217393512825154;120.18519401550293,30.190021644057804;120.237036,30.217690;120.2239465713501,30.25250588146598;120.2118444442749,30.25921547660607";
        $ret = httpPOST($Url, $data);
        // print_r($ret);exit;
        $jsonData = "";
        $arr = json_decode($ret, true);
        // print_r($arr["data"]["dataList"]);
        // array_column(input, column_key);
        $junctions = array_keys($arr["data"]["filter_juncs"]);
        foreach ($junctions as $junctionid) {
            echo "INSERT INTO `area_junction_relation` (`id`, `area_id`, `junction_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '137', '" . $junctionid . "', '0', '2019-10-10 10:28:40', '2019-10-10 10:28:40', '1970-01-01 00:00:00');<br/>";
        }
        exit;
    }

    public function testPhase()
    {
        $flow = ["in_degree" => "89.727165", "out_degree" => "87.510447"];
        print_r("input:");
        print_r($flow);
        $phaseId = adjustPhase($flow);
        print_r("output:");
        print_r($phaseId);
        exit;
    }

    public function testHaixin()
    {
        //获取目录下所有php结尾的文件列表
        $list = glob('all_json/*.json');
        foreach ($list as $key => $filepath) {
            $filecontent = file_get_contents($filepath, true);
            $tarr = (json_decode($filecontent, true));
            $channelMap = [];
            $spotID = $tarr["Spot"];
            $junctionID = $this->timing_model->getHaixinJunctionID($spotID);
            // print_r($spotID);
            // print_r("<br>");
            if (empty($junctionID)) {
                continue;
            }
            // print_r($junctionID);
            print_r('"' . $junctionID . '": "' . $spotID . '",');
            print_r("<br>");
            foreach ($tarr["AscPlan"] as $key => $value) {
                foreach ($value["Channel"] as $vv) {
                    if (!isset($channelMap[$vv["CD"]])) {
                        $sql = "INSERT INTO `haixin_channel_map` (`id`, `junction_id`, `spot_id`, `sg_num`, `hx_num`, `updated_at`) VALUES (NULL, '" . $junctionID . "', '" . $spotID . "','" . $vv["CD"] . "', '" . $vv["CN"] . "', CURRENT_TIMESTAMP);";
                        $channelMap[$vv["CD"]] = $vv["CN"];
                        // print_r($sql);
                        // print_r("<br/>");
                        // exit;
                    }
                }
            }
            // exit;
            // exit;
        }
        exit;
    }

    public function districtsTest()
    {
        $url = "http://100.69.238.11:8000/its/signal-map/map/getList?city_id=11&offset=0&count=10000&districts=320106,320104,320113,320105,320114,320102&token=4c3e3b6a3588161128d0604daab528db&user_id=signalPro";
        $jsonData  = httpPOST($url, []);
        // $jsonData = json_decode($ret,true);
        // print_r($finalRet);exit;
        $arr = json_decode($jsonData, true);
        // print_r($arr);exit;
        // print_r($arr["data"]["dataList"]);
        // array_column(input, column_key);
        $junctions = array_column($arr["data"], "logic_junction_id");
        foreach ($junctions as $junctionid) {
            echo "INSERT INTO `area_junction_relation` (`id`, `area_id`, `junction_id`, `user_id`, `update_at`, `create_at`, `delete_at`) VALUES (NULL, '219', '" . $junctionid . "', '0', '2019-08-30 21:29:40', '2019-08-30 21:29:40', '1970-01-01 00:00:00');<br/>";
        }
        exit;
        // print_r($junctions);exit;
    }

    public function getShortUrl()
    {
        $params = $this->input->post();

        $url = "http://100.69.238.11:8000/daijia/shortserver/admin/add";
        $data = array();
        $data['appkey'] = "oveS7s8f3DymeHjnrUy0lfqBW1x1n3KD";
        $data['url']  = $params['url'];
        $ret  = httpPOST($url, $data);
        $finalRet = json_decode($ret, true);
        $this->response($finalRet['short_url']);
    }
}
